<?php

declare( strict_types = 1 );

namespace Northrook\Logger\Log;

use Northrook\Logger\StringCase;

enum Level : int
{
	/**
	 * Detailed debug information
	 */
	case DEBUG = 100;

	/**
	 * Interesting events
	 *
	 * Examples: User logs in, SQL logs.
	 */
	case INFO = 200;

	/**
	 * Uncommon events
	 */
	case NOTICE = 250;

	/**
	 * Exceptional occurrences that are not errors
	 *
	 * Examples: Use of deprecated APIs, poor use of an API,
	 * undesirable things that are not necessarily wrong.
	 */
	case WARNING = 300;

	/**
	 * Runtime errors
	 */
	case ERROR = 400;

	/**
	 * Critical conditions
	 *
	 * Example: Application component unavailable, unexpected exception.
	 */
	case CRITICAL = 500;

	/**
	 * Action must be taken immediately
	 *
	 * Example: Entire website down, database unavailable, etc.
	 * This should trigger the SMS alerts and wake you up.
	 */
	case ALERT = 550;

	/**
	 * Urgent alert.
	 */
	case EMERGENCY = 600;


	public static function fromName( string $name ) : self {
		foreach ( self::cases() as $status ) {
			if ( $name === $status->name ) {
				return $status;
			}
		}
		throw new \ValueError( "$name is not a valid backing value for enum " . self::class );
	}

	/**
	 * @param  StringCase|null  $case
	 * @return string
	 */
	public function name( ?StringCase $case = null ) : string {

		$name = self::NAMES[ $this->value ];

		if ( $case ) {
			return ( $case->value )( $name );
		}

		return $name;
	}

	public const NAMES = [
		100 => 'Debug',
		200 => 'Info',
		250 => 'Notice',
		300 => 'Warning',
		400 => 'Error',
		500 => 'Critical',
		550 => 'Alert',
		600 => 'Emergency',
	];
}