<?php

namespace Wikibase\Import;

use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\EntityId\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Store\WikiPageEntityStore;

class EntityImporter {

	private $statementsImporter;

	private $badgeItemUpdater;

	private $apiEntityLookup;

	private $entityStore;

	private $entityMappingStore;

	private $apiUrl;

	private $importUser;

	private $batchSize;

	public function __construct(
		StatementsImporter $statementsImporter,
		BadgeItemUpdater $badgeItemUpdater,
		ApiEntityLookup $apiEntityLookup,
		WikiPageEntityStore $entityStore,
		ImportedEntityMappingStore $entityMappingStore,
		$apiUrl
	) {
		$this->statementsImporter = $statementsImporter;
		$this->badgeItemUpdater = $badgeItemUpdater;
		$this->apiEntityLookup = $apiEntityLookup;
		$this->entityStore = $entityStore;
		$this->entityMappingStore = $entityMappingStore;
		$this->apiUrl = $apiUrl;

		$this->importUser = User::newFromId( 0 );

		$this->batchSize = 10;
	}

	public function importIds( array $ids, $importStatements = true ) {
		$batches = array_chunk( $ids, $this->batchSize );

		$stashedEntities = array();

		foreach( $batches as $batch ) {
			$entities = $this->apiEntityLookup->getEntities( $batch, $this->apiUrl );

			$badgeItems = $this->getBadgeItems( $entities );
			$this->importIds( $badgeItems, false );

			$stashedEntities = array_merge( $stashedEntities, $this->importBatch( $batch ) );
		}

		if ( $importStatements === true ) {
			foreach( $stashedEntities as $entity ) {
				$referencedEntities = $this->getReferencedEntities( $entity );
				$this->importIds( $referencedEntities, false );
				$this->statementsImporter->importStatements( $entity );
			}
		}
	}

	private function importBatch( array $batch ) {
		$entities = $this->apiEntityLookup->getEntities( $batch, $this->apiUrl );

		$stashedEntities = array();

		foreach( $entities as $originalId => $entity ) {
			$stashedEntities[] = $entity->copy();

			echo "importing $originalId\n";

			if ( !$this->entityMappingStore->getLocalId( $originalId ) ) {
				try {
					echo "creating $originalId\n";
					$entityRevision = $this->createEntity( $entity );
					$localId = $entityRevision->getEntity()->getId()->getSerialization();
					$this->entityMappingStore->add( $originalId, $localId );
				} catch( \Exception $ex ) {
					echo "failed to add $originalId\n";
					echo $ex->getMessage();
					echo "\n";
					// omg!
				}
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

}
