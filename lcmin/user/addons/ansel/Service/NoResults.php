<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use BuzzingPixel\Ansel\Utility\RegEx;

/**
 * Class NoResults
 */
class NoResults
{
	/**
	 * Parse no results
	 *
	 * @param string $tagData
	 * @param string $namespace
	 * @return bool|string
	 */
	public function parse($tagData, $namespace = 'img:')
	{
		if (is_string($tagData) &&
			preg_match(
				RegEx::noResults($namespace),
				$tagData,
				$matches
			)
		) {
			return $matches[1];
		}

		return false;
	}
}
