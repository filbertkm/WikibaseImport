<?php

namespace Wikibase\Import\Tests\EntityId;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Import\EntityId\RangeEntityIdListBuilder;

/**
 * @group WikibaseImport
 *
 * @covers Wikibase\Import\EntityId\RangeEntityIdListBuilder
 */
class RangeEntityIdListBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityIds() {
		$entityIdListBuilder = new RangeEntityIdListBuilder( new BasicEntityIdParser() );

		$this->assertSame( [ 'Q1', 'Q2', 'Q3' ], $entityIdListBuilder->getEntityIds( 'Q1:Q3' ) );
	}

	public function testGetEntityIdsInvalidRange() {
		$entityIdListBuilder = new RangeEntityIdListBuilder(
			new BasicEntityIdParser()
		);

		$this->setExpectedException( 'RuntimeException' );

		$entityIdListBuilder->getEntityIds( 'kittens' );
	}

}
