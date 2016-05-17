<?php

namespace Wikibase\Import\Tests\EntityId;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Import\EntityId\IndividualEntityIdListBuilder;

/**
 * @group WikibaseImport
 *
 * @covers Wikibase\Import\EntityId\IndividualEntityIdListBuilder
 */
class IndividualEntityIdListBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityIds() {
		$entityIdListBuilder = new IndividualEntityIdListBuilder( new BasicEntityIdParser() );

		$this->assertSame( [ 'Q147' ], $entityIdListBuilder->getEntityIds( 'Q147' ) );
	}

}
