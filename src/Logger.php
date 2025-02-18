<?php

declare(strict_types=1);

namespace Northrook;

use BadMethodCallException;
use Psr\Log\{AbstractLogger, LoggerInterface, LoggerTrait};
use Northrook\Logger\Level;
use Stringable, Countable, ReflectionClass, InvalidArgumentException, DateTimeInterface;

/**
 * @phpstan-type  Entry array{level:string, message:string, context: array<array-key, mixed>}
 * @phpstan-type  Entries  array<int, Entry>
 */
final class Logger extends AbstractLogger implements Countable
{
    use LoggerTrait;

    public const string FORMAT_HUMAN = 'd-m-Y H:i:s T';

    public const string FORMAT_RFC3339 = 'Y-m-d\TH:i:sP';

    /** @var Entries */
    private array $entries = [];

    public function __construct( ?LoggerInterface $import = null )
    {
        if ( $import ) {
            $this->import( $import );
        }
    }

    /**
     * @param mixed                    $level
     * @param null|string|Stringable   $message
     * @param array<array-key, string> $context
     *
     * @return void
     */
    public function log( mixed $level, null|string|Stringable $message = null, array $context = [] ) : void
    {
        $level = match ( true ) {
            \is_numeric( $level ) => Level::from( (int) $level ),
            \is_string( $level )  => Level::fromName( $level ),
            default               => throw new InvalidArgumentException( 'Invalid log level.' ),
        };

        $this->entries[] = [
            'level'   => $level->name,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * Check if there are any log entries.
     *
     * @return bool
     */
    public function hasLogs() : bool
    {
        return ! empty( $this->entries );
    }

    /**
     * Return all {@see Logger::$entries}.
     *
     * @param bool $resolve
     * @param bool $highlightContext
     *
     * @return Entries
     */
    public function getLogs( bool $resolve = false, bool $highlightContext = false ) : array
    {
        if ( $resolve === true ) {
            /** @var Entries $logs */
            $logs = [];

            foreach ( $this->entries as $entry ) {
                $entry['message'] = $this->resolveLogMessage(
                    null,
                    $entry['message'],
                    $entry['context'],
                    false,
                    $highlightContext,
                );
                $logs[] = $entry;
            }
            return $logs;
        }

        return $this->entries;
    }

    /**
     * Return and clear all log entries.
     *
     * @param bool $resolve
     * @param bool $highlightContext
     *
     * @return Entries
     */
    public function cleanLogs( bool $resolve = false, bool $highlightContext = false ) : array
    {
        $logs          = $this->getLogs( $resolve, $highlightContext );
        $this->entries = [];

        return $logs;
    }

    /**
     * Clear all log entries.
     *
     * @return void
     */
    public function clear() : void
    {
        $this->entries = [];
    }

    /**
     * Return the number of log entries.
     *
     * @return int
     */
    public function count() : int
    {
        return \count( $this->entries );
    }

    /**
     * Import the logs from the given {@see LoggerInterface}.
     *
     * - Instances of {@see Logger} will be imported natively using {@see Logger::getLogs()}
     * - Instances of {@see LoggerInterface} will use the Reflection API to import the array from it
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function import( LoggerInterface $logger ) : void
    {
        // If the given LoggerInterface has a cleanLogs() method, use that
        if ( \method_exists( $logger, 'cleanLogs' ) ) {
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

                \assert( \is_array( $value ) && ! empty( $value ) );

                // Skip empty arrays, and arrays where the first element is not an array
                if ( ! \is_array( $value[0] ) ) {
                    continue;
                }

                // Add the potential array to the logs
                $logs[$property->getName()] = $value;
            }

            // If there is more than one candidate, remove keys that don't contain 'log'
            if ( \count( $logs ) > 1 ) {
                $logs = \array_filter( $logs, static fn( $key ) => \str_contains( $key, 'log' ), ARRAY_FILTER_USE_KEY );
            }

            // Pick the first array
            $importEntries = $logs[\array_key_first( $logs )] ?? [];
        }

        // Merge the arrays
        $this->entries = \array_merge( $this->entries, $importEntries );
    }

    /**
     * Print each log entry into an array, as human-readable strings.
     *
     * - Cleans the log by default.
     * - Does not include Timestamp by default.
     *
     * @param bool $clean
     * @param bool $timestamp
     *
     * @return string[]
     */
    public function printLogs( bool $clean = true, bool|string $timestamp = false ) : array
    {
        $entries = $clean ? $this->cleanLogs() : $this->getLogs();

        $logs = [];

        foreach ( $entries as $entry ) {
            $logs[] = $this->resolveLogMessage( null, $entry['message'], $entry['context'], $timestamp );
        }

        return $logs;
    }

    /**
     * @param ?string              $level
     * @param string               $message
     * @param array<string, mixed> $context
     * @param bool|string          $timestamp
     * @param bool                 $highlight
     *
     * @return string
     */
    private function resolveLogMessage(
        ?string     $level,
        string      $message,
        array       $context,
        bool|string $timestamp,
        bool        $highlight = false,
    ) : string {
        $level = $level ? \ucfirst( $level ).': ' : null;

        if ( \str_contains( $message, '{' ) && \str_contains( $message, '}' ) ) {
            foreach ( $context as $key => $value ) {
                $value = $this->resolveLogValue( $value );
                if ( $highlight ) {
                    $value = $this->highlight( $value );
                }
                $message = \str_replace( "{{$key}}", $value, $message );
            }

            \preg_match_all( '#(?<tag>{.+?})#', $message, $inline, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL );

            foreach ( $inline as $index => $match ) {
                $getNamed = static fn( $value, $key ) => \is_string( $key ) ? $value : false;
                $named    = \array_filter( $match, $getNamed, ARRAY_FILTER_USE_BOTH );

                if ( $named ) {
                    $inline[$index] = ['match' => \array_shift( $match ), ...$named];
                }
                else {
                    unset( $inline[$index] );
                }
            }

            foreach ( $inline as $tag ) {
                if ( ! $tag['tag'] || ! $tag['match'] ) {
                    continue;
                }

                $value = $this->resolveLogValue( \trim( $tag['tag'], '{}' ) );
                if ( $highlight ) {
                    $value = $this->highlight( $value );
                }

                $message = \str_replace( $tag['match'], $value, $message );
            }
        }

        $timestamp = $timestamp === true ? DateTimeInterface::RFC3339 : $timestamp;
        $time      = $timestamp ? '['.\date( $timestamp ).'] ' : '';

        return "{$time}{$level}{$message}";
    }

    private function resolveLogValue( mixed $value ) : string
    {
        return match ( true ) {
            \is_bool( $value ) => $value ? 'true' : 'false',
            \is_scalar( $value )
            || $value instanceof Stringable || \is_null( $value ) => (string) $value,
            $value instanceof DateTimeInterface                   => $value->format( DateTimeInterface::RFC3339 ),
            \is_object( $value )                                  => '[object '.\get_debug_type( $value ).']',
            default                                               => '['.\gettype( $value ).']',
        };
    }

    private function highlight( string $string ) : string
    {
        if ( \str_contains( $string, '::' ) ) {
            $string = \str_replace( '::', '<span style="color: #fefefe">::</span>', $string );
        }

        $match = \strtolower( $string );
        if ( $match === 'true' ) {
            return '<b class="highlight-success">'.$string.'</b>';
        }
        if ( $match === 'false' ) {
            return '<b class="highlight-danger">'.$string.'</b>';
        }
        if ( $match === 'null' ) {
            return '<b class="highlight-warning">'.$string.'</b>';
        }

        if ( \strlen( $string ) < 12 || \is_numeric( $string ) ) {
            return '<b class="highlight">'.$string.'</b>';
        }

        return '<span class="highlight">'.$string.'</span>';
    }

    /**
     * Dump the logs to the PHP error log if the logger is destroyed without first calling {@see cleanLogs()}.
     */
    public function __destruct()
    {
        foreach ( $this->printLogs() as $entry ) {
            \error_log( $entry );
        }
    }

    /**
     * LoggerInterfaces cannot be serialized for security reasons.
     *
     * @return array
     */
    public function __sleep() : array
    {
        throw new BadMethodCallException( LoggerInterface::class.' cannot be serialized' );
    }

    /**
     * LoggerInterfaces cannot be serialized for security reasons.
     */
    public function __wakeup() : void
    {
        throw new BadMethodCallException( LoggerInterface::class.' cannot be unserialized' );
    }
}
