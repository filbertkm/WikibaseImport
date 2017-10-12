<?php

namespace Wikibase\Import\EntityId;

use RuntimeException;

/**
 * @licence GNU GPL v2+
 * @author Lucas Werkmeister < lucas.werkmeister@wikimedia.de >
 */
class StdinEntityIdListBuilder implements EntityIdListBuilder {

	/**
	 * @param string $input (ignored)
	 *
	 * @throws RuntimeException
	 * @return string[]
	 */
	public function getEntityIds( $input ) {
		$entityIds = [];
		while ( $line = fgets( STDIN ) ) {
			$entityIds[] = trim( $line );
		}
		return $entityIds;
	}

}
