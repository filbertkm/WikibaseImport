<?php

namespace Wikibase\Import;

use DatabaseUpdater;

/**
 * Extension hooks
 */
class Hooks {

	public static function registerExtension() {

		return true;
	}

	/**
	 * @param DatabaseUpdater $du
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$du->addExtensionTable( 'wbs_entity_mapping', __DIR__ . '/../sql/entity_mapping.sql' );

		return true;
	}

}
