<?php

declare ( strict_types = 1 );

namespace Northrook\Logger;

use Northrook\Logger\Log\Entry;
use Northrook\Logger\Log\Level;
use Psr\Log as Psr;
use Stringable;
use Throwable;

/**
 * # `7` | `600`
 *
 * Log events to the {@see Log::$inventory}.
 *
 * * Events are compliant with the {@see Psr\LoggerInterface}.
 *
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook/logger
 * @todo    Provide link to documentation
 */
final class Log
{

    private static array $inventory = [];

    /**
     * # 7 | `600`
     * System is unusable.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Emergency( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Emergency', [$message, $context] );
    }

    /**
     * # `6` | `550`
     *
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Alert( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Alert', [$message, $context] );
    }

    /**
     * # `5` | `500`
     *
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Critical( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Critical', [$message, $context] );
    }

    /**
     * # `4` | `400`
     *
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Error( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Error', [$message, $context] );
    }

    /**
     * # `3` | `300`
     *
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Warning( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Warning', [$message, $context] );
    }

    /**
     * # `2` | `250`
     *
     * Normal but significant events.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Notice( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Notice', [$message, $context] );
    }

    /**
     * # `1` | `200`
     *
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Info( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Info', [$message, $context] );
    }

    /**
     * # `0` | `100`
     *
     * Detailed debug information.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Debug( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Debug', [$message, $context] );
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param array  $arguments
     *
     * @return void
     */
    public static function entry( string $level, array $arguments ) : void {

        if ( false === in_array( $level, Level::NAMES, true ) ) {
            throw new Psr\InvalidArgumentException( 'Invalid log level.' );
        }

        $level   = Level::fromName( $level );
        $message = '';
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

        if ( $level->value >= 400 && !array_key_exists( 'backtrace', $context ) ) {
            $context[ 'backtrace' ] = Debug::traceLog();
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