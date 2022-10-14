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
 * Class InternalTagParams
 *
 * @property int $width
 * @property int $height
 * @property bool $crop
 * @property string $background
 * @property bool $force_jpg
 * @property int $quality
 * @property bool $scale_up
 * @property int $cache_time
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class InternalTagParams extends Model
{
	/**
	 * Model properties
	 */
	protected $width;
	protected $height;
	protected $crop;
	protected $background;
	protected $force_jpg;
	protected $quality;
	protected $scale_up;
	protected $cache_time;

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'width' => 'int',
		'height' => 'int',
		'crop' => 'bool',
		'background' => 'string',
		'force_jpg' => 'bool',
		'quality' => 'int',
		'scale_up' => 'bool',
		'cache_time' => 'int',
	);

	/**
	 * Check if property is modified
	 *
	 * @param string $param
	 * @return bool
	 */
	public function checkIfPropertyModified($param)
	{
		// Get modified properties
		$modifiedProperties = $this->getModified();

		// Return boolean isset
		return isset($modifiedProperties[$param]);
	}

	/**
	 * crop setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__crop($val) // @codingStandardsIgnoreEnd
	{
		$this->customBoolSetter('crop', $val);
	}

	/**
	 * force_jpg setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__force_jpg($val) // @codingStandardsIgnoreEnd
	{
		$this->customBoolSetter('force_jpg', $val);
	}

	/**
	 * scale_up setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__scale_up($val) // @codingStandardsIgnoreEnd
	{
		$this->customBoolSetter('scale_up', $val);
	}

	/**
	 * Set boolean
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customBoolSetter($key, $val)
	{
		// Cast bool
		if ($val === 'y' ||
			$val === 'yes' ||
			$val === 'true' ||
			$val === true ||
			$val === 1 ||
			$val === '1'
		) {
			$val = true;
		} else {
			$val = false;
		}

		// Set property
		$this->setRawProperty($key, $val);
	}
}
