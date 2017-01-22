<?php

namespace Wikibase\Import\Console;

use ArrayIterator;
use IteratorAggregate;

class ImportOptions implements IteratorAggregate {

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @param array $options
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	public function setOption( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function hasOption( $name ) {
		return isset( $this->options[$name] );
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function getOption( $name ) {
		if ( !$this->hasOption( $name ) ) {
			throw new \OutOfBoundsException( "Unknown option: $name" );
		}

		return $this->options[$name];
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->options );
	}

}
