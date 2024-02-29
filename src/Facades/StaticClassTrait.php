<?php

namespace Northrook\Logger\Facades;

use LogicException;

/**
 * Static Class safeguarding.
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
trait StaticClassTrait
{
	private function __construct() {
		throw new LogicException(
			$this::class . " is using `StaticClassTrait`, and should not be instantiated directly."
		);
	}

	private function __clone() {
		throw new LogicException(
			$this::class . " is using `StaticClassTrait`, and should not be cloned."
		);
	}
}