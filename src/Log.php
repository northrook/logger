<?php


use Northrook\Logger\Log\Entry;
use Northrook\Logger\Log\Level;
use Northrook\Logger\StaticClassTrait;

/**
 * @method static void Emergency( string | Stringable $message, array $context = [] )
 * @method static void Alert( string | Stringable $message, array $context = [] )
 * @method static void Critical( string | Stringable $message, array $context = [] )
 * @method static void Error( string | Stringable $message, array $context = [] )
 * @method static void Warning( string | Stringable $message, array $context = [] )
 * @method static void Notice( string | Stringable $message, array $context = [] )
 * @method static void Info( string | Stringable $message, array $context = [] )
 * @method static void Debug( string | Stringable $message, array $context = [] )
 *
 * @see Psr\Log\LoggerInterface
 *
 * @author Martin Nielsen <mn@northrook.com>
 * @version 0.1.0 ☑️
 */
final class Log
{
	use StaticClassTrait;

	private static array $inventory = [];

	public static function __callStatic( string $level, array $arguments ) {

		if ( false === in_array( $level, Level::NAMES ) ) {
			throw new Psr\Log\InvalidArgumentException( 'Invalid log level.' );
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