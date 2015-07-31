<?php

namespace Wikibase\Import\Maintenance;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Import\ApiEntityLookup;
use Wikibase\Import\ImportedEntityStore;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Import\PropertyImporter;
use Wikibase\Repo\WikibaseRepo;

$IP = getenv( 'MW_INSTALL_PATH' );
if( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once( "$IP/maintenance/Maintenance.php" );

class ImportEntities extends \Maintenance {

	private $file;

	private $allProperties;

	private $apiUrl;

	public function __construct() {
		parent::__construct();

		$this->addOptions();
	}

	private function addOptions() {
		$this->addOption( 'api-url', 'Source wiki api url. Default is Wikidata.', false, true );
		$this->addOption( 'file', 'File with list of entity ids to import', false, true );
		$this->addOption( 'all-properties', 'Import all properties', false, true );
	}

	public function execute() {
		if ( !$this->extractOptions() ) {
			$this->maybeHelp( true );

			return;
		}

		$propertyImporter = $this->newPropertyImporter();

		if ( $this->allProperties ) {
			$propertyImporter->importAllProperties( $this->apiUrl );
		}

		if ( $this->file ) {
			$propertyImporter->importFromFile( $this->apiUrl, $this->file );
		}
	}

	private function extractOptions() {
		$this->apiUrl = $this->getOption( 'api-url', 'https://www.wikidata.org/w/api.php' );

		$this->file = $this->getOption( 'file' );
		$this->allProperties = $this->getOption( 'all-properties' );

		if ( $this->file === null && $this->allProperties === null ) {
			return false;
		}

		return true;
	}

	private function newPropertyImporter() {
		$serializerFactory = $this->newSerializerFactory();

		return new PropertyImporter(
			$serializerFactory->newEntitySerializer(),
			$serializerFactory->newStatementSerializer(),
			new PropertyIdLister(),
			new ApiEntityLookup( $this->newEntityDeserializer() ),
			WikibaseRepo::getDefaultInstance()->getEntityLookup( 'uncached' ),
			new ImportedEntityStore( wfGetLB() )
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
