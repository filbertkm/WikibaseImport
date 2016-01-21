<?php

namespace Wikibase\Import\Maintenance;

use Asparagus\QueryBuilder;
use Asparagus\QueryExecuter;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Wikibase\DataModel\Entity\ItemId;
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
		$this->addOption( 'query', 'Import items with property and entity id value', false, true );
		$this->addOption( 'range', 'Range of ids to import', false, true );
		$this->addOption( 'all-properties', 'Import all properties', false, false );
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

		if ( $this->range ) {
			$this->importRange( $this->range );
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

		$this->queryRunner = new QueryRunner(
			new QueryBuilder( $this->getConfig()->get( 'WBImportQueryPrefixes' ) ),
			new QueryExecuter( $this->getConfig()->get( 'WBImportQueryUrl' ) )
		);
	}

	private function newLogger() {
		$formatter = new LineFormatter( "[%datetime%]: %message%\n" );

		if ( $this->mQuiet ) {
			$handler = new NullHandler();
		} else {
			$handler = new StreamHandler( 'php://stdout' );
			$handler->setFormatter( $formatter );
		}

		$logger = new Logger( 'wikibase-import' );
		$logger->pushHandler( $handler );

		return $logger;
	}

	private function extractOptions() {
		$this->entity = $this->getOption( 'entity' );
		$this->file = $this->getOption( 'file' );
		$this->allProperties = $this->getOption( 'all-properties' );
		$this->query = $this->getOption( 'query' );
		$this->range = $this->getOption( 'range' );

		if ( $this->file === null
			&& $this->allProperties === null
			&& $this->entity === null
			&& $this->query === null
			&& $this->range === null
		) {
			return false;
		}

		return true;
	}

	private function importProperties() {
		$apiUrl = $this->getConfig()->get( 'WBImportSourceApi' );
		$ids = $this->propertyIdLister->fetch( $apiUrl );

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
		} catch ( \Exception $ex ) {
			$this->logger->error( 'Invalid entity ID' );
		}

		$this->entityImporter->importEntities( array( $entityId->getSerialization() ) );
	}

	private function importFromQuery( $query ) {
		$parts = explode( ':', $query );

		$propertyId = $this->idParser->parse( $parts[0] );
		$valueId = $this->idParser->parse( $parts[1] );

		$ids = $this->queryRunner->getPropertyEntityIdValueMatches( $propertyId, $valueId );
		$this->logger->info( 'Found ' . count( $ids ) . ' matches' );

		$this->entityImporter->importEntities( $ids );
	}

	private function importRange( $range ) {
		$parts = explode( ':', $range );

		$fromId = $this->idParser->parse( $parts[0] );
		$toId = $this->idParser->parse( $parts[1] );

		if ( !$fromId instanceof ItemId || !$toId instanceof ItemId ) {
			$this->logger->error( 'Invalid ItemId range specified', 1 );
		}

		$fromNumeric = $fromId->getNumericId();
		$toNumeric = $toId->getNumericId();

		$ids = array_map( function( $numericId ) {
			$id = new ItemId( 'Q' . $numericId );
			return $id->getSerialization();
		}, range( $fromNumeric, $toNumeric ) );

		$this->entityImporter->importEntities( $ids );
	}

}

$maintClass = "Wikibase\Import\Maintenance\ImportEntities";
require_once RUN_MAINTENANCE_IF_MAIN;
