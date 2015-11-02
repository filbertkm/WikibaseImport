<?php

namespace Wikibase\Import\Tests;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use RequestContext;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Import\EntityImporterFactory;

class ApiEntityLookupIntegrationTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntity() {
		$apiEntityLookup = $this->getApiEntityLookup();
		$item = $apiEntityLookup->getEntity( new ItemId( 'Q64' ) );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $item );
		$this->assertSame( 'Berlin', $item->getLabel( 'en' ), 'English label is Berlin' );

		$statements = $item->getStatements()->getByPropertyId( new PropertyId( 'P17' ) );

		foreach ( $statements as $statement ) {
			$mainSnakValue = $statement->getMainSnak()->getDataValue();
			$this->assertEquals( 'Q183', $mainSnakValue->getEntityId()->getSerialization() );
		}
	}

	private function getApiEntityLookup() {
		$entityImporterFactory = new EntityImporterFactory(
			RequestContext::getMain()->getConfig(),
			$this->newLogger()
		);

		return $entityImporterFactory->getApiEntityLookup();
	}

	private function newLogger() {
		$logger = new Logger( 'wikibase-import' );
		$logger->pushHandler( new NullHandler() );

		return $logger;
	}

}
