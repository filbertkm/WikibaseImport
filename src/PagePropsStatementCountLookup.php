<?php

namespace Wikibase\Import;

use LoadBalancer;
use Wikibase\DataModel\Entity\EntityId;

class PagePropsStatementCountLookup implements StatementsCountLookup {

	private $loadBalancer;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public function getStatementCount( EntityId $entityId ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$res = $db->selectRow(
			array( 'page_props', 'page' ),
			array( 'pp_value' ),
			array(
				'page_namespace' => 0,
				'page_title' => $entityId->getSerialization(),
				'pp_propname' => 'wb-claims'
			),
			__METHOD__,
			array(),
			array( 'page' => array( 'LEFT JOIN', 'page_id=pp_page' ) )
		);

		$this->loadBalancer->closeConnection( $db );

		if ( $res === false ) {
			return 0;
		}

		return (int)$res->pp_value;
	}

	public function hasStatements( EntityId $entityId ) {
		$count = $this->getStatementCount( $entityId );

		return $count > 0;
	}

}
