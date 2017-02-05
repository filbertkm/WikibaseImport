<?php

namespace Wikibase\Import;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Import\Api\BadgeItemsLookup;
use Wikibase\Import\Store\ImportedEntityMappingStore;

class BadgeItemsImporter {

	private $entityImporter;

	private $badgeItemsLookup;

	private $entityMappingStore;

	private $idParser;

	public function __construct(
		EntityImporter $entityImporter,
		BadgeItemsLookup $badgeItemsLookup,
		ImportedEntityMappingStore $entityMappingStore,
		EntityIdParser $idParser
	) {
		$this->entityImporter = $entityImporter;
		$this->badgeItemsLookup = $badgeItemsLookup;
		$this->entityMappingStore = $entityMappingStore;
		$this->idParser = $idParser;
	}

	/**
	 * @return array
	 */
	public function importBadgeItems() {
		$badgesToImport = $this->getBadgesToImport();

		if ( !empty( $badgesToImport ) ) {
			$this->entityImporter->importEntities( $badgesToImport, false );
		}

		return $badgesToImport;
	}

	/**
	 * @return array
	 */
	private function getBadgesToImport() {
		$badgeItemIds = $this->badgeItemsLookup->getBadgeItemIds();
		$badgesToImport = [];

		foreach ( $badgeItemIds as $badgeItemId ) {
			$id = $this->idParser->parse( $badgeItemId );
			$importedEntity = $this->entityMappingStore->getLocalId( $id );

			if ( $importedEntity === null ) {
				$badgesToImport[] = $badgeItemId;
			}
		}

		return $badgesToImport;
	}

}