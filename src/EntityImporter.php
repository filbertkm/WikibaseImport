<?php

namespace Wikibase\Import;

use Psr\Log\LoggerInterface;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Store\WikiPageEntityStore;

class EntityImporter {

	private $statementsImporter;

	private $badgeItemUpdater;

	private $apiEntityLookup;

	private $entityStore;

	private $entityMappingStore;

	private $logger;

	private $statementsCountLookup;

	private $idParser;

	private $importUser;

	private $batchSize;

	public function __construct(
		StatementsImporter $statementsImporter,
		BadgeItemUpdater $badgeItemUpdater,
		ApiEntityLookup $apiEntityLookup,
		WikiPageEntityStore $entityStore,
		ImportedEntityMappingStore $entityMappingStore,
		StatementsCountLookup $statementsCountLookup,
		LoggerInterface $logger
	) {
		$this->statementsImporter = $statementsImporter;
		$this->badgeItemUpdater = $badgeItemUpdater;
		$this->apiEntityLookup = $apiEntityLookup;
		$this->entityStore = $entityStore;
		$this->entityMappingStore = $entityMappingStore;
		$this->statementsCountLookup = $statementsCountLookup;
		$this->logger = $logger;

		$this->idParser = new BasicEntityIdParser();
		$this->importUser = User::newFromId( 0 );
		$this->batchSize = 10;
	}

	public function importEntities( array $ids, $importStatements = true ) {
		$batches = array_chunk( $ids, $this->batchSize );

		$stashedEntities = array();

		foreach( $batches as $batch ) {
			$entities = $this->apiEntityLookup->getEntities( $batch );

			if ( $entities ) {
				$this->importBadgeItems( $entities );
			} else {
				$this->logger->error( 'Failed to retrieve badge items' );
			}

			$stashedEntities = array_merge( $stashedEntities, $this->importBatch( $batch ) );
		}

		if ( $importStatements === true ) {
			foreach( $stashedEntities as $entity ) {
				$referencedEntities = $this->getReferencedEntities( $entity );
				$this->importEntities( $referencedEntities, false );

				$originalId = $entity->getId()->getSerialization();

				$localId = $this->entityMappingStore->getLocalId( $originalId );
				$entityId = $this->idParser->parse( $localId );

				if ( !$this->statementsCountLookup->hasStatements( $entityId ) ) {
					$this->statementsImporter->importStatements( $entity );
				} else {
					$this->logger->info( "Statements already imported for $originalId" );
				}
			}
		}
	}

	private function importBatch( array $batch ) {
		$entities = $this->apiEntityLookup->getEntities( $batch );

		if ( !is_array( $entities ) ) {
			$this->logger->error( 'Failed to import batch' );

			return array();
		}

		$stashedEntities = array();

		foreach( $entities as $originalId => $entity ) {
			$stashedEntities[] = $entity->copy();

			if ( !$this->entityMappingStore->getLocalId( $originalId ) ) {
				try {
					$this->logger->info( "Creating $originalId" );

					$entityRevision = $this->createEntity( $entity );
					$localId = $entityRevision->getEntity()->getId()->getSerialization();
					$this->entityMappingStore->add( $originalId, $localId );
				} catch( \Exception $ex ) {
					$this->logger->error( "Failed to add $originalId" );
					$this->logger->error( $ex->getMessage() );
				}
			} else {
				$this->logger->info( "$originalId already imported" );
			}
		}

		return $stashedEntities;
	}

	private function createEntity( Entity $entity ) {
		$entity->setId( null );

		$entity->setStatements( new StatementList() );

		if ( $entity instanceof Item ) {
			$siteLinkList = $this->badgeItemUpdater->replaceBadges( $entity->getSiteLinkList() );
			$entity->setSiteLinkList( $siteLinkList );
		}

		return $this->entityStore->saveEntity(
			$entity,
			'Import entity',
			$this->importUser,
			EDIT_NEW
		);
	}

	private function getBadgeItems( array $entities ) {
		$badgeItems = array();

		foreach( $entities as $entity ) {
			if ( !$entity instanceof Item ) {
				continue;
			}

			foreach( $entity->getSiteLinks() as $siteLink ) {
				foreach( $siteLink->getBadges() as $badge ) {
					$badgeItems[] = $badge->getSerialization();
				}
			}
		}

		return $badgeItems;
	}

	private function getReferencedEntities( Entity $entity ) {
		$snaks = $entity->getStatements()->getAllSnaks();
		$entities = array();

		foreach( $snaks as $snak ) {
			$entities[] = $snak->getPropertyId()->getSerialization();

			if ( $snak instanceof PropertyValueSnak ) {
				$value = $snak->getDataValue();

				if ( $value instanceof EntityIdValue ) {
					$entities[] = $value->getEntityId()->getSerialization();
				}
			}
		}

		return array_unique( $entities );
	}

    private function importBadgeItems( array $entities ) {
        $badgeItems = $this->getBadgeItems( $entities );
        $this->importEntities( $badgeItems, false );
    }

}
