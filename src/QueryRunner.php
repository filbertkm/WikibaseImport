<?php

namespace Wikibase\Import;

use Asparagus\QueryBuilder;
use Asparagus\QueryExecuter;
use Config;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

class QueryRunner {

	private $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function getPropertyEntityIdValueMatches( PropertyId $propertyId, EntityId $valueId ) {
		$propertyText = $propertyId->getSerialization();
		$valueText = $valueId->getSerialization();

		$queryBuilder = new QueryBuilder( $this->config->get( 'WBImportQueryPrefixes' ) );

		$queryBuilder->select( '?id' )
			->where( "?id", "wdt:$propertyText", "wd:$valueText" );

		$queryExecuter = new QueryExecuter( $this->config->get( 'WBImportQueryUrl' ) );

		$results = $queryExecuter->execute( $queryBuilder->getSPARQL() );

		if ( !is_array( $results ) ) {
			throw new QueryException( 'Query execution failed.' );
		}

		return $this->parseResults( $results );
	}

	private function parseResults( array $results ) {
		$pattern = "/^http:\/\/www.wikidata.org\/entity\/([PQ]\d+)$/";
		$ids = array();

		foreach ( $results['bindings'] as $result ) {
			preg_match( $pattern, $result['id']['value'], $matches );

			if ( isset( $matches[1] ) ) {
				$ids[] = $matches[1];
			}
		}

		return $ids;
	}

}
