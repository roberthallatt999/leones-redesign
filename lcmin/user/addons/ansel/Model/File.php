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
 * Class File
 *
 * @property string $location_type
 * @property string|int $location_identifier
 * @property int $file_id
 * @property string $url
 * @property string $filepath
 * @property string $filename
 * @property string $basename
 * @property string $extension
 * @property string $dirname
 * @property int $filesize
 * @property int $width
 * @property int $height
 * @property string $file_description
 * @property string $file_credit,
 * @property string $file_location
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class File extends Model
{
	/**
	 * Model properties
	 */
	protected $location_type;
	protected $location_identifier;
	protected $file_id;
	protected $url;
	protected $filepath;
	protected $filename;
	protected $basename;
	protected $extension;
	protected $dirname;
	protected $filesize;
	protected $width;
	protected $height;
	protected $file_description;
	protected $file_credit;
	protected $file_location;

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'file_id' => 'int',
		'url' => 'string',
		'filepath' => 'string',
		'filename' => 'string',
		'basename' => 'string',
		'extension' => 'string',
		'dirname' => 'string',
		'filesize' => 'int',
		'width' => 'int',
		'height' => 'int',
		'file_description' => 'string',
		'file_credit' => 'string',
		'file_location' => 'string'
	);

	/**
	 * Predefined location types
	 */
	private $predefinedLocationTypes = array(
		'ee',
		'treasury',
		'assets'
	);

	/**
	 * location_type setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__location_type($val) // @codingStandardsIgnoreEnd
	{
		if (! in_array($val, $this->predefinedLocationTypes)) {
			return;
		}

		$this->setRawProperty('location_type', $val);
	}

	/**
	 * location_identifier setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__location_identifier($val) // @codingStandardsIgnoreEnd
	{
		if (is_numeric($val)) {
			$val = (int) $val;
		}

		$this->setRawProperty('location_identifier', $val);
	}

	/**
	 * Set file path and related properties
	 *
	 * @param string $filepath
	 */
	public function setFileLocation($filepath)
	{
		$pathinfo = pathinfo($filepath);

		$this->setRawProperty('filepath', $filepath);
		$this->setRawProperty('filename', $pathinfo['filename']);
		$this->setRawProperty('basename', $pathinfo['basename']);
		$this->setRawProperty('extension', $pathinfo['extension']);
		$this->setRawProperty('dirname', $pathinfo['dirname']);
	}

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
