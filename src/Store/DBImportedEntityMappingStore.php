<?php

namespace Wikibase\Import\Store;

use LoadBalancer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

class DBImportedEntityMappingStore implements ImportedEntityMappingStore {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( LoadBalancer $loadBalancer, EntityIdParser $entityIdParser ) {
		$this->loadBalancer = $loadBalancer;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param EntityId $originalId
	 * @param EntityId $localId
	 */
	public function add( EntityId $originalId, EntityId $localId ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		return $dbw->insert(
			'wbs_entity_mapping',
			array(
				'wbs_local_id' => $localId->getSerialization(),
				'wbs_original_id' => $originalId->getSerialization()
			),
			__METHOD__
		);
	}

	/**
	 * @param EntityId $originalId
	 *
	 * @return EntityId|null
	 */
	public function getLocalId( EntityId $originalId ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		$res = $dbw->selectRow(
			'wbs_entity_mapping',
			'wbs_local_id',
			array(
				'wbs_original_id' => $originalId->getSerialization()
			),
			__METHOD__
		);

		if ( $res !== false ) {
			return $this->entityIdParser->parse( $res->wbs_local_id );
		}

		return null;
	}

	/**
	 * @param EntityId $localId
	 *
	 * @return EntityId|null
	 */
	public function getOriginalId( EntityId $localId ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		$res = $dbw->selectRow(
			'wbs_entity_mapping',
			'wbs_original_id',
			array(
				'wbs_local_id' => $localId->getSerialization()
			),
			__METHOD__
		);

		if ( $res !== false ) {
			return $this->entityIdParser->parse( $res->wbs_original_id );
		}

		return null;
	}

}
