<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

/**
 * Class Noop
 */
class Noop
{
	/**
	 * Get magic method
	 *
	 * @param string $name
	 * @return null
	 */
	public function __get($name)
	{
		return null;
	}

	/**
	 * Set magic method
	 *
	 * @param string $name
	 * @param mixed $val
	 */
	public function __set($name, $val)
	{
		return;
	}

	/**
	 * Call magic method
	 *
	 * @param string $name
	 * @param array $args
	 * @return null
	 */
	public function __call($name, $args)
	{
		return null;
	}
}
