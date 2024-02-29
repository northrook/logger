<?php

namespace Northrook\Logger\Log;

use Stringable;

final class Entry
{
	public readonly Timestamp $Timestamp;

	public function __toString() : string {
		if ( str_contains( $this->message, "{" ) && str_contains( $this->message, "}" ) ) {
			return preg_replace_callback(
				"/{(.+?)}/",
				function ( $matches ) {
					return $this->context[ $matches[ 1 ] ];
				},
				$this->message,
			);
		}
		return $this->message;
	}


	public function __construct(
		public readonly string | Stringable $message,
		public readonly array               $context,
		public readonly Level               $Level,
	) {
		$this->Timestamp = new Timestamp();
	}

}