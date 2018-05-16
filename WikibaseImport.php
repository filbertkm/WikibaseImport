<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseImport', __DIR__ . '/extension.json' );
} else {
	die( 'WikibaseImport requires MediaWiki 1.25+' );
}
