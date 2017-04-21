<?php

namespace Wikibase\Import\EntityId;

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
	 * @var QueryRunner
	 */
	private $queryRunner;

	/**
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @param EntityIdParser $idParser
	 * @param PropertyIdLister $propertyIdLister
	 * @param QueryRunner $queryRunner
	 * @param string $apiUrl
	 */
	public function __construct(
		EntityIdParser $idParser,
		PropertyIdLister $propertyIdLister,
		QueryRunner $queryRunner,
		$apiUrl
	) {
		$this->idParser = $idParser;
		$this->propertyIdLister = $propertyIdLister;
		$this->queryRunner = $queryRunner;
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
			case 'stdin':
				return $this->newStdinEntityIdListBuilder();
			default:
				throw new InvalidArgumentException( 'Unknown import mode: ' . $mode );
		}
	}

	private function newAllPropertiesEntityIdListBuilder() {
		return new AllPropertiesEntityIdListBuilder(
			$this->propertyIdLister,
			$this->apiUrl
		);
	}

	private function newFileEntityIdListBuilder() {
		return new FileEntityIdListBuilder();
	}

	private function newIndividualEntityIdListBuilder() {
		return new IndividualEntityIdListBuilder( $this->idParser );
	}

	private function newQueryEntityIdListBuilder() {
		return new QueryEntityIdListBuilder(
			$this->idParser,
			$this->queryRunner
		);
	}

	private function newRangeEntityIdListBuilder() {
		return new RangeEntityIdListBuilder( $this->idParser );
	}

	private function newStdinEntityIdListBuilder() {
		return new StdinEntityIdListBuilder();
	}

}
