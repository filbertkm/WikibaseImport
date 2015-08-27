<?php

namespace Wikibase\Import;

use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

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

	public function copy( Statement $statement ) {
		$mainSnak = $this->copySnak( $statement->getMainSnak() );
		$qualifiers = $this->copyQualifiers( $statement->getQualifiers() );

		return new Statement( $mainSnak, $qualifiers );
	}

	private function copySnak( Snak $mainSnak ) {
		$newPropertyId = $this->entityMappingStore->getLocalId( $mainSnak->getPropertyId()->getSerialization() );

		switch( $mainSnak->getType() ) {
			case 'somevalue':
				return new PropertySomeValueSnak( new PropertyId( $newPropertyId ) );
			case 'novalue':
				return new PropertyNoValueSnak( new PropertyId( $newPropertyId ) );
			default:
				$value = $mainSnak->getDataValue();

				if ( $value instanceof EntityIdValue ) {
					$localId = $this->entityMappingStore->getLocalId( $value->getEntityId()->getSerialization() );

					if ( !$localId ) {
						$this->logger->error( "Entity not found for $localId." );
					}

					$value = new EntityIdValue( $this->idParser->parse( $localId ) );
				}

				return new PropertyValueSnak( new PropertyId( $newPropertyId ), $value );
		}
	}

	private function copyQualifiers( SnakList $qualifiers ) {
		$newQualifiers = new SnakList();

		foreach( $qualifiers as $qualifier ) {
			$newQualifiers->addSnak( $this->copySnak( $qualifier ) );
		}

		return $newQualifiers;
	}

}
