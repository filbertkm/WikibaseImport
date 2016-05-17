<?php

namespace Wikibase\Import\Tests\EntityId;

use Wikibase\Import\EntityId\FileEntityIdListBuilder;

/**
 * @group WikibaseImport
 *
 * @covers Wikibase\Import\EntityId\FileEntityIdListBuilder
 */
class FileEntityIdListBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityIds() {
		$entityIdListBuilder = new FileEntityIdListBuilder();

		$this->assertSame(
			[ 'Q146', 'Q147' ],
			$entityIdListBuilder->getEntityIds( __DIR__ . '/../../data/entities.txt' )
		);
	}

	public function testGetEntityIdsInvalidFile() {
		$entityIdListBuilder = new FileEntityIdListBuilder();

		$this->setExpectedException( 'RuntimeException' );
		$entityIdListBuilder->getEntityIds( uniqid() . '.txt' );
	}

}
