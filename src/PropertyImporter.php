<?php

namespace Wikibase\Import;

use ApiMain;
use DataValues\Serializers\DataValueSerializer;
use Serializers\Serializer;
use FauxRequest;
use RequestContext;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Repo\WikibaseRepo;

class PropertyImporter {

	private $entitySerializer;

	private $statementSerializer;

	private $propertyIdLister;

	private $apiEntityLookup;

	private $entityLookup;

	private $entityStore;

	private $idParser;

	public function __construct(
		Serializer $entitySerializer,
		StatementSerializer $statementSerializer,
		PropertyIdLister $propertyIdLister,
		ApiEntityLookup $apiEntityLookup,
		EntityLookup $entityLookup,
		ImportedEntityStore $entityStore
	) {
		$this->entitySerializer = $entitySerializer;
		$this->statementSerializer = $statementSerializer;
		$this->propertyIdLister = $propertyIdLister;
		$this->apiEntityLookup = $apiEntityLookup;
		$this->entityLookup = $entityLookup;
		$this->entityStore = $entityStore;

		$this->idParser = new BasicEntityIdParser();
	}

	public function import( $apiUrl, $file = null ) {
		if ( $file === null ) {
			$ids = $this->propertyIdLister->fetch( $apiUrl );
		} else {
			$ids = array_map( 'trim', file( $file ) );
		}

		var_export( $ids );

		$this->importIds( $ids, $apiUrl );
	}

	private function importIds( array $ids, $apiUrl, $importStatements = true ) {
		$idChunks = array_chunk( $ids, 10 );

		$stashedEntities = array();

		$verbose = $importStatements ? false : true;

		foreach( $idChunks as $idChunk ) {
			$stashedEntities = array_merge(
				$stashedEntities,
				$this->importChunk( $idChunk, $apiUrl, $verbose )
			);
		}

		if ( !$importStatements ) {
			return;
		}

		foreach( $stashedEntities as $entity ) {
			$statements = $entity->getStatements();

			echo "process " . $entity->getId()->getSerialization() . "\n";

			if ( !$statements->isEmpty() ) {
				$localId = $this->entityStore->getLocalId( $entity->getId()->getSerialization() );

				$referencedEntities = $this->getReferencedEntities( $statements );

				$this->importIds( $referencedEntities, $apiUrl, false );

				try {
					$this->addStatementList( $this->idParser->parse( $localId ), $statements );
				} catch ( \Exception $ex ) {
					echo $ex->getMessage();
				}
			}
		}
	}

	private function importChunk( $idChunk, $apiUrl, $verbose = false ) {
		$entities = $this->apiEntityLookup->getEntities( $idChunk, $apiUrl );

		$stashedEntities = array();

		foreach( $entities as $originalId => $entity ) {
			$stashedEntities[] = $entity->copy();

			echo "check $originalId\n";

			if ( !$this->entityStore->getLocalId( $originalId ) ) {
				try {
					$localId = $this->addEntity( $entity );

					$this->entityStore->add( $originalId, $localId );
				} catch( \UsageException $ex ) {
					echo "failed to add $originalId\n";
					echo $ex->getMessage();
					echo "\n";
					// omg!
				}
			}
		}

		return $stashedEntities;
	}

	private function addEntity( Entity $entity ) {
		$entity->setId( null );

		$entity->setStatements( new StatementList() );

		if ( $entity instanceof Item ) {
			// @fixme handle badge items
			$entity->setSiteLinkList( new SiteLinkList() );
		}

		return $this->createEntity( $entity );
	}

	private function createEntity( Entity $entity ) {
		$params = array(
			'action' => 'wbeditentity',
			'data' => json_encode( $this->entitySerializer->serialize( $entity ) ),
			'new' => $entity->getType()
		);

		return $this->doApiRequest( $params );
	}

	private function getReferencedEntities( StatementList $statementList ) {
		$snaks = $statementList->getAllSnaks();
		$entities = array();

		foreach( $snaks as $snak ) {
			if ( $snak instanceof PropertyValueSnak ) {
				$value = $snak->getDataValue();

				if ( $value instanceof EntityIdValue ) {
					$entities[] = $value->getEntityId()->getSerialization();
				}
			}
		}

		return array_unique( $entities );
	}

	private function addStatementList( EntityId $entityId, StatementList $statements ) {
		$data = array();

		foreach( $statements as $statement ) {
			$serialization = $this->statementSerializer->serialize( $this->copyStatement( $statement ) );
			$data[] = $serialization;
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

		$newPropertyId = $this->entityStore->getLocalId( $mainSnak->getPropertyId()->getSerialization() );

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
					$localId = $this->entityStore->getLocalId( $value->getEntityId() );
					$value = new EntityIdValue( $this->idParser->parse( $localId ) );
				}

				$newMainSnak = new PropertyValueSnak( new PropertyId( $newPropertyId ), $value );
		}

		return new Statement( $newMainSnak );
	}

	private function doApiRequest( array $params ) {
		$context = RequestContext::getMain();

		$params['token'] = $context->getUser()->getEditToken();

		$context->setRequest( new FauxRequest( $params, true ) );

		$apiMain = new ApiMain( $context, true );
		$apiMain->execute();

		$result = $apiMain->getResult()->getResultData();

		if ( array_key_exists( 'success', $result ) ) {
			return $result['entity']['id'];
		}
	}

}
