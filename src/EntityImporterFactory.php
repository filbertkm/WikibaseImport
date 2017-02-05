<?php

namespace Wikibase\Import;

use DataValues\Serializers\DataValueSerializer;
use LoadBalancer;
use Psr\Log\LoggerInterface;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Import\Api\BadgeItemsLookup;
use Wikibase\Import\Api\MediaWikiApiClient;
use Wikibase\Import\Store\DBImportedEntityMappingStore;
use Wikibase\Import\Store\ImportedEntityMappingStore;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

class EntityImporterFactory {

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var ImportedEntityMappingStore
	 */
	private $importedEntityMappingStore;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @param EntityStore $entityStore
	 * @param LoadBalancer $loadBalancer
	 * @param LoggerInterface $logger
	 * @param string $apiUrl
	 */
	public function __construct(
		EntityStore $entityStore,
		LoadBalancer $loadBalancer,
		LoggerInterface $logger,
		$apiUrl
	) {
		$this->entityStore = $entityStore;
		$this->loadBalancer = $loadBalancer;
		$this->logger = $logger;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @return EntityImporter
	 */
	public function newEntityImporter() {
		$entityDuplicator = new EntityDuplicator(
			$this->newBadgeItemUpdater()
		);

		$entitySaver = new EntitySaver(
			$entityDuplicator,
			$this->entityStore,
			User::newFromId( 0 )
		);

		return new EntityImporter(
			$this->newStatementsImporter(),
			$this->getApiEntityLookup(),
			$entitySaver,
			$this->getImportedEntityMappingStore(),
			$this->getEntityIdParser(),
			$this->logger
		);
	}

	/**
	 * @return BadgeItemsImporter
	 */
	public function newBadgeItemsImporter() {
		$badgeItemsLookup = new BadgeItemsLookup(
			new MediaWikiApiClient( $this->apiUrl )
		);

		return new BadgeItemsImporter(
			$this->newEntityImporter(),
			$badgeItemsLookup,
			$this->getImportedEntityMappingStore(),
			$this->getEntityIdParser()
		);
	}

	/**
	 * @return ApiEntityLookup
	 */
	public function getApiEntityLookup() {
		return new ApiEntityLookup(
			$this->newEntityDeserializer(),
			new MediaWikiApiClient( $this->apiUrl ),
			$this->logger
		);
	}

	private function newBadgeItemUpdater() {
		return new BadgeItemUpdater( $this->getImportedEntityMappingStore() );
	}

	private function newStatementsImporter() {
		return new StatementsImporter(
			$this->newSerializerFactory()->newStatementSerializer(),
			new PagePropsStatementCountLookup( $this->loadBalancer ),
			$this->getImportedEntityMappingStore(),
			$this->logger
		);
	}

	private function getImportedEntityMappingStore() {
		if ( $this->importedEntityMappingStore === null ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$this->importedEntityMappingStore = new DBImportedEntityMappingStore(
				$this->loadBalancer,
				$wikibaseRepo->getEntityIdParser()
			);
		}

		return $this->importedEntityMappingStore;
	}

	private function newEntityDeserializer() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$deserializerFactory = $wikibaseRepo->getExternalFormatDeserializerFactory();

		return $deserializerFactory->newEntityDeserializer();
	}

	private function newSerializerFactory() {
		return new SerializerFactory(
			new DataValueSerializer()
		);
	}

	/**
	 * @return EntityIdParser
	 */
	private function getEntityIdParser() {
		return WikibaseRepo::getDefaultInstance()->getEntityIdParser();
	}
}

$maintClass = "Wikibase\Import\Maintenance\ImportEntities";
require_once RUN_MAINTENANCE_IF_MAIN;
