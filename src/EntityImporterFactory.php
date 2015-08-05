<?php

namespace Wikibase\Import;

use Config;
use DataValues\Serializers\DataValueSerializer;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

class EntityImporterFactory {

	private $config;

	private $logger;

	private $entityImporter = null;

	private $statementsImporter = null;

	private $badgeItemUpdater = null;

	public function __construct( Config $config, LoggerInterface $logger ) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function newEntityImporter() {
		if ( $this->entityImporter === null ) {
			$this->entityImporter = new EntityImporter(
				$this->newStatementsImporter(),
				$this->newBadgeItemUpdater(),
				new ApiEntityLookup( $this->newEntityDeserializer(), $this->logger ),
				WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore(),
				new ImportedEntityMappingStore( wfGetLB() ),
				$this->logger,
				$this->config->get( 'WBImportSourceApi' )
			);
		}

		return $this->entityImporter;
	}

	private function newBadgeItemUpdater() {
		return new BadgeItemUpdater( new ImportedEntityMappingStore( wfGetLB() ) );
	}

	private function newStatementsImporter() {
		return new StatementsImporter(
			$this->newSerializerFactory()->newStatementSerializer(),
			new ImportedEntityMappingStore( wfGetLB() ),
			$this->logger,
			$this->config->get( 'WBImportSourceApi' )
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
