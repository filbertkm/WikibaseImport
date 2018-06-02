<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseImport', __DIR__ . '/extension.json' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikibaseImport'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikibaseImportAlias'] = __DIR__ . '/WikibaseImport.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for WikibaseImport extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
} else {
	die( 'WikibaseImport requires MediaWiki 1.25+' );
}
