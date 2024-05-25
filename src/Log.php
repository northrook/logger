<?php

declare ( strict_types = 1 );

namespace Northrook\Logger;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
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
     * # `7` Emergency | `600`
     * System is unusable.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Emergency(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::EMERGENCY, $message, $context );
    }

    /**
     * # `6` Alert | `550`
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
    public static function Alert(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::ALERT, $message, $context );
    }

    /**
     * # `5` Critical | `500`
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
    public static function Critical(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::CRITICAL, $message, $context );
    }

    /**
     * # `4` Error | `400`
     *
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Error(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::ERROR, $message, $context );
    }

    /**
     * # `3` Warning | `300`
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
    public static function Warning(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::WARNING, $message, $context );
    }

    /**
     * # `2` Notice | `250`
     *
     * Normal but significant events.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Notice(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::NOTICE, $message, $context );
    }

    /**
     * # `1` Info | `200`
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
    public static function Info(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::INFO, $message, $context );
    }

    /**
     * # `0` Debug | `100`
     *
     * Detailed debug information.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Debug(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::entry( Level::DEBUG, $message, $context );
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string | Level     $level  = [ 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' ][$any]
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function Entry(
        string | Level      $level,
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {

        if ( is_string( $level ) ) {
            if ( false === in_array( $level, Level::NAMES, true ) ) {
                throw new Psr\InvalidArgumentException( 'Invalid log level.' );
            }

            $level = Level::fromName( $level );
        }

        foreach ( $context as $index => $argument ) {

            if ( $argument instanceof Stringable && !$argument instanceof Throwable ) {
                $context[ $index ] = (string) $argument;
                continue;
            }

            if ( $argument instanceof Throwable ) {

                if ( $index === 'exception' ) {
                    continue;
                }

                if ( array_key_exists( 'exception', $context ) ) {
                    if ( $argument === $context[ 'exception' ] ) {
                        unset( $context[ $index ] );
                    }
                    else {
                        continue;
                    }
                }

                unset( $context[ $index ] );
                $context[ 'exception' ] = $argument;
            }
        }

        if ( $level->value >= 400 && !array_key_exists( 'backtrace', $context ) ) {
            $context[ 'backtrace' ] = Log::backtrace();
        }

        Log::$inventory[] = new Entry(
            $message,
            $context,
            $level,
        );

    }

    public static function inventory() : array {
        return Log::$inventory;
    }

    private static function backtrace() : array {

        $contains = static function (
            string $string,
            array  $needle,
        ) : array {

            $contains = [];
            $search   = strtolower( $string );

            foreach ( $needle as $value ) {
                if ( substr_count( $search, strtolower( $value ) ) ) {
                    $contains[] = $value;
                }
            }

            return $contains;
        };

        $backtrace = [];

        foreach ( array_slice( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 2 ) as $index => $trace ) {
            if ( array_key_exists( 'file', $trace ) ) {
                $key = strtr( $trace[ 'file' ], '\\', '/' );
                if ( str_ends_with( $key, 'vendor/symfony/http-kernel/HttpKernel.php' ) ) {
                    break;
                }
                $has    = $contains( $key, [ 'src/', 'var/', 'public/', 'vendor/' ] );
                $needle = array_pop( $has );

                if ( !$needle ) {
                    continue;
                }

                $index = strstr( $key, $needle );

                if ( strlen( $index ) > 42 ) {
                    $index = '..' . strstr( substr( $index, -42 ), '/' ) ?: strrchr( $index, '/' );
                }
            }
            $backtrace[ $index ] = $trace;
        }
        return $backtrace;
    }
}