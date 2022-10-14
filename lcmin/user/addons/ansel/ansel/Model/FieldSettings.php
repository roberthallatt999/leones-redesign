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
 * @property int $field_id
 * @property string $field_name
 * @property string $type
 * @property string $upload_directory
 * @property string $save_directory
 * @property int $min_qty
 * @property int $max_qty
 * @property bool $prevent_upload_over_max
 * @property int $quality
 * @property bool $force_jpg
 * @property bool $retina_mode
 * @property int $min_width
 * @property int $min_height
 * @property int $max_width
 * @property int $max_height
 * @property string $ratio
 * @property int $ratio_width
 * @property int $ratio_height
 * @property bool $show_title
 * @property bool $require_title
 * @property string $title_label
 * @property bool $show_caption
 * @property bool $require_caption
 * @property string $caption_label
 * @property bool $show_cover
 * @property bool $require_cover
 * @property string $cover_label
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class FieldSettings extends Model
{
	/**
	 * Model properties
	 */
	protected $field_id;
	protected $field_name;
	protected $type;
	protected $upload_directory;
	protected $save_directory;
	protected $min_qty;
	protected $max_qty;
	protected $prevent_upload_over_max;
	protected $quality;
	protected $force_jpg;
	protected $retina_mode;
	protected $min_width;
	protected $min_height;
	protected $max_width;
	protected $max_height;
	protected $ratio;
	protected $ratio_width;
	protected $ratio_height;
	protected $show_title;
	protected $require_title;
	protected $title_label;
	protected $show_caption;
	protected $require_caption;
	protected $caption_label;
	protected $show_cover;
	protected $require_cover;
	protected $cover_label;

	/**
	 * Exclude fields from saving
	 */
	private $excludeFieldsFromSaving = array(
		'field_id',
		'field_name',
		'type',
		'ratio_width',
		'ratio_height'
	);

	/**
	 * To array
	 *
	 * @param bool $includeExcluded Include excluded fields?
	 * @param bool $booleansAsString
	 * @return array
	 */
	public function toArray($includeExcluded = false, $booleansAsString = true)
	{
		// Get the array
		$array = parent::toArray();

		// If $includeExcludedFields is false, we should remove excluded fields
		if (! $includeExcluded) {
			foreach ($this->excludeFieldsFromSaving as $item) {
				unset($array[$item]);
			}
		}

		// Loop through and set booleans
		if ($booleansAsString) {
			foreach ($array as $key => $val) {
				if (gettype($val) === 'boolean') {
					$array[$key] = $val ? 'y' : 'n';
				}
			}
		}

		// Return the array
		return $array;
	}

	/**
	 * @var \stdClass $uploadDirectoryObj
	 */
	private $uploadDirectoryObj;

	/**
	 * Get upload directory
	 *
	 * @return \stdClass
	 */
	public function getUploadDirectory()
	{
		// Check if we've already created the object
		if ($this->uploadDirectoryObj) {
			return $this->uploadDirectoryObj;
		}

		// Create a new object
		$this->uploadDirectoryObj = new \stdClass();
		$this->uploadDirectoryObj->type = null;
		$this->uploadDirectoryObj->identifier = null;

		// Check if the upload directory has been set
		if (! $this->upload_directory) {
			return $this->uploadDirectoryObj;
		}

		// Get the upload directory values
		$values = explode(':', $this->upload_directory);

		// Make sure it's valid
		if (count($values) !== 2) {
			return $this->uploadDirectoryObj;
		}

		// Set upload directory values
		$this->uploadDirectoryObj->type = $values[0];
		$this->uploadDirectoryObj->identifier = $values[1];

		return $this->uploadDirectoryObj;
	}

	/**
	 * @var \stdClass $saveDirectoryObj
	 */
	private $saveDirectoryObj;

	/**
	 * Get upload directory
	 *
	 * @return \stdClass
	 */
	public function getSaveDirectory()
	{
		// Check if we've already created the object
		if ($this->saveDirectoryObj) {
			return $this->saveDirectoryObj;
		}

		// Create a new object
		$this->saveDirectoryObj = new \stdClass();
		$this->saveDirectoryObj->type = null;
		$this->saveDirectoryObj->identifier = null;

		// Check if the upload directory has been set
		if (! $this->save_directory) {
			return $this->saveDirectoryObj;
		}

		// Get the upload directory values
		$values = explode(':', $this->save_directory);

		// Make sure it's valid
		if (count($values) !== 2) {
			return $this->saveDirectoryObj;
		}

		// Set upload directory values
		$this->saveDirectoryObj->type = $values[0];
		$this->saveDirectoryObj->identifier = $values[1];

		return $this->saveDirectoryObj;
	}

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'field_id' => 'int',
		'field_name' => 'string',
		'upload_directory' => 'string',
		'save_directory' => 'string',
		'min_qty' => 'int',
		'max_qty' => 'int',
		'quality' => 'int',
		'min_width' => 'int',
		'min_height' => 'int',
		'max_width' => 'int',
		'max_height' => 'int',
		'ratio' => 'string',
		'title_label' => 'string',
		'caption_label' => 'string',
		'cover_label' => 'string'
	);

	/**
	 * @var bool $retinizedSwitch
	 */
	private $retinizedSwitch = false;

	/**
	 * Retinize values
	 */
	public function retinizeReturnValues()
	{
		$this->retinizedSwitch = true;
	}

	/**
	 * Deretinize values
	 */
	public function deRetinizeReturnValues()
	{
		$this->retinizedSwitch = false;
	}

	/**
	 * min_width getter
	 *
	 * @return int
	 */
	// @codingStandardsIgnoreStart
	public function get__min_width() // @codingStandardsIgnoreEnd
	{
		return $this->retinizedSwitch && $this->retina_mode ?
			$this->min_width * 2 :
			$this->min_width;
	}

	/**
	 * min_height getter
	 *
	 * @return int
	 */
	// @codingStandardsIgnoreStart
	public function get__min_height() // @codingStandardsIgnoreEnd
	{
		return $this->retinizedSwitch && $this->retina_mode ?
			$this->min_height * 2 :
			$this->min_height;
	}

	/**
	 * max_width getter
	 *
	 * @return int
	 */
	// @codingStandardsIgnoreStart
	public function get__max_width() // @codingStandardsIgnoreEnd
	{
		return $this->retinizedSwitch && $this->retina_mode ?
			$this->max_width * 2 :
			$this->max_width;
	}

	/**
	 * max_height getter
	 *
	 * @return int
	 */
	// @codingStandardsIgnoreStart
	public function get__max_height() // @codingStandardsIgnoreEnd
	{
		return $this->retinizedSwitch && $this->retina_mode ?
			$this->max_height * 2 :
			$this->max_height;
	}

	/**
	 * ratio_width setter
	 */
	// @codingStandardsIgnoreStart
	protected function set__ratio_width() // @codingStandardsIgnoreEnd
	{
		return null;
	}

	/**
	 * ratio_height getter
	 *
	 * @return null|int
	 */
	// @codingStandardsIgnoreStart
	protected function get__ratio_width() // @codingStandardsIgnoreEnd
	{
		// Get the ratio
		$ratio = $this->ratio;

		// Check if ratio is set
		if (! $ratio) {
			return null;
		}

		// Explode the ratio
		$ratio = explode(':', $ratio);

		// Make sure ratio count is 2
		if (count($ratio) !== 2) {
			return null;
		}

		// Return ratio width
		return (float) $ratio[0];
	}

	/**
	 * ratio_height setter
	 */
	// @codingStandardsIgnoreStart
	protected function set__ratio_height() // @codingStandardsIgnoreEnd
	{
		return null;
	}

	/**
	 * ratio_height getter
	 *
	 * @return null|int
	 */
	// @codingStandardsIgnoreStart
	protected function get__ratio_height() // @codingStandardsIgnoreEnd
	{
		// Get the ratio
		$ratio = $this->ratio;

		// Check if ratio is set
		if (! $ratio) {
			return null;
		}

		// Explode the ratio
		$ratio = explode(':', $ratio);

		// Make sure ratio count is 2
		if (count($ratio) !== 2) {
			return null;
		}

		// Return ratio width
		return (float) $ratio[1];
	}

	/**
	 * Predefined types
	 */
	private $predefinedTypes = array(
		'channel',
		'grid',
		'blocks',
		'lowVar',
		'fluid'
	);

	/**
	 * type setter
	 *
	 * @param string $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__type($val) // @codingStandardsIgnoreEnd
	{
		if (in_array($val, $this->predefinedTypes)) {
			$this->setRawProperty('type', $val);
		}
	}

	/**
	 * type getter
	 *
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	protected function get__type() // @codingStandardsIgnoreEnd
	{
		// Check if it has been set
		if (in_array($this->type, $this->predefinedTypes)) {
			return $this->type;
		}

		// Return first item in predefined
		return $this->predefinedTypes[0];
	}

	/**
	 * quality setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__quality($val) // @codingStandardsIgnoreEnd
	{
		$val = (int) $val;

		$val = $val > 100 ? 100 : $val;

		$val = $val < 0 ? 0 : $val;

		$this->setRawProperty('quality', $val);
	}

	/**
	 * quality getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__quality() // @codingStandardsIgnoreEnd
	{
		$val = (int) $this->quality;

		$val = $val > 100 ? 100 : $val;

		$val = $val < 0 ? 0 : $val;

		return $val;
	}

	/**
	 * prevent_upload_over_max setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__prevent_upload_over_max($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('prevent_upload_over_max', $this->castBool($val));
	}

	/**
	 * prevent_upload_over_max getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__prevent_upload_over_max() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->prevent_upload_over_max);
	}

	/**
	 * force_jpg setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__force_jpg($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('force_jpg', $this->castBool($val));
	}

	/**
	 * force_jpg getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__force_jpg() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->force_jpg);
	}

	/**
	 * retina_mode setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__retina_mode($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('retina_mode', $this->castBool($val));
	}

	/**
	 * retina_mode getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__retina_mode() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->retina_mode);
	}

	/**
	 * show_title setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__show_title($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('show_title', $this->castBool($val));
	}

	/**
	 * show_title getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__show_title() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->show_title);
	}

	/**
	 * require_title setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__require_title($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('require_title', $this->castBool($val));
	}

	/**
	 * require_title getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__require_title() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->require_title);
	}

	/**
	 * show_caption setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__show_caption($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('show_caption', $this->castBool($val));
	}

	/**
	 * show_caption getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__show_caption() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->show_caption);
	}

	/**
	 * require_caption setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__require_caption($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('require_caption', $this->castBool($val));
	}

	/**
	 * require_caption getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__require_caption() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->require_caption);
	}

	/**
	 * show_cover setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__show_cover($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('show_cover', $this->castBool($val));
	}

	/**
	 * show_cover getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__show_cover() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->show_cover);
	}

	/**
	 * require_cover setter
	 *
	 * @param mixed $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__require_cover($val) // @codingStandardsIgnoreEnd
	{
		$this->setRawProperty('require_cover', $this->castBool($val));
	}

	/**
	 * show_cover getter
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	protected function get__require_cover() // @codingStandardsIgnoreEnd
	{
		return $this->castBool($this->require_cover);
	}

	/**
	 * Cast bool
	 *
	 * @param mixed $val
	 * @return bool
	 */
	private function castBool($val)
	{
		// If val is already a boolean, send it back
		if (gettype($val) === 'boolean') {
			return $val;
		}

		// Set string truth values
		$truthy = array(
			'y',
			'yes',
			'true'
		);

		// Return true or false based on string truthy
		return in_array($val, $truthy);
	}
}
