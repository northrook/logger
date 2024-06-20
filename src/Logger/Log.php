<?php

declare ( strict_types = 1 );

namespace Northrook\Logger;

use JetBrains\PhpStorm\Language;
use Northrook\Logger;
use Psr\Log\LoggerInterface;
use Stringable, Throwable;
use function strstr, strpos, substr, get_debug_type, hrtime, number_format, strlen, ltrim, str_pad;


/**
 *
 * Log events to the {@see Log::$inventory}.
 *
 * - Events are compliant with the {@see LoggerInterface}.
 *
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook/logger
 */
final class Log
{
    private static LoggerInterface $logger;

    private static bool $enablePrecision;
    private static int  $precisionTimestamp;
    private static ?int $precisionPreviousEntry = null;

    /**
     * Set the {@see LoggerInterface} instance.
     *
     * @param LoggerInterface  $logger  The {@see LoggerInterface} instance to use
     * @param bool             $import  [true] Import the array from {@see LoggerInterface} if using the default {@see Logger}
     *
     * @return LoggerInterface The current set {@see LoggerInterface} instance
     */
    public static function setLogger(
        LoggerInterface $logger,
        bool            $enablePrecision = true,
        bool            $import = true,
    ) : LoggerInterface {

        Log::$enablePrecision    = $enablePrecision;
        Log::$precisionTimestamp ??= hrtime( true );

        if ( $import && isset( Log::$logger ) && $logger instanceof Logger ) {
            $logger->import( Log::$logger );
        }

        return Log::$logger = $logger;
    }

    /**
     * # `E` Exception
     * System has experienced an error.
     *
     * @param Throwable          $exception
     * @param null|string|Level  $level
     * @param null|string        $message
     * @param array              $context
     *
     * @return void
     */
    public static function exception(
        Throwable             $exception,
        null | string | Level $level = null,
        ?string               $message = null,
        array                 $context = [],
        ?bool                 $precision = null,
    ) : void {

        $exceptionMessage = $exception->getMessage();
        $exceptionLevel   = strstr( $exceptionMessage, ':', true );

        if ( $exceptionLevel ) {
            $exceptionLevel   = Level::fromName( $exceptionLevel );
            $exceptionMessage = substr( $exceptionMessage, strpos( $exceptionMessage, ':' ) + 1 );
        }
        else {
            $exceptionLevel = Level::ERROR;
        }

        $level   ??= $exceptionLevel;
        $message ??= $exceptionMessage;

        if ( !$message ) {
            $type    = get_debug_type( $exception );
            $line    = $exception->getLine();
            $file    = $exception->getFile();
            $message = "$type thrown at line $line in $file. Trace: " . $exception->getTraceAsString();
        }

        $context[ 'exception' ] = $exception;

        Log::entry( $level, $message, $context, $precision );
    }

    /**
     * # `7` Emergency | `600`
     * System is unusable.
     *
     * @param string|Stringable  $message
     * @param array              $context
     *
     * @return void
     */
    public static function emergency(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::EMERGENCY, $message, $context, $precision );
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
    public static function alert(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::ALERT, $message, $context, $precision );
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
    public static function critical(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::CRITICAL, $message, $context, $precision );
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
    public static function error(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::ERROR, $message, $context, $precision );
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
    public static function warning(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::WARNING, $message, $context, $precision );
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
    public static function notice(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::NOTICE, $message, $context, $precision );
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
    public static function info(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::INFO, $message, $context, $precision );
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
    public static function debug(
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        Log::entry( Level::DEBUG, $message, $context, $precision );
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
    public static function entry(
        string | Level      $level,
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
        ?bool               $precision = null,
    ) : void {
        if ( $precision ?? Log::$enablePrecision ) {
            $context += [ 'precision' => Log::resolvePrecisionDelta() ];
        }
        Log::getLogger()->log(
            Log::getLevel( $level )->name(),
            trim( $message ),
            $context,
        );
    }

    /**
     * Return the LoggerInterface instance.
     *
     * - If no LoggerInterface has been set, the default {@see Logger} will be used.
     *
     * @return LoggerInterface
     */
    private static function getLogger() : LoggerInterface {
        return Log::$logger ??= Log::setLogger( new Logger() );
    }

    private static function formatPrecisionDelta( ?int $hrTime ) : ?string {

        if ( !$hrTime ) {
            return null;
        }

        $time = (float) number_format( $hrTime / 1_000_000, strlen( (string) $hrTime ) );

        $decimals = 2;

        // If we have leading zeros
        if ( $time < 1 ) {
            $floating = substr( (string) $time, 2 );
            $decimals += strlen( $floating ) - strlen( ltrim( $floating, '0' ) );
        }

        $time = number_format( $time, $decimals, '.', '' );

        $time = str_pad( $time, 4, '0' );


        return $time ? $time . 'ms' : null;
    }

    private static function resolvePrecisionDelta() : array {

        // The current hrtime
        $precisionTime   = hrtime( true );
        $precisionDelta  = $precisionTime - Log::$precisionTimestamp;
        $precisionOffset = Log::$precisionPreviousEntry ? $precisionTime - Log::$precisionPreviousEntry : null;

        Log::$precisionPreviousEntry = $precisionTime;

        return [
            'hrTime'   => $precisionTime, // The current hrtime
            'hrDelta'  => $precisionDelta,
            'DeltaMs'  => Log::formatPrecisionDelta( $precisionDelta ),
            'OffsetMs' => Log::formatPrecisionDelta( $precisionOffset ),
        ];

    }

    /**
     * Resolve a given {@see \Psr\Log\LogLevel} or string to a valid {@see Level}.
     *
     * @param string|Level  $level
     *
     * @return Level
     */
    private static function getLevel( string | Level $level ) : Level {
        return $level instanceof Level ? $level : Level::fromName( $level );
    }
}