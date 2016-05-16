<?php

namespace Wikibase\Import;

use DataValues\Serializers\DataValueSerializer;
use LoadBalancer;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
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
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $apiUrl;

	private $entityImporter = null;

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
		if ( $this->entityImporter === null ) {
			$this->entityImporter = new EntityImporter(
				$this->newStatementsImporter(),
				$this->newBadgeItemUpdater(),
				$this->getApiEntityLookup(),
				$this->entityStore,
				new ImportedEntityMappingStore( $this->loadBalancer ),
				new PagePropsStatementCountLookup( $this->loadBalancer ),
				$this->logger
			);
		}

		return $this->entityImporter;
	}

	/**
	 * @return ApiEntityLookup
	 */
	public function getApiEntityLookup() {
		return new ApiEntityLookup(
			$this->newEntityDeserializer(),
			$this->logger,
			$this->apiUrl
		);
	}

	private function newBadgeItemUpdater() {
		return new BadgeItemUpdater( new ImportedEntityMappingStore( $this->loadBalancer ) );
	}

	private function newStatementsImporter() {
		return new StatementsImporter(
			$this->newSerializerFactory()->newStatementSerializer(),
			new ImportedEntityMappingStore( $this->loadBalancer ),
			$this->logger
		);
	}

	private function newEntityDeserializer() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$deserializerFactory = new DeserializerFactory(
			$wikibaseRepo->getDataValueDeserializer(),
			$wikibaseRepo->getEntityIdParser()
		);

		return $deserializerFactory->newEntityDeserializer();
	}

	private function newSerializerFactory() {
		return new SerializerFactory(
			new DataValueSerializer()
		);
	}
}

$maintClass = "Wikibase\Import\Maintenance\ImportEntities";
require_once RUN_MAINTENANCE_IF_MAIN;
