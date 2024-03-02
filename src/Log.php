<?php

declare( strict_types = 1 );

namespace Northrook\Logger;

use Northrook\Logger\Facades\StaticClassTrait;
use Northrook\Logger\Log\Entry;
use Northrook\Logger\Log\Level;
use Psr\Log as Psr;
use Stringable;
use Throwable;

/**
 * @method static Emergency( string | Stringable $message, array $context = [] )
 * @method static Alert( string | Stringable $message, array $context = [] )
 * @method static Critical( string | Stringable $message, array $context = [] )
 * @method static Error( string | Stringable $message, array $context = [] )
 * @method static Warning( string | Stringable $message, array $context = [] )
 * @method static Notice( string | Stringable $message, array $context = [] )
 * @method static Info( string | Stringable $message, array $context = [] )
 * @method static Debug( string | Stringable $message, array $context = [] )
 *
 * @see Psr\LoggerInterface
 *
 * @author Martin Nielsen <mn@northrook.com>
 * @version 0.1.5 ☑️
 */
final class Log
{
	use StaticClassTrait;

	private static array $inventory = [];

	public static function __callStatic( string $level, array $arguments ) {

		if ( false === in_array( $level, Level::NAMES ) ) {
			throw new Psr\InvalidArgumentException( 'Invalid log level.' );
		}

		$level = Level::fromName( $level );
		$message = null;
		$context = [];

		foreach ( $arguments as $index => $argument ) {
			if (
				is_string( $argument )
				||
				( $argument instanceof Stringable && !$argument instanceof Throwable )
			) {
				$message = $argument;
				unset( $arguments[ $index ] );
				continue;
			}

			if ( is_array( $argument ) ) {
				$context = $argument;
				unset( $arguments[ $index ] );
				continue;
			}

			if ( $argument instanceof Throwable ) {
				$context[ 'exception' ] = $argument;

				unset( $arguments[ $index ] );

				if ( array_key_exists( 'exception', $arguments ) ) {
					if ( $arguments[ 'exception' ] !== $context[ 'exception' ] ) {
						$context[] = $arguments[ 'exception' ];
					}
					unset( $arguments[ 'exception' ] );
				}
				continue;
			}

			$context[] = $argument;
			unset( $arguments[ $index ] );
		}

		Log::$inventory[] = new Entry(
			$message,
			$context,
			$level
		);

	}

	public static function inventory() : array {
		return Log::$inventory;
	}
}