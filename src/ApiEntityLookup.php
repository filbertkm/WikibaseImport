<?php

namespace Wikibase\Import;

use Deserializers\DispatchingDeserializer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Import\Api\MediaWikiApiClient;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiEntityLookup implements EntityLookup {

	/**
	 * @var DispatchingDeserializer
	 */
	private $deserializer;

	/**
	 * @var MediaWikiApiClient
	 */
	private $apiClient;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param DispatchingDeserializer $deserializer
	 * @param MediaWikiApiClient $apiClient
	 * @param LoggerInterface $logger
	 * @param string $apiUrl
	 */
	public function __construct(
		DispatchingDeserializer $deserializer,
		MediaWikiApiClient $apiClient,
		LoggerInterface $logger
	) {
		$this->deserializer = $deserializer;
		$this->apiClient = $apiClient;
		$this->logger = $logger;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument
	 */
	public function getEntity( EntityId $entityId ) {
		$prefixedId = $entityId->getSerialization();
		$entities = $this->getEntities( [ $prefixedId ] );

		foreach ( $entities as $entity ) {
			return $entity;
		}

		return null;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->getEntity( $entityId ) !== null;
	}

	/**
	 * @param string[] $ids
	 *
	 * @throws RuntimeException
	 * @return EntityDocument[]
	 */
	public function getEntities( array $ids ) {
		$params = [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $ids ),
		];

		$data = $this->apiClient->get( $params );

		if ( $data && array_key_exists( 'success', $data ) ) {
			unset( $data['success'] );

			return $this->extractEntities( $data );
		} else {
			throw new \RuntimeException( 'API request to wbgetentities failed' );
		}
	}

	private function extractEntities( array $entries ) {
		$entities = [];

		foreach ( $entries as $entry ) {
			foreach ( $entry as $entityId => $serialization ) {
				if ( array_key_exists( 'missing', $serialization ) ) {
					continue;
				} elseif ( $this->deserializer->isDeserializerFor( $serialization ) ) {
					$entities[$entityId] = $this->deserializer->deserialize( $serialization );
				}
			}
		}

		return $entities;
	}
}
