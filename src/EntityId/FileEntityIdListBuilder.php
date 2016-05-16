<?php

namespace Wikibase\Import\EntityId;

use RuntimeException;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class FileEntityIdListBuilder implements EntityIdListBuilder {

	/**
	 * @param string $input
	 *
	 * @throws RuntimeException
	 * @return string[]
	 */
	public function getEntityIds( $input ) {
		if ( !is_readable( $input ) ) {
			throw new RuntimeException( 'File not found' );
		}

		$rows = file( $input );

		if ( !is_array( $rows ) ) {
			throw new RuntimeException( 'File is invalid' );
		}

		return array_map( 'trim', $rows );
	}

}
