<?php

namespace Wikibase\Import\EntityId;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Import\QueryRunner;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class QueryEntityIdListBuilder implements EntityIdListBuilder {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var QueryRunner
	 */
	private $queryRunner;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param QueryRunner $queryRunner
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		QueryRunner $queryRunner
	) {
		$this->entityIdParser = $entityIdParser;
		$this->queryRunner = $queryRunner;
	}

	/**
	 * @param string $input
	 *
	 * @return string[]
	 */
	public function getEntityIds( $input ) {
		$parts = explode( ':', $input );

		$propertyId = new PropertyId( $parts[0] );
		$valueId = $this->entityIdParser->parse( $parts[1] );

		return $this->queryRunner->getPropertyEntityIdValueMatches( $propertyId, $valueId );
	}

}
