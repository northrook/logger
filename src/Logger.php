<?php

namespace Northrook;

use Psr\Log\{AbstractLogger, LoggerInterface, LoggerTrait};
use Countable, Stringable, BadMethodCallException, DateTimeInterface;
use function array_map, date, get_debug_type, gettype, is_null, is_object, is_scalar, str_contains, str_replace;

final class Logger extends AbstractLogger implements Countable
{
    use LoggerTrait;

    /**
     * @var array<array{string, string, array}>
     */
    private array $entries = [];

    public function log( $level, $message = null, array $context = [] ) : void {
        $this->entries[] = [ $level, $message, $context ];
    }

    public function hasLogs() : bool {
        return !empty( $this->entries );
    }

    public function clear() : void {
        $this->entries = [];
    }

    public function count() : int {
        return count( $this->entries );
    }

    public function getLogs() : array {
        return $this->entries;
    }

    public function cleanLogs() : array {
        $logs          = $this->entries;
        $this->entries = [];

        return $logs;
    }

    /**
     * Print each log entry into an array, as human-readable strings.
     *
     * - Cleans the log by default.
     * - Does not include Timestamp by default.
     *
     * @param bool  $clean
     * @param bool  $timestamp
     *
     * @return array
     */
    public function printLogs( bool $clean = true, bool $timestamp = false ) : array {

        $entries = $clean ? $this->cleanLogs() : $this->getLogs();

        $logs = [];

        foreach ( $entries as [ $level, $message, $context ] ) {
            $level = ucfirst( $level );

            if ( str_contains( $message, '{' ) && str_contains( $message, '}' ) ) {
                foreach ( $context as $key => $value ) {
                    $value   = match ( true ) {
                        $this->isScalar( $value )   => (string) $value,
                        $this->isDateTime( $value ) => $value->format( DateTimeInterface::RFC3339 ),
                        is_object( $value )         => '[object ' . get_debug_type( $value ) . ']',
                        default                     => '[' . gettype( $value ) . ']',
                    };
                    $message = str_replace( "{{$key}}", $value, $message );
                }
            }

            $time = $timestamp ? '[' . date( DateTimeInterface::RFC3339 ) . '] ' : '';

            $logs[] = "{$time}{$level}: {$message}";
        }

        return $logs;

    }

    private function isScalar( mixed $value ) : bool {
        return is_scalar( $value ) || $value instanceof Stringable || is_null( $value );
    }

    private function isDateTime( mixed $value ) : bool {
        return $value instanceof DateTimeInterface;
    }

    /**
     * Dump the logs to the PHP error log if the logger is destroyed without first calling {@see cleanLogs()}.
     */
    public function __destruct() {
        array_map( '\error_log', $this->printLogs() );
    }

    /**
     * LoggerInterfaces cannot be serialized for security reasons.
     *
     * @return array
     */
    public function __sleep() : array {
        throw new BadMethodCallException( LoggerInterface::class . ' cannot be serialized' );
    }

    /**
     * LoggerInterfaces cannot be serialized for security reasons.
     */
    public function __wakeup() : void {
        throw new BadMethodCallException( LoggerInterface::class . ' cannot be unserialized' );
    }
}