<?php

namespace Wikibase\Import\EntityId;

use Asparagus\QueryBuilder;
use Asparagus\QueryExecuter;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Import\QueryRunner;

class EntityIdListBuilderFactory {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var PropertyIdLister
	 */
	private $propertyIdLister;

	/**
	 * @var array
	 */
	private $queryPrefixes;

	/**
	 * @var string
	 */
	private $queryUrl;

	/**
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @param EntityIdParser $idParser
	 * @param PropertyIdLister $propertyIdLister
	 * @param array $queryPrefixes
	 * @param string $queryUrl
	 * @param string $apiUrl
	 */
	public function __construct(
		EntityIdParser $idParser, PropertyIdLister $propertyIdLister, array $queryPrefixes,
		$queryUrl, $apiUrl
	) {
		$this->idParser = $idParser;
		$this->propertyIdLister = $propertyIdLister;
		$this->queryPrefixes = $queryPrefixes;
		$this->queryUrl = $queryUrl;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @param string $mode
	 *
	 * @throws InvalidArgumentException
	 * @return EntityIdListBuilder
	 */
	public function newEntityIdListBuilder( $mode ) {
		switch ( $mode ) {
			case 'all-properties':
				return $this->newAllPropertiesEntityIdListBuilder();
			case 'file':
				return $this->newFileEntityIdListBuilder();
			case 'entity':
				return $this->newIndividualEntityIdListBuilder();
			case 'range':
				return $this->newRangeEntityIdListBuilder();
			case 'query':
				return $this->newQueryEntityIdListBuilder();
			default:
				throw new InvalidArgumentException( 'Unknown import mode: ' . $mode );
		}
	}

	private function newAllPropertiesEntityIdListBuilder() {
		return new AllPropertiesEntityIdListBuilder( $this->propertyIdLister, $this->apiUrl );
	}

	private function newFileEntityIdListBuilder() {
		return new FileEntityIdListBuilder();
	}

	private function newIndividualEntityIdListBuilder() {
		return new IndividualEntityIdListBuilder( $this->idParser );
	}

	private function newQueryEntityIdListBuilder() {
		return new QueryEntityIdListBuilder( $this->idParser, $this->newQueryRunner() );
	}

	private function newQueryRunner() {
		return new QueryRunner( new QueryBuilder( $this->queryPrefixes ),
			new QueryExecuter( $this->queryUrl ) );
	}

	private function newRangeEntityIdListBuilder() {
		return new RangeEntityIdListBuilder( $this->idParser );
	}

}
