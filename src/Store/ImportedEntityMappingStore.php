<?php

namespace Wikibase\Import\Store;

use Wikibase\DataModel\Entity\EntityId;

interface ImportedEntityMappingStore {

	/**
	 * @param EntityId $originalId
	 * @param EntityId $localId
	 */
	public function add( EntityId $originalId, EntityId $localId );

	/**
	 * @param EntityId $originalId
	 *
	 * @return EntityId|null
	 */
	public function getLocalId( EntityId $originalId );

	/**
	 * @param EntityId $localId
	 *
	 * @return EntityId|null
	 */
	public function getOriginalId( EntityId $localId );

}
