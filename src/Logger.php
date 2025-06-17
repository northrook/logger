<?php

declare(strict_types=1);

namespace Core;

use Psr\Log\{LoggerInterface};
use Core\Logger\{
    LoggerMethods,
    Level,
    Log,
};
use Countable;
use Stringable;
use ReflectionClass;
use JetBrains\PhpStorm\Language;

final class Logger implements LoggerInterface, Countable
{
    use LoggerMethods;

    public const string FORMAT_HUMAN = 'd-m-Y H:i:s T';

    public const string FORMAT_RFC3339 = 'Y-m-d\TH:i:sP';

    /** @var Log[] */
    private array $entries = [];

    public function __construct(
        private readonly bool $precision = false,
        ?LoggerInterface      $import = null,
    ) {
        if ( $import ) {
            $this->import( $import );
        }
    }

    /**
     * @param int|Level|string         $level
     * @param null|string|Stringable   $message
     * @param array<array-key, string> $context
     *
     * @return void
     */
    public function log(
        mixed                  $level,
        #[Language( 'Smarty' )]
        null|string|Stringable $message = null,
        array                  $context = [],
    ) : void {
        $this->entries[] = new Log(
            $level,
            $message,
            $context,
            $this->precision ? \microtime( true ) : \time(),
        );
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
     * @param bool $highlight
     * @param bool $promoteBrackets
     *
     * @return ($resolve is true ? string[] : Log[])
     */
    public function getLogs(
        bool $resolve = false,
        bool $highlight = false,
        bool $promoteBrackets = false,
    ) : array {
        if ( $resolve === true ) {
            $logs = [];

            foreach ( $this->entries as $entry ) {
                $logs[] = $entry->resolve( $highlight, $promoteBrackets );
            }
            return $logs;
        }

        return $this->entries;
    }

    /**
     * Return and clear all log entries.
     *
     * @param bool $resolve
     * @param bool $highlight
     *
     * @return ($resolve is true ? string[] : Log[])
     */
    public function cleanLogs( bool $resolve = false, bool $highlight = false ) : array
    {
        $logs          = $this->getLogs( $resolve, $highlight );
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

                // Skip empty arrays and arrays where the first element is not an array
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

        foreach ( $importEntries as $entry ) {
            $this->entries[] = new Log( ...$entry );
        }
    }

    /**
     * Print each log entry into an array, as human-readable strings.
     *
     * - Cleans the log by default.
     * - Does not include Timestamp by default.
     *
     * @param bool $clean
     *
     * @return string[]
     */
    public function printLogs( bool $clean = true ) : array
    {
        return $clean ? $this->cleanLogs( true ) : $this->getLogs( true );
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
     * Serialized empty security reasons.
     *
     * @return array
     */
    public function __sleep() : array
    {
        return [];
    }
}
