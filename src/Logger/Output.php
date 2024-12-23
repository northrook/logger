<?php

declare(strict_types=1);

namespace Northrook\Logger;

use Northrook\Logger;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type  Entry array{level:string, message:string, context: array<array-key, mixed>}
 * @phpstan-type  Entries  array<int, Entry>
 *
 * @method static self dump( LoggerInterface $logger )
 */
final class Output
{
    private const string STYLESHEET = <<<'CSS'
            pre.log-dump {
                --padding: 8px;
                display: grid;
                grid-template-areas: "level timestamp message"; /* 3 columns */
                grid-template-columns: max-content max-content 1fr; /* 2 sizes */
                padding: 0 var(--padding);
                column-gap: 1ch;
                
                color: #fefefe;
                background-color: #15191e80;

                font-family: "Dev Workstation", monospace !important;
                font-size: 15px;
                line-height: 1.5;
                letter-spacing: .05ch;
                overflow: hidden;
            }

            pre.log-dump div.log-entry {
                display: contents;
            }
            
            body pre.log-dump div[class*="log-column-"] {
                white-space: normal;
                position: relative;
                padding-top: 5px;
                padding-bottom: 5px;
            }

            body pre.log-dump .log-column-timer {
                user-select: none;
                position: relative;
                display: flex;
                justify-content: flex-end;
                width: 100%;
                height: fit-content;
                overflow: hidden;
                padding-left: 1.125ch;
            }
            
            
            body pre.log-dump .log-column-timer.has-offset:hover {
                cursor: progress;
            }

            body pre.log-dump .log-column-timer.has-offset > span {
                display: inline-block;
                transition: opacity 100ms ease-in-out, transform 150ms ease-in-out;
                transition-delay: 50ms;
            }
            
            
            body pre.log-dump .log-column-timer.has-offset:hover > span {
                transition-delay: 0ms;
            }

            body pre.log-dump .log-column-timer > span.log-precision-delta {
                position: relative;
                right: .5rem;
                opacity: 1;
                transform: translateY( 0 );
                color: #bfacac;
            }

            body pre.log-dump .log-column-timer > span.log-precision-offset {
                position: absolute;
                right: .5rem;
                opacity: 0;
                transform: translateY( -100% );
                color: #a2b3ef;
            }

            body pre.log-dump .log-column-timer.has-offset::before {
                content: "+";
                position: absolute;
                left: 0;
                color: lightgreen;
                opacity: .25;
                transition: opacity 100ms ease-in-out ;
            }

            body pre.log-dump .log-column-timer.has-offset:hover::before {
                opacity: 1;
            }

            body pre.log-dump .log-column-timer.has-offset:hover > span.log-precision-delta {
                opacity: 0;
                transform: translateY( 100% );
            }

            body pre.log-dump .log-column-timer.has-offset:hover > span.log-precision-offset {
                opacity: 1;
                transform: translateY( 0 );
            }

            body pre.log-dump .log-column-level {
                user-select: none;
                width: fit-content;
                height: calc(100% - 10px);
            }

            body pre.log-dump .log-entry:nth-child(even) .log-column-level::before {
                content: "";
                position: absolute;
                top: 0;
                bottom: 0;
                left: calc(var(--padding) * -1);
                right: calc( var(--padding) + 100vw * -1 );
                background-color: #15191e50;
                z-index: -5;
            }
            
            body pre.log-dump .highlight {
            display: inline-block;
                color: #52dfff;
            }
            
            body pre.log-dump .highlight-success {
                color: #45d5bd;
            }
            
            body pre.log-dump .highlight-warning {
                color: #d69045;
            }
            
            body pre.log-dump .highlight-danger {
                color: #d55645;
            }


            body pre.log-dump .log-level.info {
                color: #2fe02f;
            }

            body pre.log-dump .log-level.debug {
                color: #e6f2ff;
            }

            body pre.log-dump .log-level.notice {
                color: #2fa5e0;
            }

            body pre.log-dump .log-level.warning {
                color: #e0992f;
            }

            body pre.log-dump .log-level.error {
                color: #e12f2f;
            }

            body pre.log-dump .log-level.critical {
                font-weight: bold;
                color: #ff0000;
            }

            body pre.log-dump .log-level.alert {
                color: white;
                background-color: #e12f2f;
            }

            body pre.log-dump .log-level.emergency {
                color: #ff0000;
            }

            body pre.log-dump .log-column-message  {
                position: relative;
            }
            
            body pre.log-dump .log-column-message.emergency::before {
                content: "";
                position: absolute;
                left: -50vw;
                right: -50vw;
                height: 100%;
                background-color: black;
                z-index: -1;
            }

        CSS;

    /** @var Entries */
    public readonly array $log;

    private function __construct(
        LoggerInterface $logger,
        bool            $clear = true,
    ) {
        $this->log = ( new Logger( import : $logger ) )->cleanLogs( $clear, true );
    }

    /**
     * @param string                    $name
     * @param array<0, LoggerInterface> $arguments
     *
     * @return void
     */
    public static function __callStatic( string $name, array $arguments )
    {
        if ( 'dump' === $name ) {
            ( new Output( ...$arguments ) )->output();
        }
    }

    private function output() : void
    {
        $output = [];

        foreach ( $this->log as $log ) {

            $level = \strtolower( $log['level'] );

            $delta     = $log['context']['precision.deltaMs']  ?? null;
            $offset    = $log['context']['precision.offsetMs'] ?? null;
            $delta     = $delta ? '<span class="log-precision-delta">'.$delta.'</span>' : null;
            $offset    = $offset ? '<span class="log-precision-offset">'.$offset.'</span>' : null;
            $hasOffset = $offset ? ' has-offset' : null;

            $message = '<span class="log-message">'.$log['message'].'</span>';

            $output[] = <<<HTML
                <div class="log-entry">
                    <div class="log-column-level"><span class="log-level {$level}">{$level}</span></div>
                    <div class="log-column-timer{$hasOffset}">{$delta}{$offset}</div>
                    <div class="log-column-message {$level}">{$message}</div>
                </div>
                HTML;
        }

        $timestamp = \date( 'Y-m-d H:i:s' );

        echo '<style>'.Output::STYLESHEET.'</style>';
        echo '<pre class="log-dump" data-timestamp="'.$timestamp.'">'.\implode( "\n", $output ).'</pre>';
    }
}
