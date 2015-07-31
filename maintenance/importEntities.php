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

	public function __construct() {
		parent::__construct();

		$this->addOption( 'file', "File with list of entity ids to import.", false, true );
	}

	public function execute() {
		$file = $this->getOption( 'file', null );

		$serializerFactory = $this->newSerializerFactory();

		$propertyImporter = new PropertyImporter(
			$serializerFactory->newEntitySerializer(),
			$serializerFactory->newStatementSerializer(),
			new PropertyIdLister(),
			new ApiEntityLookup( $this->newEntityDeserializer() ),
			WikibaseRepo::getDefaultInstance()->getEntityLookup( 'uncached' ),
			new ImportedEntityStore( wfGetLB() )
		);

		$propertyImporter->import( 'https://www.wikidata.org/w/api.php', $file );
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
