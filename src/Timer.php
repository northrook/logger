<?php

namespace Northrook\Logger;

use Log;

final class Timer
{
	use StaticClassTrait;

	public const FORMAT_S  = 1_000_000_000;
	public const FORMAT_MS = 1_000_000;
	public const FORMAT_US = 1_000;
	public const FORMAT_NS = 1;

	private static array $events = [];

	public static function start( string $name, bool $override = false ) : void {

		if ( isset( self::$events[ $name ] ) && !$override ) {
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