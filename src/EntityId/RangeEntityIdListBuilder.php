<?php

namespace Wikibase\Import\EntityId;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RangeEntityIdListBuilder implements EntityIdListBuilder {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param string $input
	 *
	 * @return EntityId[]
	 */
	public function getEntityIds( $input ) {
		$parts = explode( ':', $input );

		$fromId = $this->entityIdParser->parse( $parts[0] );
		$toId = $this->entityIdParser->parse( $parts[1] );

		// @todo make this work with other types of entities!
		if ( !$fromId instanceof ItemId || !$toId instanceof ItemId ) {
			throw new RuntimeException( 'Invalid ItemId range specified' );
		}

		$fromNumeric = $fromId->getNumericId();
		$toNumeric = $toId->getNumericId();

		$ids = array_map( function( $numericId ) {
			$id = new ItemId( 'Q' . $numericId );
			return $id->getSerialization();
		}, range( $fromNumeric, $toNumeric ) );

		return $ids;
	}

}
