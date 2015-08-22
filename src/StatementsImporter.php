<?php

namespace Wikibase\Import;

use ApiMain;
use DataValues\Serializers\DataValueSerializer;
use Serializers\Serializer;
use FauxRequest;
use Psr\Log\LoggerInterface;
use RequestContext;
use User;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\EntityId\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

class StatementsImporter {

	private $statementSerializer;

	private $entityMappingStore;

	private $idParser;

	private $importUser;

	private $logger;

	private $apiUrl;

	public function __construct(
		StatementSerializer $statementSerializer,
		ImportedEntityMappingStore $entityMappingStore,
		LoggerInterface $logger,
		$apiUrl
	) {
		$this->statementSerializer = $statementSerializer;
		$this->entityMappingStore = $entityMappingStore;
		$this->logger = $logger;
		$this->apiUrl = $apiUrl;

		$this->importUser = User::newFromId( 0 );
		$this->idParser = new BasicEntityIdParser();
	}

	public function importStatements( Entity $entity ) {
		$statements = $entity->getStatements();

		$this->logger->info( 'Adding statements: ' . $entity->getId()->getSerialization() );

		if ( !$statements->isEmpty() ) {
			$localId = $this->entityMappingStore->getLocalId( $entity->getId()->getSerialization() );

			if ( !$localId ) {
				$this->logger->error( $entity->getId()->getSerialization() .  ' not found' );
			}

			try {
				$this->addStatementList( $this->idParser->parse( $localId ), $statements );
			} catch ( \Exception $ex ) {
				$this->logger->error( $ex->getMessage() );
			}
		}
	}

	private function addStatementList( EntityId $entityId, StatementList $statements ) {
		$data = array();

		foreach( $statements as $statement ) {
			try {
				$serialization = $this->statementSerializer->serialize( $this->copyStatement( $statement ) );
				$data[] = $serialization;
			} catch ( \Exception $ex ) {
				$this->logger->error( $ex->getMessage() );
			}
		}

		$params = array(
			'action' => 'wbeditentity',
			'data' => json_encode( array( 'claims' => $data ) ),
			'id' => $entityId->getSerialization()
		);

		$this->doApiRequest( $params );
	}

	private function copyStatement( Statement $statement ) {
		$mainSnak = $statement->getMainSnak();

		$newPropertyId = $this->entityMappingStore->getLocalId( $mainSnak->getPropertyId()->getSerialization() );

		switch( $mainSnak->getType() ) {
			case 'somevalue':
				$newMainSnak = new PropertySomeValueSnak( new PropertyId( $newPropertyId ) );
				break;
			case 'novalue':
				$newMainSnak = new PropertyNoValueSnak( new PropertyId( $newPropertyId ) );
				break;
			default:
				$value = $mainSnak->getDataValue();

				if ( $value instanceof EntityIdValue ) {
					$localId = $this->entityMappingStore->getLocalId( $value->getEntityId()->getSerialization() );

					if ( !$localId ) {
						$this->logger->error( "Entity not found for $localId." );
					}

					$value = new EntityIdValue( $this->idParser->parse( $localId ) );
				}

				$newMainSnak = new PropertyValueSnak( new PropertyId( $newPropertyId ), $value );
		}

		return new Statement( $newMainSnak );
	}

	private function doApiRequest( array $params ) {
		$context = RequestContext::getMain();

		$params['token'] = $this->importUser->getEditToken();

		$context->setRequest( new FauxRequest( $params, true ) );

		$apiMain = new ApiMain( $context, true );
		$apiMain->execute();

		$result = $apiMain->getResult()->getResultData();

		if ( array_key_exists( 'success', $result ) ) {
			return $result['entity']['id'];
		}
	}

}
