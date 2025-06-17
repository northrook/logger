<?php

declare(strict_types=1);

namespace Core\Logger;

use Core\Logger;
use DateTimeInterface;
use Stringable;
use DateTimeImmutable;
use BadMethodCallException;

/**
 * @internal created by {@see Logger}
 */
final readonly class Log implements Stringable
{
    public Level $level;

    public string $message;

    /**
     * @param int|Level|string       $level     = [ 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' ][$any]
     * @param null|string|Stringable $message
     * @param array<string, mixed>   $context
     * @param null|float|int         $timestamp
     */
    public function __construct(
        int|Level|string       $level,
        null|string|Stringable $message,
        public array           $context = [],
        public null|float|int  $timestamp = null,
    ) {
        $this->level = Level::resolve( $level );

        $message = \trim( (string) $message );

        $this->message = $message
                ?: 'Unknown log event at '.( new DateTimeImmutable() )->format( 'r' );
    }

    public function __toString() : string
    {
        return $this->resolve();
    }

    public function resolve(
        bool $highlight = false,
        bool $promoteBrackets = false,
    ) : string {
        $message = $this->message;

        if ( \str_contains( $message, '{' ) && \str_contains( $message, '}' ) ) {
            foreach ( $this->context as $key => $value ) {
                $value = $this->resolveLogValue( $value );
                if ( $highlight ) {
                    $value = $this->highlight( $value );
                }
                $message = \str_replace( "{{$key}}", $value, $message );
            }

            if ( $promoteBrackets ) {
                foreach ( $this->inlineBrackets( $message ) as $tag ) {
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
        }

        return $message;
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

    /**
     * @param string $message
     *
     * @return string[][]
     */
    private function inlineBrackets( string $message ) : array
    {
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

        return $inline;
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
     * @return array{
     *     level: string,
     *     message: string,
     *     context: array<string,mixed>,
     *     timestamp: null|float|int
     * }
     */
    public function toArray() : array
    {
        return [
            'level'     => $this->level->name(),
            'message'   => $this->message,
            'context'   => $this->context,
            'timestamp' => $this->timestamp,
        ];
    }

    public function __serialize() : array
    {
        throw new BadMethodCallException( 'Log entries cannot be serialized.' );
    }

    public function __clone() : void
    {
        throw new BadMethodCallException( 'Log entries cannot be cloned.' );
    }

    public function __unserialize( array $data ) : void
    {
        throw new BadMethodCallException( 'Log entries cannot be unserialized.' );
    }
}
