<?php

namespace Wikibase\Import;

use DataValues\Serializers\DataValueSerializer;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

class EntityImporterFactory {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $apiUrl;

	private $entityImporter = null;

	private $statementsImporter = null;

	private $badgeItemUpdater = null;

	/**
	 * @param LoggerInterface $logger
	 * @param string $apiUrl
	 */
	public function __construct( LoggerInterface $logger, $apiUrl ) {
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
				WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore(),
				new ImportedEntityMappingStore( wfGetLB() ),
				new PagePropsStatementCountLookup( wfGetLB() ),
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
		return new BadgeItemUpdater( new ImportedEntityMappingStore( wfGetLB() ) );
	}

	private function newStatementsImporter() {
		return new StatementsImporter(
			$this->newSerializerFactory()->newStatementSerializer(),
			new ImportedEntityMappingStore( wfGetLB() ),
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
