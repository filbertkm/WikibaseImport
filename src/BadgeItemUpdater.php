<?php

namespace Wikibase\Import;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Import\Store\ImportedEntityMappingStore;

class BadgeItemUpdater {

	/**
	 * @var ImportedEntityMappingStore
	 */
	private $entityMappingStore;

	/**
	 * @param ImportedEntityMappingStore $entityMappingStore
	 */
	public function __construct( ImportedEntityMappingStore $entityMappingStore ) {
		$this->entityMappingStore = $entityMappingStore;
	}

	/**
	 * @param SiteLinkList $siteLinks
	 */
	public function replaceBadges( SiteLinkList $siteLinks ) {
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
			$newBadges[] = $this->entityMappingStore->getLocalId( $badge );
		}

		return new SiteLink(
			$siteLink->getSiteId(),
			$siteLink->getPageName(),
			$newBadges
		);
	}

}
