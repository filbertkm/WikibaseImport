<?php

namespace Wikibase\Import\Maintenance;

use Asparagus\QueryBuilder;
use Asparagus\QueryExecuter;
use Exception;
use Psr\Log\LoggerInterface;
use Wikibase\Import\Console\ImportOptions;
use Wikibase\Import\EntityId\EntityIdListBuilderFactory;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\LoggerFactory;
use Wikibase\Import\QueryRunner;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Repo\WikibaseRepo;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class ImportEntities extends \Maintenance {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct() {
		parent::__construct();

		$this->addOptions();
	}

	private function addOptions() {
		$this->addOption( 'file', 'File with list of entity ids to import', false, true );
		$this->addOption( 'entity', 'ID of entity to import', false, true );
		$this->addOption( 'query', 'Import items with property and entity id value', false, true );
		$this->addOption( 'range', 'Range of ids to import', false, true );
		$this->addOption( 'stdin', 'Read entity IDs to import from standard input', false, false );
		$this->addOption( 'all-properties', 'Import all properties', false, false );
	}

	public function execute() {
		$this->logger = LoggerFactory::newLogger( 'wikibase-import', $this->mQuiet );

		$importOptions = $this->extractOptions();

		$entityIdListBuilderFactory = $this->newEntityIdListBuilderFactory();

		foreach ( $this->getValidOptions() as $option ) {
			if ( $importOptions->hasOption( $option ) ) {
				$entityIdListBuilder = $entityIdListBuilderFactory->newEntityIdListBuilder(
					$option
				);

				if ( $option === 'all-properties' ) {
					$input = 'all-properties';
				} else {
					$input = $importOptions->getOption( $option );
				}

				break;
			}
		}

		if ( !isset( $entityIdListBuilder ) ) {
			$this->logger->error( 'ERROR: No valid import option was provided' );

			return;
		} else {
			try {
				$ids = $entityIdListBuilder->getEntityIds( $input );

				$entityImporter = $this->newEntityImporter();
				$entityImporter->importEntities( $ids );
			} catch ( Exception $ex ) {
				$this->logger->error( $ex->getMessage() );
			}

			$this->logger->info( 'Done' );
		}
	}

	private function extractOptions() {
		$options = [];

		foreach ( $this->getValidOptions() as $optionName ) {
			$options[$optionName] = $this->getOption( $optionName );
		}

		if ( empty( $options ) ) {
			$this->maybeHelp( true );
		}

		return new ImportOptions( $options );
	}

	private function getValidOptions() {
		return [ 'entity', 'file', 'all-properties', 'query', 'range', 'stdin' ];
	}

	private function newEntityIdListBuilderFactory() {
		$queryRunner = new QueryRunner(
			new QueryBuilder( $this->getConfig()->get( 'WBImportQueryPrefixes' ) ),
			new QueryExecuter( $this->getConfig()->get( 'WBImportQueryUrl' ) )
		);

		return new EntityIdListBuilderFactory(
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			new PropertyIdLister(),
			$queryRunner,
			$this->getConfig()->get( 'WBImportSourceApi' )
		);
	}

	private function newEntityImporter() {
		$entityImporterFactory = new EntityImporterFactory(
			WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore(),
			wfGetLB(),
			$this->logger,
			$this->getConfig()->get( 'WBImportSourceApi' )
		);

		return $entityImporterFactory->newEntityImporter();
	}

}

$maintClass = "Wikibase\Import\Maintenance\ImportEntities";
require_once RUN_MAINTENANCE_IF_MAIN;
