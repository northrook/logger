<?php

declare( strict_types = 1 );

namespace Northrook\Logger;

use Northrook\Logger\Log\Level;

/**
 * Debug_backtrace helper class.
 *
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook/logger
 * @todo    Provide link to documentation
 */
final class Debug
{
    private array $backtrace;
    private array $caller;

    public function __invoke() : array {
        return $this->backtrace;
    }

    private function __construct(
        int $limit = 0,
    ) {
        $this->backtrace = array_slice(
            debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit + 3 ),
            2,
        );
        $this->caller    = array_pop( $this->backtrace );
    }

    public function getCaller( ?int $key = null ) : string {
        $backtrace = $key ? $this->backtrace[ $key ] : $this->caller;
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
            [ ...$this->backtrace, ],
        );
    }

    public static function backtrace( int $limit = 0 ) : Debug {
        return new self( $limit );
    }

    public static function traceLog( bool $stopAtHttpKernel = true ) : array {

        $contains = static function (
            string $string,
            array  $needle,
        ) : array {

            $contains = [];
            $search   = strtolower( $string );

            foreach ( $needle as $value ) {
                if ( substr_count( $search, strtolower( $value ) ) ) {
                    $contains[] = $value;
                }
            }

            return $contains;
        };

        $backtrace = [];
        foreach ( array_slice( debug_backtrace(), 2 ) as $index => $trace ) {
            if ( array_key_exists( 'file', $trace ) ) {
                $key = strtr( $trace[ 'file' ], '\\', '/' );
                if ( $stopAtHttpKernel && str_ends_with( $key, 'vendor/symfony/http-kernel/HttpKernel.php' ) ) {
                    break;
                }
                $has    = $contains( $key, [ 'src/', 'var/', 'public/', 'vendor/' ] );
                $needle = array_pop( $has );

                if ( ! $needle ) {
                    continue;
                }

                $index  = strstr( $key, $needle );

                if ( strlen( $index ) > 42 ) {
                    $index = '..' . strstr( substr( $index, -42 ), '/' ) ?: strrchr( $index, '/' );
                }
            }
            $backtrace[ $index ] = $trace;
        }
        return $backtrace;
    }
}