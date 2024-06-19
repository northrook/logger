<?php

declare ( strict_types = 1 );

namespace Northrook\Logger;

use JetBrains\PhpStorm\Language;
use LogicException;
use Northrook\Logger;
use Psr\Log\LoggerInterface;
use Stringable, Throwable;
use function  strstr, strpos, substr, get_debug_type;


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

    public function __construct( ?LoggerInterface $logger = null ) {

        if ( isset( Log::$logger ) ) {
            throw new LogicException( Log::class . ' cannot be instantiated more than once.' );
        }

        Log::$logger = $logger;
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

        Log::entry(
            $level,
            trim($message),
            $context,
        );
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
    public static function alert(
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
    public static function critical(
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
    public static function error(
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
    public static function warning(
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
    public static function notice(
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
    public static function info(
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
    public static function debug(
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
    public static function entry(
        string | Level      $level,
        #[Language( 'Smarty' )]
        string | Stringable $message,
        array               $context = [],
    ) : void {
        Log::getLogger()->log( Log::getLevel( $level )->name(), $message, $context );
    }

    /**
     * Return the LoggerInterface instance.
     *
     * - If no LoggerInterface has been set, the default {@see Logger} will be used.
     *
     * @return LoggerInterface
     */
    public static function getLogger() : LoggerInterface {
        return Log::$logger ??= new Logger();
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