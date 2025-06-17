<?php

namespace Core\Logger;

use JetBrains\PhpStorm\Language;
use Stringable;
use Throwable;

trait LoggerMethods
{
    /**
     * Logs with an arbitrary level.
     *
     * @param Level|string            $level   = [ 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' ][$any]
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    abstract public function log(
        string|Level      $level,
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void;

    /**
     * # `E` Exception
     * System has experienced an error.
     *
     * @param Throwable               $exception
     * @param null|Level|string       $level
     * @param null|string             $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function exception(
        Throwable         $exception,
        null|string|Level $level = null,
        #[Language( 'Smarty' )]
        ?string           $message = null,
        array             $context = [],
    ) : void {
        $exceptionMessage = $exception->getMessage();
        $exceptionLevel   = \strstr( $exceptionMessage, ':', true );

        if ( $exceptionLevel !== false ) {
            $exceptionMessage = \substr( $exceptionMessage, \strpos( $exceptionMessage, ':' ) + 1 );
        }

        try {
            $exceptionLevel = $exceptionLevel ? Level::fromName( $exceptionLevel ) : Level::ERROR;
        }
        catch ( Throwable ) {
            $exceptionLevel = Level::ERROR;
        }

        $level   ??= $exceptionLevel;
        $message ??= $exceptionMessage;

        if ( ! $message ) {
            $type    = \get_debug_type( $exception );
            $line    = $exception->getLine();
            $file    = $exception->getFile();
            $message = "{$type} thrown at line {$line} in {$file}. Trace: ".$exception->getTraceAsString();
        }

        $context['exception'] = $exception;

        $this->log( $level, $message, $context );
    }

    /**
     * # `7` Emergency | `600`
     * System is unusable.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function emergency(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::EMERGENCY, $message, $context );
    }

    /**
     * # `6` Alert | `550`.
     *
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function alert(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::ALERT, $message, $context );
    }

    /**
     * # `5` Critical | `500`.
     *
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function critical(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::CRITICAL, $message, $context );
    }

    /**
     * # `4` Error | `400`.
     *
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function error(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::ERROR, $message, $context );
    }

    /**
     * # `3` Warning | `300`.
     *
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function warning(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::WARNING, $message, $context );
    }

    /**
     * # `2` Notice | `250`.
     *
     * Normal but significant events.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function notice(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::NOTICE, $message, $context );
    }

    /**
     * # `1` Info | `200`.
     *
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function info(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::INFO, $message, $context );
    }

    /**
     * # `0` Debug | `100`.
     *
     * Detailed debug information.
     *
     * @param string|Stringable       $message
     * @param array<array-key, mixed> $context
     *
     * @return void
     */
    public function debug(
        #[Language( 'Smarty' )]
        string|Stringable $message,
        array             $context = [],
    ) : void {
        $this->log( Level::DEBUG, $message, $context );
    }
}
