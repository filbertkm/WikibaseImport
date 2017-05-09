<?php

use \Wikibase\Import\Maintenance\ImportEntities;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/../src/Maintenance/ImportEntities.php';

$maintClass = ImportEntities::class;
require_once RUN_MAINTENANCE_IF_MAIN;
