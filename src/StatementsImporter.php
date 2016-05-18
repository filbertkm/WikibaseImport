<?php

namespace Wikibase\Import;

use ApiMain;
use Serializers\Serializer;
use FauxRequest;
use Psr\Log\LoggerInterface;
use RequestContext;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Import\Store\ImportedEntityMappingStore;

class StatementsImporter {

	private $statementSerializer;

	private $entityMappingStore;

	private $statementCopier;

	private $idParser;

	private $importUser;

	private $logger;

	public function __construct(
		StatementSerializer $statementSerializer,
		ImportedEntityMappingStore $entityMappingStore,
		LoggerInterface $logger
	) {
		$this->statementSerializer = $statementSerializer;
		$this->entityMappingStore = $entityMappingStore;
		$this->logger = $logger;

		$this->statementCopier = new StatementCopier( $entityMappingStore, $logger );
		$this->importUser = User::newFromId( 0 );
		$this->idParser = new BasicEntityIdParser();
	}

	public function importStatements( EntityDocument $entity ) {
		$statements = $entity->getStatements();

		$this->logger->info( 'Adding statements: ' . $entity->getId()->getSerialization() );

		if ( !$statements->isEmpty() ) {
			$entityId = $entity->getId();

			if ( $entityId instanceof EntityId ) {
				$localId = $this->entityMappingStore->getLocalId( $entityId );

				if ( !$localId ) {
					$this->logger->error( $entityId->getSerialization() .  ' not found' );
				} else {
					try {
						$this->addStatementList( $localId, $statements );
					} catch ( \Exception $ex ) {
						$this->logger->error( $ex->getMessage() );
					}
				}
			} else {
				$this->logger->error( 'EntityId not set for entity' );
			}
		}
	}

	private function addStatementList( EntityId $entityId, StatementList $statements ) {
		$data = array();

		foreach( $statements as $statement ) {
			try {
				$data[] = $this->statementSerializer->serialize(
					$this->statementCopier->copy( $statement )
				);
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
