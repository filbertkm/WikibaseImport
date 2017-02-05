<?php

namespace Wikibase\Import\Store;

use Wikibase\DataModel\Services\Lookup\EntityLookup;

interface BatchEntityLookup extends EntityLookup {

	/**
	 * @param string[] $ids
	 *
	 * @throws RuntimeException
	 * @return EntityDocument[]
	 */
	public function getEntities( array $ids );

}
