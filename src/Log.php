<?php

declare ( strict_types = 1 );

namespace Northrook\Logger;

use Northrook\Logger\Log\Entry;
use Northrook\Logger\Log\Level;
use Psr\Log as Psr;
use Stringable;
use Throwable;

/**
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

    public static function Emergency( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Emergency', [$message, $context] );
    }

    public static function Alert( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Alert', [$message, $context] );
    }

    public static function Critical( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Critical', [$message, $context] );
    }

    public static function Error( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Error', [$message, $context] );
    }

    public static function Warning( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Warning', [$message, $context] );
    }

    public static function Notice( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Notice', [$message, $context] );
    }

    public static function Info( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Info', [$message, $context] );
    }

    public static function Debug( string | Stringable $message, array $context = [] ) : void {
        Log::entry( 'Debug', [$message, $context] );
    }

    public static function entry( string $level, array $arguments ) : void {

        if ( false === in_array( $level, Level::NAMES ) ) {
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