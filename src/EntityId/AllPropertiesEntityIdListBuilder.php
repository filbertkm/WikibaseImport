<?php

namespace Wikibase\Import\EntityId;

use InvalidArgumentException;
use Wikibase\Import\PropertyIdLister;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class AllPropertiesEntityIdListBuilder implements EntityIdListBuilder {

	/**
	 * @var PropertyIdLister
	 */
	private $propertyIdLister;

	/**
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @param PropertyIdLister $propertyIdLister
	 * @param string $apiUrl
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( PropertyIdLister $propertyIdLister, $apiUrl ) {
		if ( !is_string( $apiUrl ) ) {
			throw new InvalidArgumentException( '$apiUrl must be a string' );
		}

		$this->propertyIdLister = $propertyIdLister;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @param string $input
	 *
	 * @return string[]
	 */
	public function getEntityIds( $input ) {
		return $this->propertyIdLister->fetch( $this->apiUrl );
	}

}
