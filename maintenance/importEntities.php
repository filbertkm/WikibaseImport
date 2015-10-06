<?php

namespace Wikibase\Import\Maintenance;

use MediaWiki\Logger\LoggerFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Wikibase\Import\EntityImporter;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Import\QueryRunner;
use Wikibase\Repo\WikibaseRepo;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class ImportEntities extends \Maintenance {

	private $logger;

	private $entityImporter;

	private $propertyIdLister;

	private $idParser;

	private $queryRunner;

	private $entity;

	private $file;

	private $allProperties;

	public function __construct() {
		parent::__construct();

		$this->addOptions();
	}

	private function addOptions() {
		$this->addOption( 'file', 'File with list of entity ids to import', false, true );
		$this->addOption( 'entity', 'ID of entity to import', false, true );
		$this->addOption( 'query', 'Import items with specified property and entity id value', false, true );
		$this->addOption( 'all-properties', 'Import all properties', false, true );
	}

	public function execute() {
		if ( $this->extractOptions() === false ) {
			$this->maybeHelp( true );

			return;
		}

		$this->initServices();

		if ( $this->allProperties ) {
			$this->importProperties();
		}

		if ( $this->file ) {
			$this->importEntitiesFromFile( $this->file );
		}

		if ( $this->entity ) {
			$this->importEntity( $this->entity );
		}

		if ( $this->query ) {
			$this->importFromQuery( $this->query );
		}

		$this->logger->info( 'Done' );
	}

	private function initServices() {
		$this->logger = $this->newLogger();

		$entityImporterFactory = new EntityImporterFactory( $this->getConfig(), $this->logger );
		$this->entityImporter = $entityImporterFactory->newEntityImporter();

		$this->propertyIdLister = new PropertyIdLister();
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$this->queryRunner = new QueryRunner( $this->getConfig() );
	}

	private function newLogger() {
		$logger = new Logger( 'wb-import-console' );
		$logger->pushHandler(
			new StreamHandler( 'php://stdout' )
		);

		return $logger;
	}

	private function extractOptions() {
		$this->entity = $this->getOption( 'entity' );
		$this->file = $this->getOption( 'file' );
		$this->allProperties = $this->getOption( 'all-properties' );
		$this->query = $this->getOption( 'query' );

		if ( $this->file === null
			&& $this->allProperties === null
			&& $this->entity === null
			&& $this->query === null
		) {
			return false;
		}

		return true;
	}

	private function importProperties() {
		$ids = $this->propertyIdLister->fetch();

		$this->entityImporter->importEntities( $ids );
	}

	private function importEntitiesFromFile( $filename ) {
		$rows = file( $filename );

		if ( !is_array( $rows ) ) {
			$this->logger->error( 'File is invalid.' );
		}

		$ids = array_map( 'trim', $rows );
		$this->entityImporter->importEntities( $ids );
	}

	private function importEntity( $entityIdString ) {
		try {
			$entityId = $this->idParser->parse( $entityIdString );

			$this->entityImporter->importEntities( array( $entityId->getSerialization() ) );
		} catch ( \Exception $ex ) {
			$this->logger->error( 'Invalid entity ID' );
		}
	}

	private function importFromQuery( $query ) {
		$parts = explode( ',', $query );

		$propertyId = $this->idParser->parse( $parts[0] );
		$valueId = $this->idParser->parse( $parts[1] );

		$ids = $this->queryRunner->getPropertyEntityIdValueMatches( $propertyId, $valueId );
		$this->logger->info( 'Found ' . count( $ids ) . ' matches' );

		$this->entityImporter->importEntities( $ids );
	}

}

$maintClass = "Wikibase\Import\Maintenance\ImportEntities";
require_once RUN_MAINTENANCE_IF_MAIN;
