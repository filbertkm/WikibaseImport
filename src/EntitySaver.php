<?php

namespace Wikibase\Import;

use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;
use Wikibase\Repo\Store\WikiPageEntityStore;

class EntitySaver {

	private $entityDuplicator;

	private $entityStore;

	private $importUser;

	public function __construct(
		EntityDuplicator $entityDuplicator,
		WikiPageEntityStore $entityStore,
		User $importUser
	) {
		$this->entityDuplicator = $entityDuplicator;
		$this->entityStore = $entityStore;
		$this->importUser = $importUser;
	}

	/**
	 * @param EntityDocument $entity
	 * @return EntityRevision
	 * @throws \UnexpectedValueException
	 */
	public function saveEntity( EntityDocument $entity ) {
		if ( $entity instanceof Item || $entity instanceof Property ) {
			$newEntity = $this->entityDuplicator->copyEntityWithoutStatements( $entity );

			return $this->entityStore->saveEntity(
				$newEntity,
				'Import entity',
				$this->importUser,
				EDIT_NEW
			);
		} else {
			// TODO support other entity types
			throw new \UnexpectedValueException(
				'[EntitySaver] Unsupported entity type cannot be imported'
			);
		}
	}
}
