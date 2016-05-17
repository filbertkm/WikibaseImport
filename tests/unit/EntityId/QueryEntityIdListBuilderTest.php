<?php

namespace Wikibase\Import\Tests\EntityId;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Import\EntityId\QueryEntityIdListBuilder;

/**
 * @group WikibaseImport
 *
 * @covers Wikibase\Import\EntityId\QueryEntityIdListBuilder
 */
class QueryEntityIdListBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityIds() {
		$queryResult = [ 'Q2', 'Q20' ];

		$entityIdListBuilder = new QueryEntityIdListBuilder(
			new BasicEntityIdParser(),
			$this->getQueryRunner( $queryResult )
		);

		$this->assertSame( $queryResult, $entityIdListBuilder->getEntityIds( 'P31:Q146' ) );
	}

	public function testGetEntityIdsInvalidQuery() {
		$entityIdListBuilder = new QueryEntityIdListBuilder(
			new BasicEntityIdParser(),
			$this->getQueryRunner( [] )
		);

		$this->setExpectedException( 'InvalidArgumentException' );

		$entityIdListBuilder->getEntityIds( 'kittens' );
	}

	private function getQueryRunner( $queryResult ) {
		$queryRunner = $this->getMockBuilder( 'Wikibase\Import\QueryRunner' )
			->disableOriginalConstructor()
			->getMock();

		$queryRunner->expects( $this->any() )
			->method( 'getPropertyEntityIdValueMatches' )
			->will( $this->returnValue( $queryResult ) );

		return $queryRunner;
	}

}
