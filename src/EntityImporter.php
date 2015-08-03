<?php

namespace Wikibase\Import;

use ApiMain;
use DataValues\Serializers\DataValueSerializer;
use Serializers\Serializer;
use FauxRequest;
use RequestContext;
use User;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\EntityId\BasicEntityIdParser;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Store\WikiPageEntityStore;
use Wikibase\Repo\WikibaseRepo;

class EntityImporter {

	private $statementSerializer;

	private $propertyIdLister;

	private $apiEntityLookup;

	private $entityLookup;

	private $entityStore;

	private $entityMappingStore;

	private $badgeItemUpdater;

	private $idParser;

	private $importUser;

	private $apiUrl;

	private $batchSize;

	public function __construct(
		StatementSerializer $statementSerializer,
		ApiEntityLookup $apiEntityLookup,
		EntityRevisionLookup $entityRevisionLookup,
		WikiPageEntityStore $entityStore,
		ImportedEntityMappingStore $entityMappingStore,
		$apiUrl
	) {
		$this->statementSerializer = $statementSerializer;
		$this->apiEntityLookup = $apiEntityLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->entityMappingStore = $entityMappingStore;
		$this->apiUrl = $apiUrl;

		$this->importUser = User::newFromId( 0 );
		$this->idParser = new BasicEntityIdParser();

		$this->batchSize = 10;

		$this->badgeItemUpdater = new BadgeItemUpdater( $entityMappingStore, $this );
	}

	public function importIds( array $ids, $importStatements = true ) {
		$batches = array_chunk( $ids, $this->batchSize );

		$stashedEntities = array();

		foreach( $batches as $batch ) {
			$stashedEntities = array_merge( $stashedEntities, $this->importBatch( $batch ) );
		}

		if ( $importStatements === true ) {
			$this->importStatements( $stashedEntities );
		}
	}

	private function importBatch( array $batch ) {
		$entities = $this->apiEntityLookup->getEntities( $batch, $this->apiUrl );

		$stashedEntities = array();

		foreach( $entities as $originalId => $entity ) {
			$stashedEntities[] = $entity->copy();

			echo "importing $originalId\n";

			if ( !$this->entityMappingStore->getLocalId( $originalId ) ) {
				try {
					$entityRevision = $this->createEntity( $entity );
					$localId = $entityRevision->getEntity()->getId()->getSerialization();
					$this->entityMappingStore->add( $originalId, $localId );
				} catch( \Exception $ex ) {
					echo "failed to add $originalId\n";
					echo $ex->getMessage();
					echo "\n";
					// omg!
				}
			}
		}

		return $stashedEntities;
	}

	private function createEntity( Entity $entity ) {
		$entity->setId( null );

		$entity->setStatements( new StatementList() );

		if ( $entity instanceof Item ) {
			$siteLinkList = $this->badgeItemUpdater->replaceBadges( $entity->getSiteLinkList() );
			$entity->setSiteLinkList( $siteLinkList );
		}

		return $this->entityStore->saveEntity(
			$entity,
			'Import entity',
			$this->importUser,
			EDIT_NEW
		);
	}

	private function getReferencedEntities( StatementList $statementList ) {
		$snaks = $statementList->getAllSnaks();
		$entities = array();

		foreach( $snaks as $snak ) {
			$entities[] = $snak->getPropertyId();

			if ( $snak instanceof PropertyValueSnak ) {
				$value = $snak->getDataValue();

				if ( $value instanceof EntityIdValue ) {
					$entities[] = $value->getEntityId()->getSerialization();
				}
			}
		}

		return array_unique( $entities );
	}

	private function importStatements( array $stashedEntities ) {
		foreach( $stashedEntities as $entity ) {
			$statements = $entity->getStatements();

			echo "adding statements: " . $entity->getId()->getSerialization() . "\n";

			if ( !$statements->isEmpty() ) {
				$localId = $this->entityMappingStore->getLocalId( $entity->getId()->getSerialization() );

				$referencedEntities = $this->getReferencedEntities( $statements );

				$this->importIds( $referencedEntities, false );

				try {
					$this->addStatementList( $this->idParser->parse( $localId ), $statements );
				} catch ( \Exception $ex ) {
					echo $ex->getMessage();
				}
			}
		}
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
					$localId = $this->entityMappingStore->getLocalId( $value->getEntityId() );
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
