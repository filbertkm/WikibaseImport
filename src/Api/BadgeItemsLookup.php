<?php

namespace Wikibase\Import\Api;

class BadgeItemsLookup {

	/**
	 * @var string
	 */
	private $apiClient;

	/**
	 * @param MediaWikiApiClient $apiClient
	 */
	public function __construct( MediaWikiApiClient $apiClient ) {
		$this->apiClient = $apiClient;
	}

	/**
	 * @throws \RuntimeException
	 * @return array
	 */
	public function getBadgeItemIds() {
		$params = [
			'action' => 'wbavailablebadges'
		];

		$data = $this->apiClient->get( $params );

		if ( $data && array_key_exists( 'badges', $data ) ) {
			return $data['badges'];
		} else {
			throw new \RuntimeException( 'Error returning badges from API' );
		}
	}

}