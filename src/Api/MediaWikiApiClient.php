<?php

namespace Wikibase\Import\Api;

use Http;

class MediaWikiApiClient {

	/**
	 * @var string
	 */
	private $apiBaseUrl;

	/**
	 * @param string $apiBaseUrl
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $apiBaseUrl ) {
		if ( !is_string( $apiBaseUrl ) ) {
			throw new \InvalidArgumentException( '$apiBaseUrl must be a string' );
		}

		$this->apiBaseUrl = $apiBaseUrl;
	}

	/**
	 * @param array $params
	 * @throws \RuntimeException
	 * @return array
	 */
	public function get( array $params ) {
		$params['format'] = 'json';

		$json = Http::get(
			wfAppendQuery( $this->apiBaseUrl, $params ),
			[],
			__METHOD__
		);

		$data = json_decode( $json, true );

		if ( is_array( $data ) ) {
			return $data;
		}

		throw new \RuntimeException( 'Failed to decode JSON API response' );
	}

}