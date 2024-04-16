<?php

declare( strict_types = 1 );

namespace Northrook\Logger\Log;

use DateTimeImmutable;
use DateTimeZone;
use Northrook\Logger\Log;

/**
 * Timestamp for {@see Log\Entry}.
 *
 * @author  Martin Nielsen <mn@northrook.com>
 *
 * @link    https://github.com/northrook/logger
 * @todo    Provide link to documentation
 */
class Timestamp
{


	public const FORMAT_HUMAN            = 'd-m-Y H:i:s';
	public const FORMAT_W3C              = 'Y-m-d\TH:i:sP';
	public const FORMAT_RFC3339          = 'Y-m-d\TH:i:sP';
	public const FORMAT_RFC3339_EXTENDED = 'Y-m-d\TH:i:s.vP';

	private const DEFAULT_TIMEZONE = 'Europe/London';

	private readonly DateTimeImmutable $DateTime;
	private readonly DateTimeZone      $TimeZone;
	public readonly int                $timestamp;

	public function __construct(
		null | string | int $timestamp = null,
		DateTimeZone        $timezone = null,
	) {
		$this->timestamp = $this::getUnixTimestamp( $timestamp );
		$this->TimeZone = $timezone ?? new DateTimeZone( Timestamp::DEFAULT_TIMEZONE );
	}

	public function __toString() : string {
		return $this->format();
	}

	/**
	 * @link https://secure.php.net/manual/en/datetime.format.php
	 * @param  string  $format
	 * Format accepted by  {@link https://secure.php.net/manual/en/function.date.php date()}.
	 *
	 * @return string
	 */
	public function format( string $format = Timestamp::FORMAT_HUMAN ) : string {
		return $this->getDateTime()->format( $format );
	}

	public function getDateTime( ?DateTimeZone $timezone = null ) : DateTimeImmutable {

		if ( isset( $this->DateTime ) ) {
			return $this->DateTime;
		}

		$this->DateTime = ( new DateTimeImmutable() )
			->setTimezone( $timezone ?? $this->TimeZone )
			->setTimestamp( $this->timestamp )
		;

		return $this->DateTime;
	}

	/** Formats provided datetime string to Unix Timestamp
	 *
	 * If malformed or null string is provided; return `time()`
	 *
	 * @param  string|int|null  $time
	 *
	 * @return int Unix Timestamp
	 *
	 * @see      time(), DateTime::setTimestamp
	 */
	public static function getUnixTimestamp( string | int | null $time ) : int {

		$isNumeric = preg_match( '/^\d+$/', (string) $time );

		return $isNumeric ?: strtotime( (string) $time ) ?: time();
	}


}