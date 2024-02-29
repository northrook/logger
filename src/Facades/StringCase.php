<?php

namespace Northrook\Logger\Facades;

/**
 * Simple string case converter Enum.
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
enum StringCase : string
{
	case STRTOUPPER = 'strtoupper';
	case strtolower = 'strtolower';
	case Ucfirst = 'ucfirst';
	case Uc_Words = 'ucwords';
}
