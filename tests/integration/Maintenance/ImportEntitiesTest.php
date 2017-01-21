<?php

namespace Wikibase\Import\Tests;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group WikibaseImport
 */
class ApiEntityLookupIntegrationTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntity() {
		$apiEntityLookup = $this->getApiEntityLookup();
		$item = $apiEntityLookup->getEntity( new ItemId( 'Q60' ) );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $item );
		$this->assertSame(
			'New York City',
			$item->getFingerprint()->getLabel( 'en' )->getText(),
			'English label is New York City'
		);

		$statements = $item->getStatements()->getByPropertyId( new PropertyId( 'P17' ) );

		foreach ( $statements as $statement ) {
			$mainSnakValue = $statement->getMainSnak()->getDataValue();
			$this->assertEquals( 'Q30', $mainSnakValue->getEntityId()->getSerialization() );
		}
	}

	private function getApiEntityLookup() {
		$entityImporterFactory = new EntityImporterFactory(
			WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore(),
			wfGetLB(),
			$this->newLogger(),
			'https://www.wikidata.org/w/api.php'
		);

		return $entityImporterFactory->getApiEntityLookup();
	}

	private function newLogger() {
		$logger = new Logger( 'wikibase-import' );
		$logger->pushHandler( new NullHandler() );

		return $logger;
	}

}
