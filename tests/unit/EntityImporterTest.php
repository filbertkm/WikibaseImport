<?php

namespace Wikibase\Import\Tests\Unit;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Import\EntityImporter;
use Wikibase\Import\EntitySaver;
use Wikibase\Import\StatementsImporter;
use Wikibase\Import\Store\BatchEntityLookup;
use Wikibase\Import\Store\ImportedEntityMappingStore;

class EntityImporterTest extends \PHPUnit_Framework_TestCase {

	public function testImportEntities() {
		$entityImporter = $this->getEntityImporter();
		$result = $entityImporter->importEntities( [ 'Q147' ], false );

		$this->assertSame( [ 'Q147' ], $result );
	}

	private function getEntityImporter() {
		$item = $this->makeItem();

		$entityLookup = $this->getMockBuilder( BatchEntityLookup::class )
			->getMock();

		$entityLookup->expects( $this->any() )
			->method( 'getEntities' )
			->with( [ 'Q147' ] )
			->willReturn( [ 'Q147' => $item ] );

		$entityMappingStore = $this->getMockBuilder( ImportedEntityMappingStore::class )
			->getMock();

		$entitySaver = $this->getMockBuilder( EntitySaver::class )
			->disableOriginalConstructor()
			->getMock();

		$statementsImporter = $this->getMockBuilder( StatementsImporter::class )
			->disableOriginalConstructor()
			->getMock();

		/*
		$statementsImporter->expects( $this->exactly( 2 ) )
			->method( 'importStatements' )
			->withConsecutive(
				[ $item, $item->getId() ]
			);
*/

		$entityImporter = new EntityImporter(
			$statementsImporter,
			$entityLookup,
			$entitySaver,
			$entityMappingStore,
			new BasicEntityIdParser(),
			$this->newLogger()
		);

		return $entityImporter;
	}

	private function makeItem() {
		$item = new Item( new ItemId( 'Q147' ) );
		$item->setLabel( 'en', 'Kitten' );

		$item->getStatements()->addNewStatement(
			new PropertyValueSnak(
				new PropertyId( 'P279' ),
				new EntityIdValue( new ItemId( 'Q146' ) )
			)
		);

		return $item;
	}

	private function newLogger() {
		$logger = new Logger( 'wikibase-import' );
		$logger->pushHandler( new NullHandler() );

		return $logger;
	}

}