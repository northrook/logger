<?php

declare( strict_types = 1 );

namespace Northrook\Logger;

use LogicException;

/**
 * A simple stopwatch timer.
 *
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook/logger
 * @todo    Provide link to documentation
 */
final class Timer
{

    public const FORMAT_S  = 1_000_000_000;
    public const FORMAT_MS = 1_000_000;
    public const FORMAT_US = 1_000;
    public const FORMAT_NS = 1;

    private static array $events = [];

    private function __construct() {
        throw new LogicException( $this::class . " should not be instantiated directly." );
    }

    private function __clone() {
        throw new LogicException( $this::class . "  should not be cloned." );
    }

    public static function start( string $name, bool $override = false ) : void {

        if ( isset( Timer::$events[ $name ] ) && !$override ) {
            Log::Warning( 'Timer already started {name}.', [ 'name' => $name ] );

            return;
        }

        Timer::$events[ $name ] = [ 'running' => hrtime( true ) ];

    }

    public static function stop( string $name ) : ?int {

        if ( !isset( Timer::$events[ $name ] ) && Timer::$events[ $name ][ 'running' ] ) {
            Log::Warning(
                'Timer not started {name}.',
                [
                    'name'   => $name,
                    'events' => Timer::$events,
                ],
            );

            return null;
        }

        $time = hrtime( true ) - Timer::$events[ $name ][ 'running' ];

        Timer::$events[ $name ] = $time;

        return $time;

    }

    public static function get(
        string     $event,
        int | bool $format = Timer::FORMAT_MS,
        bool       $stop = true,
    ) : null | string | float | int {
        if ( !isset( Timer::$events[ $event ] ) ) {
            Log::Warning(
                message : 'Timer requested, but not started: {event}.',
                context : [ 'event' => $event ],
            );

            return null;
        }

        $timer = Timer::$events[ $event ];

        if ( is_array( $timer ) && isset( $timer[ 'running' ] ) ) {
            if ( $stop ) {
                $timer = Timer::stop( $event );
            }
            else {

                Log::Warning(
                    message : 'Event {event} found, but it is currently running.',
                    context : [ 'event' => $event ],
                );

                return null;
            }
        }

        if ( $format === false ) {
            return $timer;
        }

        return ltrim( number_format( $timer / $format, 3 ), '0' );

    }

    public static function getAll() : array {
        return Timer::$events;
    }

}