<?php

namespace Wikibase\Import;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

class LoggerFactory {

	/**
	 * @param bool $quietMode
	 *
	 * @return LoggerInterface
	 */
	public static function newLogger( $loggerName, $quietMode ) {
		if ( !is_string( $loggerName ) ) {
			throw new InvalidArgumentException( '$loggerName must be a string' );
		}

		if ( !is_bool( $quietMode ) ) {
			throw new InvalidArgumentException( '$quietMode must be boolean' );
		}

		$formatter = new LineFormatter( "[%datetime%]: %message%\n" );

		if ( $quietMode === true ) {
			$handler = new NullHandler();
		} else {
			$handler = new StreamHandler( 'php://stdout' );
			$handler->setFormatter( $formatter );
		}

		$logger = new Logger( $loggerName );
		$logger->pushHandler( $handler );

		return $logger;
	}

}
