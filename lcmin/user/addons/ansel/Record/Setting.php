<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Record;

use EllisLab\ExpressionEngine\Service\Model\Model as Record;

/**
 * @property int $id
 * @property string $settings_type
 * @property string $settings_key
 * @property string $settings_value
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
class Setting extends Record
{
	/**
	 * @var string $_table_name
	 */
	// @codingStandardsIgnoreStart
	protected static $_table_name = 'ansel_settings'; // @codingStandardsIgnoreEnd

	/**
	 * @var string $_primary_key
	 */
	// @codingStandardsIgnoreStart
	protected static $_primary_key = 'id'; // @codingStandardsIgnoreEnd

	/**
	 * Record properties
	 */
	protected $id;
	protected $settings_type;
	protected $settings_key;
	protected $settings_value;

	/**
	 * @var array $_db_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_db_columns = array( // @codingStandardsIgnoreEnd
		'settings_type' => array(
			'type' => 'TINYTEXT'
		),
		'settings_key' => array(
			'type' => 'TINYTEXT'
		),
		'settings_value' => array(
			'type' => 'TEXT'
		)
	);

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'id' => 'int',
		'settings_type' => 'string',
		'settings_key' => 'string',
		'settings_value' => 'string'
	);

	/**
	 * @var string $_rows_key
	 */
	// @codingStandardsIgnoreStart
	protected static $_rows_key = 'settings_key'; // @codingStandardsIgnoreEnd

	/**
	 * @var array $_rows
	 */
	// @codingStandardsIgnoreStart
	protected static $_rows = array( // @codingStandardsIgnoreEnd
		array(
			'settings_type' => 'string',
			'settings_key' => 'license_key',
			'settings_value' => null
		),
		array(
			'settings_type' => 'int',
			'settings_key' => 'phone_home',
			'settings_value' => 0
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'default_host',
			'settings_value' => null
		),
		array(
			'settings_type' => 'int',
			'settings_key' => 'default_max_qty',
			'settings_value' => null
		),
		array(
			'settings_type' => 'int',
			'settings_key' => 'default_image_quality',
			'settings_value' => 90
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_jpg',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_retina',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_show_title',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_require_title',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'default_title_label',
			'settings_value' => null
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_show_caption',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_require_caption',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'default_caption_label',
			'settings_value' => null
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_show_cover',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'default_require_cover',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'default_cover_label',
			'settings_value' => null
		),
		array(
			'settings_type' => 'bool',
			'settings_key' => 'hide_source_save_instructions',
			'settings_value' => 'n'
		),
		array(
			'settings_type' => 'int',
			'settings_key' => 'check_for_updates',
			'settings_value' => 0
		),
		array(
			'settings_type' => 'int',
			'settings_key' => 'updates_available',
			'settings_value' => 0
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'update_feed',
			'settings_value' => ''
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'encoding',
			'settings_value' => ''
		),
		array(
			'settings_type' => 'string',
			'settings_key' => 'encoding_data',
			'settings_value' => ''
		)
	);

	/**
	 * Get rows
	 */
	public function getRows()
	{
		return self::$_rows;
	}
}
