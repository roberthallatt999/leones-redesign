<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Class Source
 *
 * @property string $url
 * @property string $path
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Source extends Model
{
	/**
	 * Model properties
	 */
	protected $url;
	protected $path;

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'url' => 'string',
		'path' => 'string'
	);

	/**
	 * Get urlsafe param
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getUrlSafeParam($name)
	{
		return rawurlencode($this->getProperty($name));
	}
}
