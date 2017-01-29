<?php

namespace Wikibase\Import;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

class EntityDuplicator {

	/**
	 * @var BadgeItemUpdater
	 */
	private $badgeItemUpdater;

	/**
	 * @param BadgeItemUpdater $badgeItemUpdater
	 */
	public function __construct( BadgeItemUpdater $badgeItemUpdater ) {
		$this->badgeItemUpdater = $badgeItemUpdater;
	}

	/**
	 * @param EntityDocument $entity
	 * @return Item|Property
	 * @throws \RuntimeException
	 */
	public function copyEntityWithoutStatements( EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			$siteLinkList = $this->badgeItemUpdater->replaceBadges( $entity->getSiteLinkList() );

			return new Item(
				null,
				$entity->getFingerprint(),
				$siteLinkList
			);
		} elseif ( $entity instanceof Property ) {
			return new Property(
				null,
				$entity->getFingerprint(),
				$entity->getDataTypeId()
			);
		} else {
			throw new \RuntimeException( 'Unsupported entity type' );
		}
	}

}
