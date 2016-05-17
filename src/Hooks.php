<?php

namespace Wikibase\Import;

use DatabaseUpdater;

/**
 * Extension hooks
 */
class Hooks {

	/**
	 * @param DatabaseUpdater $du
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$du->addExtensionTable( 'wbs_entity_mapping', __DIR__ . '/../sql/entity_mapping.sql' );

		return true;
	}

	/**
	 * @param array &$paths
	 * @return bool
	 */
	public static function onUnitTestsList( &$paths ) {
		$paths[] = __DIR__ . '/../tests/integration';
		$paths[] = __DIR__ . '/../tests/unit';

		return true;
	}

}
