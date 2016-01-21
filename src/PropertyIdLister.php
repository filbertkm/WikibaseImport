<?php

namespace Wikibase\Import;

use Http;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyIdLister {

	private $continuation = null;

	private $properties = array();

	/**
	 * @param string $apiUrl
	 *
	 * @return string[]
	 */
	public function fetch( $apiUrl ) {
		do {
			$res = $this->doRequest( $apiUrl );

			$this->extractContinuation( $res );
			$this->extractProperties( $res );
		} while ( $this->continuation !== null );

		return $this->properties;
	}

	private function doRequest( $apiUrl ) {
		$params = array(
			'action' => 'query',
			'list' => 'allpages',
			'apnamespace' => 120,
			'aplimit' => 300,
			'format' => 'json',
			'rawcontinue' => 1
		);

		if ( isset( $this->continuation ) && $this->continuation !== null ) {
			$params['apfrom'] = $this->continuation;
		}

		$json = Http::get(
			wfAppendQuery( $apiUrl, $params ),
			array(),
			__METHOD__
		);

		return json_decode( $json, true );
	}

	private function extractContinuation( array $res ) {
		if ( !array_key_exists( 'query-continue', $res ) ) {
			$this->continuation = null;

			return;
		}

		$this->continuation = $res['query-continue']['allpages']['apcontinue'];
	}

	private function extractProperties( array $res ) {
		if ( !array_key_exists( 'query', $res ) ) {
			throw new \RuntimeException( 'query param not found in result' );
		}

		foreach( $res['query']['allpages'] as $page ) {
			$parts = explode( ':', $page['title'] );
			$this->properties[] = $parts[1];
		}
	}

}
