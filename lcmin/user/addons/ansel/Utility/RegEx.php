<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Utility;

/**
 * Class RegEx
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class RegEx
{
	/**
	 * Host regex
	 *
	 * @return string
	 */
	public static function host()
	{
		return '/^((?:https?:)?\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})/';
	}

	/**
	 * Param regex
	 *
	 * @return string
	 */
	public static function param()
	{
		return '/\s?([^=]*)=["\']([^=]*)["\']/s';
	}

	/**
	 * No Results regex
	 *
	 * @param string $namespace
	 * @return string
	 */
	public static function noResults($namespace = 'img:')
	{
		$ld = LD;
		$rd = RD;
		return "#{$ld}if {$namespace}no_results{$rd}(.*?){$ld}/if{$rd}#s";
	}

	/**
	 * Between tags regex
	 *
	 * @return string
	 */
	public static function tagBetween()
	{
		return '((.+?)=([\"\'])(.+?)([\"\'])( *|\r\n*|\n*|\r*|\t*)*?)?';
	}

	/**
	 * Tag regex
	 *
	 * @param string $tag
	 * @param string $namespace
	 * @return string
	 */
	public static function tag($tag = 'tag', $namespace = 'img:')
	{
		return '/{' . $namespace . $tag . self::tagBetween() . '}/';
	}
}
