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
 * Class PHPFileUpload
 *
 * @property string $name
 * @property string $type
 * @property string $tmp_name
 * @property int $error
 * @property int $size
 * @property string $anselCachePath
 * @property string $base64
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class PHPFileUpload extends Model
{
	/**
	 * Model properties
	 */
	protected $name;
	protected $type;
	protected $tmp_name;
	protected $error;
	protected $size;
	protected $anselCachePath;
	protected $base64;

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'name' => 'string',
		'type' => 'string',
		'tmp_name' => 'string',
		'error' => 'int',
		'size' => 'int',
		'anselCachePath' => 'string',
		'base64' => 'string'
	);

	/**
	 * Get accepted mime-types
	 */
	public static function getAcceptedMimeTypes()
	{
		return array(
			'image/jpeg',
			'image/gif',
			'image/png'
		);
	}

	/**
	 * Check if accepted mime type
	 *
	 * @param string $mimeType Mime type
	 * @return bool
	 */
	public static function isAcceptedMimeType($mimeType)
	{
		return in_array($mimeType, self::getAcceptedMimeTypes());
	}

	/**
	 * Validate upload
	 *
	 * @return bool
	 */
	public function isValidUpload()
	{
		// Make sure properties are set and upload is valid
		if (! $this->name ||
			! self::isAcceptedMimeType($this->type) ||
			! is_file($this->tmp_name) ||
			$this->error ||
			! $this->size
		) {
			return false;
		}

		return true;
	}
}
