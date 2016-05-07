<?php

namespace Wikibase\Import\EntityId;

use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class IndividualEntityIdListBuilder implements EntityIdListBuilder {

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
	 * @return string[]
	 */
	public function getEntityIds( $input ) {
		$entityId = $this->entityIdParser->parse( $input );

		return array( $entityId->getSerialization() );
	}

}
