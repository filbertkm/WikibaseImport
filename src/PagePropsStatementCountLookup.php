<?php

namespace Wikibase\Import;

use Exception;
use LoadBalancer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\WikibaseRepo;

class PagePropsStatementCountLookup implements StatementsCountLookup {

	private $loadBalancer;

	private $lookup;

	public function __construct( LoadBalancer $loadBalancer, EntityNamespaceLookup $lookup ) {
		$this->loadBalancer = $loadBalancer;
		$this->lookup = $lookup;
	}

	public function getStatementCount( EntityId $entityId ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$res = $db->selectRow(
			array( 'page_props', 'page' ),
			array( 'pp_value' ),
			array(
				'page_namespace' => $this->lookup->getEntityNamespace( $entityId->getEntityType() ),
				'page_title' => $entityId->getSerialization(),
				'pp_propname' => 'wb-claims'
			),
			__METHOD__,
			array(),
			array( 'page' => array( 'LEFT JOIN', 'page_id=pp_page' ) )
		);

		$this->loadBalancer->closeConnection( $db );

		if ( $res === false ) {
			throw new Exception( 'Could not find entity ' . $entityId->getSerialization() . ' in page_props!' );
		}

		return (int)$res->pp_value;
	}

	public function hasStatements( EntityId $entityId ) {
		$count = $this->getStatementCount( $entityId );

		return $count > 0;
	}

}
