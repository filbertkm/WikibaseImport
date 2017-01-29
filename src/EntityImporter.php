<?php

namespace Wikibase\Import;

use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Import\Store\ImportedEntityMappingStore;

class EntityImporter {

	private $statementsImporter;

	private $apiEntityLookup;

	private $entitySaver;

	private $entityMappingStore;

	private $logger;

	private $idParser;

	private $batchSize;

	public function __construct(
		StatementsImporter $statementsImporter,
		ApiEntityLookup $apiEntityLookup,
		EntitySaver $entitySaver,
		ImportedEntityMappingStore $entityMappingStore,
		LoggerInterface $logger
	) {
		$this->statementsImporter = $statementsImporter;
		$this->apiEntityLookup = $apiEntityLookup;
		$this->entitySaver = $entitySaver;
		$this->entityMappingStore = $entityMappingStore;
		$this->logger = $logger;

		$this->idParser = new BasicEntityIdParser();
		$this->batchSize = 10;
	}

	public function importEntities( array $ids, $importStatements = true ) {
		$entityIdBatches = array_chunk( $ids, $this->batchSize );

		$stashedEntities = [];

		foreach ( $entityIdBatches as $entityIds ) {
			$entities = $this->apiEntityLookup->getEntities( $entityIds );

			if ( empty( $entities ) ) {
				$this->logger->error( "[EntityImporter] Failed to lookup entities" );
				continue;
			}

			$this->importBadgeItems( $entities );
			$stashedEntities = array_merge( $stashedEntities, $this->importBatch( $entities ) );
		}

		if ( $importStatements === true ) {
			$this->importStatementsOfEntities( $stashedEntities );
		}
	}

	/**
	 * @param array $stashedEntities
	 */
	private function importStatementsOfEntities( array $stashedEntities ) {
		foreach ( $stashedEntities as $entity ) {
			$entityId = $entity->getId();

			if ( !$entityId instanceof EntityId ) {
				$this->logger->error( 'Referenced entity does not have a valid entity id' );
				return;
			}

			if ( !$entity instanceof StatementListProvider ) {
				$this->logger->error( "Entity " . $entityId->getSerialization()
					. " is not a StatementListProvider" );
				continue;
			}

			$this->importStatements( $entity, $entityId );
		}
	}

	/**
	 * @param StatementListProvider $entity
	 * @param EntityId $entityId
	 */
	private function importStatements( StatementListProvider $entity, EntityId $entityId ) {
		$referencedEntities = $this->getReferencedEntities( $entity );
		$this->importEntities( $referencedEntities, false );

		$this->statementsImporter->importStatements( $entity, $entityId );
	}

	/**
	 * @param StatementListProvider $entity
	 * @return array
	 */
	private function getReferencedEntities( StatementListProvider $entity ) {
		$snaks = $entity->getStatements()->getAllSnaks();
		$entities = [];

		foreach ( $snaks as $snak ) {
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

	/**
	 * @param EntityDocument[] $entities
	 * @return EntityDocument[]
	 */
	private function importBatch( array $entities ) {
		$stashedEntities = [];

		foreach ( $entities as $originalId => $entity ) {
			$stashedEntities[] = $entity->copy();
			$originalEntityId = $this->idParser->parse( $originalId );

			if ( !$this->entityMappingStore->getLocalId( $originalEntityId ) ) {
				try {
					$this->logger->info( "Creating referenced entity: $originalId" );

					$entityRevision = $this->entitySaver->saveEntity( $entity );
					$localId = $entityRevision->getEntity()->getId();
					$this->entityMappingStore->add( $originalEntityId, $localId );
				} catch ( \Exception $ex ) {
					$this->logger->error( "Failed to create referenced entity: $originalId" );
					$this->logger->error( $ex->getMessage() );
				}
			}
		}

		return $stashedEntities;
	}

	private function getBadgeItems( array $entities ) {
		$badgeItems = [];

		foreach ( $entities as $entity ) {
			if ( !$entity instanceof Item ) {
				continue;
			}

			foreach ( $entity->getSiteLinkList() as $siteLink ) {
				foreach ( $siteLink->getBadges() as $badge ) {
					$badgeItems[] = $badge->getSerialization();
				}
			}
		}

		return $badgeItems;
	}

	private function importBadgeItems( array $entities ) {
		$badgeItems = $this->getBadgeItems( $entities );

		if ( !empty( $badgeItems ) ) {
			$this->importEntities( $badgeItems, false );
		}
	}

}
