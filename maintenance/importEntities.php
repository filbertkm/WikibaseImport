<?php

namespace Wikibase\Import\Maintenance;

use Exception;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\Import\Console\ImportOptions;
use Wikibase\Import\EntityId\EntityIdListBuilder;
use Wikibase\Import\EntityId\EntityIdListBuilderFactory;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\LoggerFactory;
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

	/**
	 * @var ImportOptions
	 */
	private $importOptions;

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
		$this->logger = LoggerFactory::newLogger( 'wikibase-import', $this->mQuiet );
		$this->importOptions = $this->extractOptions();

		try {
			foreach ( $this->importOptions as $importMode => $input ) {
				$this->output( "Importing $importMode\n" );

				$entityIdListBuilder = $this->newEntityIdListBuilder( $importMode );

				$ids = $entityIdListBuilder->getEntityIds( $input );

				$entityImporter = $this->newEntityImporter();
				$entityImporter->importEntities( $ids );
			}
		}
		catch ( Exception $ex ) {
			$this->error( $ex->getMessage() );
		}

		$this->logger->info( 'Done' );
	}

	/**
	 * @inheritdoc
	 */
	protected function error( $err, $die = 0 ) {
		$err = "\033[31mERROR:\033[0m $err";

		$this->logger->error( $err );
		$this->maybeHelp( true );
	}

	/**
	 * @return array
	 */
	private function getValidOptions() {
		return [ 'entity', 'file', 'all-properties', 'query', 'range' ];
	}

	/**
	 * @return ImportOptions
	 * @throws RuntimeException
	 */
	private function extractOptions() {
		$options = [];

		foreach ( $this->getValidOptions() as $optionName ) {
			if ( $this->hasOption( $optionName ) ) {
				$options[$optionName] = $this->getOptionValue( $optionName );
			}
		}

		if ( empty( $options ) ) {
			throw new RuntimeException( 'No valid import mode option provided' );
		}

		return new ImportOptions( $options );
	}

	/**
	 * @param string $optionName
	 * @return mixed
	 */
	private function getOptionValue( $optionName ) {
		if ( $optionName === 'all-properties' ) {
			return 'all-properties';
		} else {
			return $this->getOption( $optionName );
		}
	}

	/**
	 * @param string $importMode
	 * @return EntityIdListBuilder
	 */
	private function newEntityIdListBuilder( $importMode ) {
		$entityIdListBuilderFactory = new EntityIdListBuilderFactory(
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			new PropertyIdLister(),
			$this->getConfig()->get( 'WBImportQueryPrefixes' ),
			$this->getConfig()->get( 'WBImportQueryUrl' ),
			$this->getConfig()->get( 'WBImportSourceApi' )
		);

		return $entityIdListBuilderFactory->newEntityIdListBuilder( $importMode );
	}

	private function newEntityImporter() {
		$entityImporterFactory = new EntityImporterFactory(
			WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore(),
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			$this->logger,
			$this->getConfig()->get( 'WBImportSourceApi' )
		);

		return $entityImporterFactory->newEntityImporter();
	}
}

$maintClass = ImportEntities::class;
require_once RUN_MAINTENANCE_IF_MAIN;
