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
 * Class ImagesTagParams
 *
 * @property int|array $image_id Ansel image ID
 * @property int|array $not_image_id
 *
 * @property int|array $site_id
 * @property int|array $not_site_id
 *
 * @property int|array $source_id Usually channel id, could be low variables id
 * @property int|array $not_source_id
 *
 * @property int|array $content_id Usually entry_id, could be low variables id
 * @property int|array $not_content_id
 *
 * @property int|array $field_id
 * @property int|array $not_field_id
 *
 * @property string|array $content_type channel|grid|low_variables
 * @property string|array $not_content_type
 *
 * @property int|array $row_id Grid/Bloqs row_id
 * @property int|array $not_row_id
 *
 * @property int|array $col_id Grid/Bloqs col_id
 * @property int|array $not_col_id
 *
 * @property int|array $file_id EE file id
 * @property int|array $not_file_id
 *
 * @property string|array $original_location_type ee|assets|treasury
 * @property string|array $not_original_location_type
 *
 * @property int|array $original_file_id EE file id
 * @property int|array $not_original_file_id
 *
 * @property string|array $upload_location_type ee|assets|treasury
 * @property string|array $not_upload_location_type
 *
 * @property string|array $upload_location_id
 * @property string|array $not_upload_location_id
 *
 * @property string|array $filename
 * @property string|array $not_filename
 *
 * @property string|array $extension
 * @property string|array $not_extension
 *
 * @property string|array $original_extension
 * @property string|array $not_original_extension
 *
 * @property int $filesize Prefix with > or <
 *
 * @property int $original_filesize Prefix with > or <
 *
 * @property int $width Prefix with > or <
 *
 * @property int $height Prefix with > or <
 *
 * @property string|array $title
 * @property string|array $not_title
 *
 * @property string|array $caption
 * @property string|array $not_caption
 *
 * @property int|array $member_id
 * @property int|array $not_member_id
 *
 * @property string $position Prefix with > or <
 *
 * @property bool $cover_first
 *
 * @property bool $cover_only
 *
 * @property bool $skip_cover
 *
 * @property bool $show_disabled
 *
 * @property string $namespace
 *
 * @property int $limit
 *
 * @property int $offset
 *
 * @property string|array $order_by date:desc|order:asc
 *
 * @property bool $random
 *
 * @property bool $count
 *
 * @property bool $manipulations
 *
 * @property string $host
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class ImagesTagParams extends Model
{
	/**
	 * Model properties
	 */

	protected $image_id;
	protected $not_image_id;

	protected $site_id;
	protected $not_site_id;

	protected $source_id;
	protected $not_source_id;

	protected $content_id;
	protected $not_content_id;

	protected $field_id;
	protected $not_field_id;

	protected $content_type;
	protected $not_content_type;

	protected $row_id;
	protected $not_row_id;

	protected $col_id;
	protected $not_col_id;

	protected $file_id;
	protected $not_file_id;

	protected $original_location_type;
	protected $not_original_location_type;

	protected $original_file_id;
	protected $not_original_file_id;

	protected $upload_location_type;
	protected $not_upload_location_type;

	protected $upload_location_id;
	protected $not_upload_location_id;

	protected $filename;
	protected $not_filename;

	protected $extension;
	protected $not_extension;

	protected $original_extension;
	protected $not_original_extension;

	protected $filesize;

	protected $original_filesize;

	protected $width;

	protected $height;

	protected $title;
	protected $not_title;

	protected $caption;
	protected $not_caption;

	protected $member_id;
	protected $not_member_id;

	protected $position;

	protected $cover_first;

	protected $cover_only;

	protected $skip_cover;

	protected $show_disabled;

	protected $namespace;

	protected $limit;

	protected $offset;

	protected $order_by;

	protected $random;

	protected $sort;

	protected $count;

	protected $manipulations;

	protected $host;

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'image_id' => 'intArray',
		'not_image_id' => 'intArray',
		'site_id' => 'intArray',
		'not_site_id' => 'intArray',
		'source_id' => 'intArray',
		'not_source_id' => 'intArray',
		'content_id' => 'intArray',
		'not_content_id' => 'intArray',
		'field_id' => 'intArray',
		'not_field_id' => 'intArray',
		'content_type' => 'stringArray',
		'not_content_type' => 'stringArray',
		'row_id' => 'intArray',
		'not_row_id' => 'intArray',
		'col_id' => 'intArray',
		'not_col_id' => 'intArray',
		'file_id' => 'intArray',
		'not_file_id' => 'intArray',
		'original_location_type' => 'stringArray',
		'not_original_location_type' => 'stringArray',
		'original_file_id' => 'intArray',
		'not_original_file_id' => 'intArray',
		'upload_location_type' => 'stringArray',
		'not_upload_location_type' => 'stringArray',
		'upload_location_id' => 'intArray',
		'not_upload_location_id' => 'intArray',
		'filename' => 'stringArray',
		'not_filename' => 'stringArray',
		'extension' => 'stringArray',
		'not_extension' => 'stringArray',
		'original_extension' => 'stringArray',
		'not_original_extension' => 'stringArray',
		'filesize' => 'string',
		'original_filesize' => 'string',
		'width' => 'string',
		'height' => 'string',
		'title' => 'stringArray',
		'not_title' => 'stringArray',
		'caption' => 'stringArray',
		'not_caption' => 'stringArray',
		'member_id' => 'intArray',
		'not_member_id' => 'intArray',
		'position' => 'string',
		'cover_first' => 'customBool',
		'cover_only' => 'customBool',
		'skip_cover' => 'customBool',
		'show_disabled' => 'customBool',
		'namespace' => 'namespace',
		'limit' => 'int',
		'offset' => 'int',
		'order_by' => 'orderBy',
		'random' => 'customBool',
		'sort' => 'string',
		'count' => 'bool',
		'manipulations' => 'bool',
		'host' => 'host'
	);

	/**
	 * Get types
	 */
	public function getPropertyTypes()
	{
		return self::$_typed_columns;
	}

	/**
	 * Filter properties
	 */
	// @codingStandardsIgnoreStart
	protected static $_filter_properties = array( // @codingStandardsIgnoreEnd
		'image_id' => 'IN',
		'not_image_id' => 'NOT IN',
		'site_id' => 'IN',
		'not_site_id' => 'NOT IN',
		'source_id' => 'IN',
		'not_source_id' => 'NOT IN',
		'content_id' => 'IN',
		'not_content_id' => 'NOT IN',
		'field_id' => 'IN',
		'not_field_id' => 'NOT IN',
		'content_type' => 'IN',
		'not_content_type' => 'NOT IN',
		'row_id' => 'IN',
		'not_row_id' => 'NOT IN',
		'col_id' => 'IN',
		'not_col_id' => 'NOT IN',
		'file_id' => 'IN',
		'not_file_id' => 'NOT IN',
		'original_location_type' => 'IN',
		'not_original_location_type' => 'NOT IN',
		'original_file_id' => 'IN',
		'not_original_file_id' => 'NOT IN',
		'upload_location_type' => 'IN',
		'not_upload_location_type' => 'NOT IN',
		'upload_location_id' => 'IN',
		'not_upload_location_id' => 'NOT IN',
		'filename' => 'IN',
		'not_filename' => 'NOT IN',
		'extension' => 'IN',
		'not_extension' => 'NOT IN',
		'original_extension' => 'IN',
		'not_original_extension' => 'NOT IN',
		'filesize' => 'GT',
		'original_filesize' => 'GT',
		'width' => 'GT',
		'height' => 'GT',
		'title' => 'IN',
		'not_title' => 'NOT IN',
		'caption' => 'IN',
		'not_caption' => 'NOT IN',
		'member_id' => 'IN',
		'not_member_id' => 'NOT IN',
		'position' => 'GT'
	);

	/**
	 * Get filter properties
	 */
	public function getFilterProperties()
	{
		return array_keys(self::$_filter_properties);
	}

	/**
	 * Get filterable property
	 *
	 * @param string $prop
	 * @return string
	 */
	public function getFilterableProperty($prop)
	{
		// Set trimmed prop
		$trimmedProp = $prop;

		// Get not pos
		$notPos = stripos($prop, 'not_');

		// Check if it prefixed the string
		if ($notPos === 0) {
			$trimmedProp = substr($prop, 4);
		}

		// Special treatment for image_id
		if ($trimmedProp === 'image_id') {
			return 'id';
		}

		return $trimmedProp;
	}

	/**
	 * Get filter comparison
	 *
	 * @param string $prop
	 * @return string
	 */
	public function getFilterComparison($prop)
	{
		// Get the operator
		$operator = self::$_filter_properties[$prop];

		// Check if this is a GT
		if ($operator === 'GT') {
			// Get the property value
			$propVal = $this->getProperty($prop);

			// Check if there is a value
			if (! $propVal) {
				return '';
			}

			// Check if first character is > or <
			$char = $propVal[0];

			if ($char === '>' || $char === '<') {
				return $char;
			}

			return '==';
		}

		return $operator;
	}

	/**
	 * Get filterable value
	 *
	 * @param string $prop
	 * @return mixed
	 */
	public function getFilterableValue($prop)
	{
		// Get the operator
		$operator = self::$_filter_properties[$prop];

		// Get the property value
		$propVal = $this->getProperty($prop);

		// Check if this is a GT
		if ($operator === 'GT') {
			$propVal = ltrim($propVal, '>');
			$propVal = ltrim($propVal, '<');
			$propVal = trim($propVal);
		}

		return $propVal;
	}

	/**
	 * Populate model from array
	 *
	 * @param array $data
	 */
	public function populate($data)
	{
		foreach ($data as $key => $val) {
			$this->setProperty($key, $val);
		}
	}

	/**
	 * Set property
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return self
	 */
	public function setProperty($key, $val)
	{
		// Check if property is settable
		if (! $this->hasProperty($key)) {
			return $this;
		}

		// Check if the type is set
		if (! isset(self::$_typed_columns[$key])) {
			return $this;
		}

		// Check for custom set method
		$method = 'customSetter_' . self::$_typed_columns[$key];

		if (method_exists($this, $method)) {
			$this->{$method}($key, $val);
			return $this;
		}

		// Run parent setter
		parent::setProperty($key, $val);

		// Return instance
		return $this;
	}

	/**
	 * Get property
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getProperty($key)
	{
		// Check if property is gettable
		if (! $this->hasProperty($key)) {
			return null;
		}

		// Check if the type is set
		if (! isset(self::$_typed_columns[$key])) {
			return $this;
		}

		// Check for custom set method
		$method = 'customGetter_' . self::$_typed_columns[$key];

		if (method_exists($this, $method)) {
			return $this->{$method}($key);
		}

		// Run parent setter
		return parent::getProperty($key);
	}

	/**
	 * Set int array
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customSetter_intArray($key, $val)
	{
		// Make sure value is array
		$val = $this->arrayCaster($val);

		// Cast values
		foreach ($val as $i => $item) {
			$val[$i] = (int) $item;
		}

		// Set property
		$this->setRawProperty($key, $val);
	}

	/**
	 * Get int array
	 *
	 * @param string $key
	 * @return array
	 */
	private function customGetter_intArray($key)
	{
		// Return from array getter
		return $this->arrayGetter($key);
	}

	/**
	 * Set string array
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customSetter_stringArray($key, $val)
	{
		// Make sure value is array
		$val = $this->arrayCaster($val);

		// Cast values
		foreach ($val as $i => $item) {
			$val[$i] = (string) $item;
		}

		// Set property
		$this->setRawProperty($key, $val);
	}

	/**
	 * Get string array
	 *
	 * @param string $key
	 * @return array
	 */
	private function customGetter_stringArray($key)
	{
		// Return from array getter
		return $this->arrayGetter($key);
	}

	/**
	 * Set boolean
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customSetter_customBool($key, $val)
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

	/**
	 * Get boolean
	 *
	 * @param string $key
	 * @return bool
	 */
	private function customGetter_customBool($key)
	{
		// Get value
		$val = $this->getRawProperty($key);

		// Return bool
		return $val === true;
	}

	/**
	 * Custom order_by setter
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customSetter_orderBy($key, $val)
	{
		// Cast to array
		$val = $this->arrayCaster($val);

		// Set final ordering
		$finalOrdering = array();

		// Check each item for operators
		foreach ($val as $item) {
			// Explode item into an array
			$item = explode(':', $item);

			// The first item is the ordering property
			$prop = $item[0];

			// The second item is the ordering direction
			$dir = isset($item[1]) ? $item[1] : null;

			// If $dir is not set, set defaults
			if (! $dir) {
				if ($prop === 'upload_date' || $prop === 'modify_date') {
					$dir = 'desc';
				} else {
					$dir = 'asc';
				}
			}

			// Set the ordering
			$finalOrdering[$prop] = $dir;
		}

		// Set property
		$this->setRawProperty($key, $finalOrdering);
	}

	/**
	 * Get order_by array
	 *
	 * @param string $key
	 * @return array
	 */
	private function customGetter_orderBy($key)
	{
		// Get value
		$val = $this->getRawProperty($key);

		// Check if we should send default
		if (! $val || gettype($val) !== 'array') {
			return array(
				'position' => 'asc'
			);
		}

		// Get value
		return $val;
	}

	/**
	 * Custom order_by setter
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customSetter_namespace($key, $val)
	{
		$val = rtrim($val, ':');

		if ($val) {
			$val .= ':';
		}

		// Set property
		$this->setRawProperty($key, $val);
	}

	/**
	 * Get order_by array
	 *
	 * @param string $key
	 * @return string
	 */
	private function customGetter_namespace($key)
	{
		// Get value
		$val = $this->getRawProperty($key);

		// Check if we should send default
		if ($val === null) {
			return 'img:';
		}

		// Get value
		return $val;
	}

	/**
	 * Custom order_by setter
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	private function customSetter_host($key, $val)
	{
		if (! $val) {
			$this->setRawProperty($key, '');
			return;
		}

		$this->setRawProperty($key, rtrim($val, '/') . '/');
	}

	/**
	 * Get order_by array
	 *
	 * @param string $key
	 * @return string
	 */
	private function customGetter_host($key)
	{
		return (string) $this->getRawProperty($key);
	}

	/**
	 * Array caster
	 *
	 * @param mixed $val
	 * @return array
	 */
	private function arrayCaster($val)
	{
		// Run string operation
		if (gettype($val) === 'string') {
			$val = explode('|', $val);
		}

		// Make sure value is array
		if (gettype($val) !== 'array') {
			$val = array(
				$val
			);
		}

		// Return the array
		return $val;
	}

	/**
	 * Array getter
	 *
	 * @param string $key
	 * @return mixed
	 */
	private function arrayGetter($key)
	{
		// Get value
		$val = $this->getRawProperty($key);

		// Make sure its an array
		if (gettype($val) !== 'array') {
			return array();
		}

		// Return the array value
		return $val;
	}
}
