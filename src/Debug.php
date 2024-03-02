<?php

namespace Northrook\Logger;

use Northrook\Logger\Log\Level;

/**
 * Debug_backtrace helper class.
 *
 * @author Martin Nielsen <mn@northrook.com>
 * @version 0.1.5 ☑️
 */
final class Debug
{
	private readonly array $backtrace;

	public function __invoke() : array {
		return $this->backtrace;
	}

	private function __construct(
		int $limit = 0,
	) {
		$this->backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit );
	}

	public function getCaller( ?int $key = null ) : string {
		$backtrace = $key ? $this->backtrace[ $key ] : end( $this->backtrace );
		return $backtrace[ 'class' ] . $backtrace[ 'type' ] . $backtrace[ 'function' ];
	}

	public function getLine( ?int $key = null ) : int {
		$backtrace = $key ? $this->backtrace[ $key ] : end( $this->backtrace );
		return $backtrace[ 'line' ];
	}

	public function getFile( ?int $key = null ) : string {
		$backtrace = $key ? $this->backtrace[ $key ] : end( $this->backtrace );
		return $backtrace[ 'file' ];
	}

	public function log( ?Level $level = null ) : void {
		
		$method = $level->name;

		Log::$method(
			'Debug Backtrace.',
			[
				...$this->backtrace,
			],
		);
	}

	public static function backtrace( int $limit = 0 ) : Debug {
		return new self( $limit );
	}
}