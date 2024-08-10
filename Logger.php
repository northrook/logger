<?php

declare( strict_types = 1 );

namespace Northrook;

use BadMethodCallException;
use Countable;
use DateTimeInterface;
use Psr\Log\{AbstractLogger, LoggerInterface, LoggerTrait};
use ReflectionClass;
use Stringable;
use function array_map;
use function date;
use function get_debug_type;
use function gettype;
use function is_null;
use function is_object;
use function is_scalar;
use function str_contains;
use function str_replace;

final class Logger extends AbstractLogger implements Countable
{
    use LoggerTrait;

    public const FORMAT_HUMAN   = 'd-m-Y H:i:s T';
    public const FORMAT_RFC3339 = 'Y-m-d\TH:i:sP';

    /**
     * @var array<array{string, string, array}>
     */
    private array $entries = [];

    public function __construct( ?LoggerInterface $import = null ) {
        if ( $import ) {
            $this->import( $import );
        }
    }

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
    public function getLogs( bool $resolve = false, bool $highlightContext = false ) : array {
        if ( $resolve === true ) {
            $logs = [];

            foreach ( $this->entries as $entry ) {
                $entry[ 1 ] = $this->resolveLogMessage( null, $entry[ 1 ], $entry[ 2 ], false, $highlightContext );
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
    public function cleanLogs( bool $resolve = false, bool $highlightContext = false ) : array {
        $logs          = $this->getLogs( $resolve, $highlightContext );
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

        // If the given LoggerInterface has a cleanLogs() method, use that
        if ( method_exists( $logger, 'cleanLogs' ) ) {
            $importEntries = $logger->cleanLogs();
        }
        // Otherwise, use Reflection to import the array
        else {

            $logs            = [];
            $loggerInterface = new ReflectionClass( $logger );

            /*
               While it is incredibly unlikely that there will be more
               than one property containing the logs, we will still
               ensure that we pick the most likely candidate.
            */
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

            // If there is more than one candidate, remove keys that don't contain 'log'
            if ( count( $logs ) > 1 ) {
                $logs = array_filter( $logs, static fn ( $key ) => str_contains( $key, 'log' ), ARRAY_FILTER_USE_KEY );
            }

            // Pick the first array
            $importEntries = $logs[ array_key_first( $logs ) ] ?? [];
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
    public function printLogs( bool $clean = true, bool | string $timestamp = false ) : array {

        $entries = $clean ? $this->cleanLogs() : $this->getLogs();

        $logs = [];

        foreach ( $entries as [ $level, $message, $context ] ) {
            $logs[] = $this->resolveLogMessage( $level, $message, $context, $timestamp );
        }

        return $logs;
    }

    private function resolveLogMessage(
        ?string $level,
        string  $message,
        array   $context,
        mixed   $timestamp,
        bool    $highlight = false,
    ) : string {

        $level = $level ? ucfirst( $level ) . ': ' : null;

        if ( str_contains( $message, '{' ) && str_contains( $message, '}' ) ) {
            foreach ( $context as $key => $value ) {
                $value = $this->resolveLogValue( $value );
                if ( $highlight ) {
                    $value = $this->highlight( $value );
                }
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

    private function highlight( string $string ) : string {

        if ( \str_contains( $string, '::' ) ) {
            $string = \str_replace( '::', '<span style="color: #fefefe">::</span>', $string );
        }

        $match = \strtolower( $string );
        if ( $match === 'true' ) {
            return '<b class="highlight-success">' . $string . '</b>';
        }
        if ( $match === 'false' ) {
            return '<b class="highlight-danger">' . $string . '</b>';
        }
        if ( $match === 'null' ) {
            return '<b class="highlight-warning">' . $string . '</b>';
        }

        if ( \strlen( $string ) < 12 || \is_numeric( $string ) ) {
            return '<b class="highlight">' . $string . '</b>';
        }

        return '<span class="highlight">' . $string . '</span>';
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