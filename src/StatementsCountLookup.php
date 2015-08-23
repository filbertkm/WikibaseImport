<?php

namespace Wikibase\Import;

use Wikibase\DataModel\Entity\EntityId;

interface StatementsCountLookup {

	/**
	 * @param EntityId $entityId
	 *
	 * @return int
	 */
	public function getStatementCount( EntityId $entityId );

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	public function hasStatements( EntityId $entityId );

}
