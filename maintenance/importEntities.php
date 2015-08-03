<?php

namespace Wikibase\Import\Maintenance;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Import\ApiEntityLookup;
use Wikibase\Import\EntityImporter;
use Wikibase\Import\ImportedEntityMappingStore;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Repo\WikibaseRepo;

$IP = getenv( 'MW_INSTALL_PATH' );
if( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once( "$IP/maintenance/Maintenance.php" );

class ImportEntities extends \Maintenance {

	private $entity;

	private $file;

	private $allProperties;

	private $apiUrl;

	public function __construct() {
		parent::__construct();

		$this->addOptions();
	}

	private function addOptions() {
		$this->addOption( 'file', 'File with list of entity ids to import', false, true );
		$this->addOption( 'entity', 'ID of entity to import', false, true );
		$this->addOption( 'all-properties', 'Import all properties', false, true );
	}

	public function execute() {
		if ( $this->extractOptions() === false ) {
			$this->maybeHelp( true );

			return;
		}

		$entityImporter = $this->newEntityImporter();

		if ( $this->allProperties ) {
			$propertyLister = new PropertyLister();
			$ids = $propertyIdLister->fetch( $this->apiUrl );

			$entityImporter->importIds( $ids );
		}

		if ( $this->file ) {
			$ids = array_map( 'trim', file( $file ) );
			$entityImporter->importIds( $ids );
		}

		if ( $this->entity ) {
			$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

			try {
				$id = $idParser->parse( $this->entity );
				$entityImporter->importIds( array( $id ) );
			} catch ( \Exception $ex ) {
				$this->output( 'Invalid entity ID' );
			}
		}
	}

	private function extractOptions() {
		$this->entity = $this->getOption( 'entity' );
		$this->file = $this->getOption( 'file' );
		$this->allProperties = $this->getOption( 'all-properties' );

		if ( $this->file === null && $this->allProperties === null && $this->entity === null ) {
			return false;
		}

		return true;
	}

	private function newEntityImporter() {
		$wbRepo = WikibaseRepo::getDefaultInstance();

		return new EntityImporter(
			$this->newSerializerFactory()->newStatementSerializer(),
			new ApiEntityLookup( $this->newEntityDeserializer() ),
			$wbRepo->getEntityRevisionLookup( 'uncached' ),
			$wbRepo->getStore()->getEntityStore(),
			new ImportedEntityMappingStore( wfGetLB() ),
			$this->getConfig()->get( 'WBImportSourceApi' )
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
