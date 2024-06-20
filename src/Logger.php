<?php

namespace Northrook;

use Psr\Log\{AbstractLogger, LoggerInterface, LoggerTrait};
use Countable, Stringable, BadMethodCallException, DateTimeInterface, ReflectionClass;
use Northrook\Logger\Log;
use function array_map, date, get_debug_type, gettype, is_null, is_object, is_scalar, str_contains, str_replace;

final class Logger extends AbstractLogger implements Countable
{
    use LoggerTrait;

    public const FORMAT_HUMAN   = 'd-m-Y H:i:s T';
    public const FORMAT_RFC3339 = 'Y-m-d\TH:i:sP';

    /**
     * @var array<array{string, string, array}>
     */
    private array $entries = [];

    public function log( $level, $message = null, array $context = [] ) : void {
        $this->entries[] = [ $level, $message, $context ];
    }

    /**
     * Check if there are any log entries.
     *
     * @return bool
     */
    public function hasLogs() : bool {
        return !empty( $this->entries );
    }

    /**
     * Return all log entries.
     *
     * @return array[]
     */
    public function getLogs( bool $resolve = false ) : array {
        if ( $resolve === true ) {
            $logs = [];

            foreach ( $this->entries as $entry ) {
                $entry[ 1 ] = $this->resolveLogMessage( null, $entry[1], $entry[2], false );
                $logs[]     = $entry;
            }
            return $logs;
        }

        return $this->entries;
    }

    /**
     * Return and clear all log entries.
     *
     * @return array[]
     */
    public function cleanLogs( bool $resolve = false ) : array {
        $logs          = $this->getLogs( $resolve );
        $this->entries = [];

        return $logs;
    }

    /**
     * Clear all log entries.
     *
     * @return void
     */
    public function clear() : void {
        $this->entries = [];
    }

    /**
     * Return the number of log entries.
     *
     * @return int
     */
    public function count() : int {
        return count( $this->entries );
    }

    /**
     * Import the logs from the given {@see LoggerInterface}.
     *
     * - Instances of {@see Logger} will be imported natively using {@see Logger::getLogs()}
     * - Instances of {@see LoggerInterface} will use the Reflection API to import the array from it
     *
     * @param LoggerInterface  $logger
     *
     * @return void
     */
    public function import( LoggerInterface $logger ) : void {

        // If we are given an arbitrary LoggerInterface, try to import the array from it
        // While it is incredibly unlikely that there will be more than one,
        // we will try to pick the most likely candidate
        if ( !$logger instanceof Logger ) {

            $logs            = [];
            $loggerInterface = new ReflectionClass( $logger );

            foreach ( $loggerInterface->getProperties() as $property ) {

                // Only look at array properties
                if ( (string) $property->getType() !== 'array' ) {
                    continue;
                }

                $value = $property->getValue( $logger );

                // Skip empty arrays, and arrays where the first element is not an array
                if ( empty( $value ) || !is_array( $value[ 0 ] ) ) {
                    continue;
                }

                // Add the potential array to the logs
                $logs[ $property->getName() ] = $value;
            }

            // If there is more than one array, remove keys that don't contain 'log'
            if ( count( $logs ) > 1 ) {
                $logs = array_filter( $logs, static fn ( $key ) => str_contains( $key, 'log' ), ARRAY_FILTER_USE_KEY );
            }

            // Pick the first array
            $importEntries = $logs[ array_key_first( $logs ) ] ?? [];
        }
        else {
            $importEntries = $logger->getLogs();
        }

        // Merge the arrays
        $this->entries = array_merge( $this->entries, $importEntries );
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
    public function printLogs( bool $clean = true, bool | string $timestamp = true ) : array {

        $entries = $clean ? $this->cleanLogs() : $this->getLogs();

        $logs = [];

        foreach ( $entries as [ $level, $message, $context ] ) {
            $logs[] = $this->resolveLogMessage( $level, $message, $context, $timestamp );
        }

        return $logs;
    }

    private function resolveLogMessage( ?string $level, string $message, array $context, $timestamp ) : string {

        $level = $level ?  ucfirst( $level ) . ': ' : null;

        if ( str_contains( $message, '{' ) && str_contains( $message, '}' ) ) {
            foreach ( $context as $key => $value ) {
                $value   = $this->resolveLogValue( $value );
                $message = str_replace( "{{$key}}", $value, $message );
            }
        }

        $timestamp = $timestamp === true ? DateTimeInterface::RFC3339 : $timestamp;
        $time      = $timestamp ? '[' . date( $timestamp ) . '] ' : '';

        return "{$time}{$level}{$message}";
    }

    private function resolveLogValue( mixed $value ) : string {
        return match ( true ) {
            is_scalar( $value ) ||
            $value instanceof Stringable || is_null( $value ) => (string) $value,
            $value instanceof DateTimeInterface               => $value->format( DateTimeInterface::RFC3339 ),
            is_object( $value )                               => '[object ' . get_debug_type( $value ) . ']',
            default                                           => '[' . gettype( $value ) . ']',
        };
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