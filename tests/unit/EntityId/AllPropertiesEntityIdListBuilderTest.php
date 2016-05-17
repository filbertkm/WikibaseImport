<?php

namespace Wikibase\Import\Tests\EntityId;

use Wikibase\Import\EntityId\AllPropertiesEntityIdListBuilder;

/**
 * @group WikibaseImport
 */
class AllPropertiesEntityIdListBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityIds() {
		$propertyIds = [ 'P1', 'P9', 'P9000' ];

		$propertyIdLister = $this->getMockBuilder( 'Wikibase\Import\PropertyIdLister' )
			->disableOriginalConstructor()
			->getMock();

		$propertyIdLister->expects( $this->any() )
			->method( 'fetch' )
			->will( $this->returnValue( $propertyIds ) );

		$entityIdListBuilder = new AllPropertiesEntityIdListBuilder(
			$propertyIdLister,
			'https://www.wikidata.org/w/api.php'
		);

		$this->assertSame( $propertyIds, $entityIdListBuilder->getEntityIds( 'all' ) );
	}

}
