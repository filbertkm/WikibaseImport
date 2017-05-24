<?php

namespace Wikibase\Import;

use Psr\Log\LoggerInterface;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Import\Store\ImportedEntityMappingStore;
use Wikibase\Lib\Store\EntityStore;

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
		EntityStore $entityStore,
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
		$this->importUser = User::newFromSession();
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
				$this->logger->error( 'Failed to retrieve items for batch' );
			}

			$stashedEntities = array_merge( $stashedEntities, $this->importBatch( $batch ) );
		}

		if ( $importStatements === true ) {
			foreach( $stashedEntities as $entity ) {
				$referencedEntities = $this->getReferencedEntities( $entity );
				$this->importEntities( $referencedEntities, false );

				$localId = $this->entityMappingStore->getLocalId( $entity->getId() );

				if ( $localId && !$this->statementsCountLookup->hasStatements( $localId ) ) {
					$this->statementsImporter->importStatements( $entity );
				} else {
					$this->logger->info(
						'Statements already imported for ' . $entity->getId()->getSerialization()
					);
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
			$originalEntityId = $this->idParser->parse( $originalId );

			if ( !$this->entityMappingStore->getLocalId( $originalEntityId ) ) {
				try {
					$this->logger->info( "Creating $originalId" );

					$entityRevision = $this->createEntity( $entity );
					$localId = $entityRevision->getEntity()->getId();
					$this->entityMappingStore->add( $originalEntityId, $localId );
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

	private function createEntity( EntityDocument $entity ) {
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

	private function getReferencedEntities( EntityDocument $entity ) {
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
