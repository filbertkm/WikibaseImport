<?php

namespace Wikibase\Import;

use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Import\Store\ImportedEntityMappingStore;

class StatementCopier {

	private $entityMappingStore;

	private $idParser;

	private $logger;

	public function __construct(
		ImportedEntityMappingStore $entityMappingStore,
		LoggerInterface $logger
	) {
		$this->entityMappingStore = $entityMappingStore;
		$this->logger = $logger;
		$this->idParser = new BasicEntityIdParser();
	}

	/**
	 * @param Statement $statement
	 *
	 * @return Statement
	 */
	public function copy( Statement $statement ) {
		$mainSnak = $this->copySnak( $statement->getMainSnak() );
		$qualifiers = $this->copyQualifiers( $statement->getQualifiers() );
		$references = $this->copyReferences( $statement->getReferences() );

		$newStatement = new Statement( $mainSnak, $qualifiers, $references );
		$newStatement->setRank( $statement->getRank() );

		return $newStatement;
	}

	private function copySnak( Snak $mainSnak ) {
		$oldPropertyId = $mainSnak->getPropertyId();
		$newPropertyId = $this->entityMappingStore->getLocalId( $oldPropertyId );

		if ( !$newPropertyId ) {
			$this->logger->error( "Entity not found for $oldPropertyId." );
			$newPropertyId = $oldPropertyId;
		}

		switch( $mainSnak->getType() ) {
			case 'somevalue':
				return new PropertySomeValueSnak( $newPropertyId );
			case 'novalue':
				return new PropertyNoValueSnak( $newPropertyId );
			default:
				$value = $mainSnak->getDataValue();

				if ( $value instanceof EntityIdValue ) {
					$newValue = $this->replaceEntityIdValue( $value );

					if ( $newValue ) {
						// If we can't map the id, keep the old one.
						// replaceEntityIdValue() already logged the issue.
						$value = $newValue;
					}
				}

				return new PropertyValueSnak( $newPropertyId, $value );
		}
	}

	private function replaceEntityIdValue( EntityIdValue $value ) {
		$originalId = $value->getEntityId();
		$localId = $this->entityMappingStore->getLocalId( $originalId );

		if ( !$localId ) {
			$this->logger->error( "Entity not found for $originalId." );
			return null;
		}

		return new EntityIdValue( $localId );
	}

	private function copyQualifiers( SnakList $qualifiers ) {
		$newQualifiers = new SnakList();

		foreach( $qualifiers as $qualifier ) {
			$newQualifiers->addSnak( $this->copySnak( $qualifier ) );
		}

		return $newQualifiers;
	}

	private function copyReferences( ReferenceList $references ) {
		$newReferences = new ReferenceList();

		foreach( $references as $reference ) {
			$newReferenceSnaks = array();

			foreach( $reference->getSnaks() as $referenceSnak ) {
				$newReferenceSnaks[] = $this->copySnak( $referenceSnak );
			}

			$newReferences->addReference( new Reference( $newReferenceSnaks ) );
		}

		return $newReferences;
	}

}
