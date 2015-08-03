<?php

namespace Wikibase\Import;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

class BadgeItemUpdater {

	/**
	 * @var ImportedEntityMappingStore
	 */
	private $entityMappingStore;

	/**
	 * @var EntityImporter
	 */
	private $entityImporter;

	/**
	 * @param ImportedEntityMappingStore $entityMappingStore
	 * @param EntityImporter $entityImporter
	 */
	public function __construct(
		ImportedEntityMappingStore $entityMappingStore,
		EntityImporter $entityImporter
	) {
		$this->entityMappingStore = $entityMappingStore;
		$this->entityImporter = $entityImporter;
	}

	/**
	 * @param SiteLinkList $siteLinks
	 */
	public function replaceBadges( SiteLinkList $siteLinks ) {
		$badgeItems = $this->getBadgeItems( $siteLinks );

		if ( $badgeItems === array() ) {
			return $siteLinks;
		}

		$siteLinkList = new SiteLinkList();

		$this->entityImporter->importIds( $badgeItems, false );

		return $this->replaceAllBadgeItems( $siteLinks );
	}

	private function getBadgeItems( SiteLinkList $siteLinks ) {
		$badgeItems = array();

		foreach( $siteLinks as $siteLink ) {
			foreach( $siteLink->getBadges() as $badge ) {
				$badgeItems[] = $badge->getSerialization();
			}
		}

		return array_unique( $badgeItems );
	}

	private function replaceAllBadgeItems( SiteLinkList $siteLinks ) {
		$newSiteLinks = array();

		foreach( $siteLinks as $siteLink ) {
			if ( $siteLink->getBadges() !== array() ) {
				$newSiteLinks[] = $this->replaceBadgeItemsInSiteLink( $siteLink );
			} else {
				$newSiteLinks[] = $siteLink;
			}
		}

		return new SiteLinkList( $newSiteLinks );
	}

	private function replaceBadgeItemsInSiteLink( SiteLink $siteLink ) {
		$newBadges = array();

		foreach( $siteLink->getBadges() as $badge ) {
			$localId = $this->entityMappingStore->getLocalId( $badge->getSerialization() );
			$newBadges[] = new ItemId( $localId );
		}

		return new SiteLink(
			$siteLink->getSiteId(),
			$siteLink->getPageName(),
			$newBadges
		);
	}

}
