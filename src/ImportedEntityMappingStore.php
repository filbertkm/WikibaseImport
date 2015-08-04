<?php

namespace Wikibase\Import;

use LoadBalancer;

class ImportedEntityMappingStore {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public function add( $originalId, $localId ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		return $dbw->insert(
			'wbs_entity_mapping',
			array(
				'wbs_local_id' => $localId,
				'wbs_original_id' => $originalId
			),
			__METHOD__
		);
	}

	public function getLocalId( $originalId ) {
		if ( !is_string( $originalId ) ) {
			throw new \InvalidArgumentException( '$originalId must be a string' );
		}

		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		$res = $dbw->selectRow(
			'wbs_entity_mapping',
			'wbs_local_id',
			array(
				'wbs_original_id' => $originalId
			),
			__METHOD__
		);

		if ( $res !== false ) {
			return $res->wbs_local_id;
		}

		return null;
	}

	public function getOriginalId( $localId ) {
		if ( !is_string( $localId ) ) {
			throw new \InvalidArgumentException( '$localId must be a string' );
		}

		$dbw = $this->loadBalaance->getConnection( DB_MASTER );

		$res = $dbw->selectRow(
			'wbs_entity_mapping',
			'wbs_original_id',
			array(
				'wbs_local_id' => $localId
			),
			__METHOD__
		);

		if ( $res !== false ) {
			return $res->wbs_original_id;
		}

		return null;
	}

	public function remove() {

	}

}
