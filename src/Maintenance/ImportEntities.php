<?php

namespace Wikibase\Import\Maintenance;

use Exception;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\Import\EntityId\EntityIdListBuilderFactory;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\LoggerFactory;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Repo\WikibaseRepo;

class ImportEntities extends \Maintenance {

	/**
	 * @var EntityIdListBuilderFactory
	 */
	private $entityIdListBuilderFactory;

	/**
	 * @var EntityImporterFactory
	 */
	private $entityImporterFactory;

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
		$this->addOption( 'all-properties', 'Import all properties', false, false );
	}

	public function setServices(
		EntityIdListBuilderFactory $entityIdListBuilderFactory,
		EntityImporterFactory $entityImporterFactory,
		LoggerInterface $logger
	) {
		$this->entityIdListBuilderFactory = $entityIdListBuilderFactory;
		$this->entityImporterFactory = $entityImporterFactory;
		$this->logger = $logger;
	}

	public function initServices() {
		// needed for EntityImporter
		if ( !isset( $this->logger ) ) {
			$this->logger = LoggerFactory::newLogger( 'wikibase-import', $this->mQuiet );
		}

		if ( !isset( $this->entityIdListBuilderFactory ) ) {
			$this->entityIdListBuilderFactory = $this->newEntityIdListBuilderFactory();
		}

		if ( !isset( $this->entityImporterFactory ) ) {
			$this->entityImporterFactory = $this->newEntityImporterFactory( $this->logger );
		}
	}

	public function execute() {
		$this->initServices();

		try {
			$this->doImport( $this->extractOptions() );
		} catch ( Exception $ex ) {
			$this->error( $ex->getMessage() );
		}

		$this->logger->info( 'Done' );
	}

	/**
	 * @param ImportOptions $importOptions
	 */
	private function doImport( ImportOptions $importOptions ) {
		foreach ( $importOptions as $importMode => $input ) {
			$this->logger->info( "Importing $importMode\n" );

			$entityIdListBuilder = $this->entityIdListBuilderFactory->newEntityIdListBuilder(
				$importMode
			);

			$ids = $entityIdListBuilder->getEntityIds( $input );
			$this->entityImporterFactory->newEntityImporter()->importEntities( $ids );
		}
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
	 * @return EntityIdListBuilderFactory
	 */
	private function newEntityIdListBuilderFactory() {
		$entityIdListBuilderFactory = new EntityIdListBuilderFactory(
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			new PropertyIdLister(),
			$this->getConfig()->get( 'WBImportQueryPrefixes' ),
			$this->getConfig()->get( 'WBImportQueryUrl' ),
			$this->getConfig()->get( 'WBImportSourceApi' )
		);

		return $entityIdListBuilderFactory;
	}

	/**
	 * @return EntityImporterFactory
	 */
	private function newEntityImporterFactory( LoggerInterface $logger ) {
		$entityImporterFactory = new EntityImporterFactory(
			WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore(),
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			$logger,
			$this->getConfig()->get( 'WBImportSourceApi' )
		);

		return $entityImporterFactory;
	}
}