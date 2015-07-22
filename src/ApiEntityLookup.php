<?php

namespace Wikibase\Import;

use Deserializers\DispatchingDeserializer;
use Http;
use Wikibase\Lib\Store\EntityLookup;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiEntityLookup {

	/**
	 * @var DispatchingDeserializer
	 */
	private $deserializer;

	/**
	 * @param DispatchingDeserializer $deserializer
	 */
	public function __construct( DispatchingDeserializer $deserializer ) {
		$this->deserializer = $deserializer;
	}

	/**
	 * @param string[] $ids
	 * @param string $apiUrl
	 *
	 * @throws RuntimeException
	 * @return Entity[]
	 */
	public function getEntities( array $ids, $apiUrl ) {
		$data = $this->doRequest( $ids, $apiUrl );

		if ( $data && array_key_exists( 'success', $data ) ) {
			unset( $data['success'] );
			return $this->extractEntities( $data );
		}

		throw new \RuntimeException( 'Api request failed' );
	}

	private function doRequest( array $ids, $apiUrl ) {
		$params = array(
			'action' => 'wbgetentities',
			'ids' => implode( '|', $ids ),
			'format' => 'json'
		);

		$json = Http::get(
			wfAppendQuery( $apiUrl, $params ),
			array(),
			__METHOD__
		);

		$data = json_decode( $json, true );

		if ( $data ) {
			return $data;
		}

		throw new \RuntimeException( 'Api request failed' );
	}

	private function extractEntities( array $entries ) {
		$entities = array();

		foreach( $entries as $entry ) {
			foreach( $entry as $entityId => $serialization ) {
				if ( array_key_exists( 'missing', $serialization ) ) {
					continue;
				} else if ( $this->deserializer->isDeserializerFor( $serialization ) ) {
					$entities[$entityId] = $this->deserializer->deserialize( $serialization );
				}
			}
		}

		return $entities;
	}
}
