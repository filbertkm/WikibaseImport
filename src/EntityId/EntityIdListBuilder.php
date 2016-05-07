<?php

namespace Wikibase\Import\EntityId;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface EntityIdListBuilder {

	/**
	 * @param string $input
	 *
	 * @return string[]
	 */
	public function getEntityIds( $input );

}
