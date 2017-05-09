<?php

namespace Wikibase\Import\Tests\Maintenance;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Wikibase\Import\EntityId\EntityIdListBuilder;
use Wikibase\Import\EntityId\EntityIdListBuilderFactory;
use Wikibase\Import\EntityImporter;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\Maintenance\ImportEntities;

/**
 * @group Database
 * @group WikibaseImport
 */
class ImportEntitiesTest extends \PHPUnit_Framework_TestCase {

	public function testInitServices() {
		$importEntities = new ImportEntities();
		$importEntities->initServices();

		// sanity check
		$this->assertTrue( true );
	}

	public function testExecute() {
		$name = 'extensions/Wikidata/extensions/Import/maintenance/importEntities.php';

		$options = [
			'entity' => 'Q14085872',
		    'memory-limit' => 'max'
		];

		$args = [];

		$importEntities = new ImportEntities();
		$importEntities->setServices(
			$this->getEntityIdListBuilderFactory(),
			$this->getEntityImporterFactory(),
			$this->getLogger()
		);

		$importEntities->loadParamsAndArgs( $name, $options, $args );
		$importEntities->execute();

		$this->assertTrue( true );
	}

	private function getEntityIdListBuilderFactory() {
		$entityIdListBuilder = $this->getMockBuilder( EntityIdListBuilder::class )
			->getMock();

		$entityIdListBuilder->expects( $this->any() )
			->method( 'getEntityIds' )
			->will( $this->returnValue( [ 'Q147' ] ) );

		// TODO: EntityIdListBuilderFactory really doesn't necessarily need to be mocked
		$entityIdListBuilderFactory = $this->getMockBuilder( EntityIdListBuilderFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdListBuilderFactory->expects( $this->once() )
			->method( 'newEntityIdListBuilder' )
			->will( $this->returnValue( $entityIdListBuilder ) );

		return $entityIdListBuilderFactory;
	}

	private function getEntityImporterFactory() {
		$entityImporter = $this->getMockBuilder( EntityImporter::class )
			->disableOriginalConstructor()
			->getMock();

		$entityImporter->expects( $this->any() )
			->method( 'importEntities' )
			->will( $this->returnValue( null ) );

		$entityImporterFactory = $this->getMockBuilder( EntityImporterFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityImporterFactory->expects( $this->once() )
			->method( 'newEntityImporter' )
			->will( $this->returnValue( $entityImporter ) );

		return $entityImporterFactory;
	}

	private function getLogger() {
		$logger = new Logger( 'wikibase-import' );
		$logger->pushHandler( new NullHandler() );

		return $logger;
	}

}
