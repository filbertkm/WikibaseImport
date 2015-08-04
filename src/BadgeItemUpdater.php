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
