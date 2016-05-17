<?php

namespace Wikibase\Import\Tests\Store;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Import\Store\DBImportedEntityMappingStore;

/**
 * @group WikibaseImport
 * @group Database
 *
 * @covers Wikibase\Import\Store\DBImportedEntityMappingStore
 */
class DBImportedEntityMappingStoreTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wbs_entity_mapping';
	}

	public function testGetLocalId() {
		$store = $this->newDBImportedEntityMappingStore();

		$originalId = new ItemId( 'Q1' );
		$localId = new ItemId( 'Q100' );

		$store->add( $originalId, $localId );

		$this->assertEquals( $localId, $store->getLocalId( $originalId ) );
	}

	public function testGetOriginalId() {
		$store = $this->newDBImportedEntityMappingStore();

		$originalId = new PropertyId( 'P9' );
		$localId = new PropertyId( 'P900' );

		$store->add( $originalId, $localId );

		$this->assertEquals( $originalId, $store->getOriginalId( $localId ) );
	}

	private function newDBImportedEntityMappingStore() {
		return new DBImportedEntityMappingStore(
			wfGetLB(),
			new BasicEntityIdParser()
		);
	}

}
