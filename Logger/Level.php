<?php

declare ( strict_types = 1 );

namespace Northrook\Logger;

/**
 * PSR-3 compliant {@see \Psr\Log\LogLevel} Enum.
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
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

    public const NAMES = [
        100 => 'debug',
        200 => 'info',
        250 => 'notice',
        300 => 'warning',
        400 => 'error',
        500 => 'critical',
        550 => 'alert',
        600 => 'emergency',
    ];

    public static function fromName( string $name ) : self {

        foreach ( Level::cases() as $status ) {
            if ( \strtoupper( $name ) === $status->name ) {
                return $status;
            }
        }
        throw new \ValueError( "{$name} is not a valid backing value for enum " . Level::class );
    }

    /**
     * @return string
     */
    public function name() : string {
        return Level::NAMES[ $this->value ];
    }

}