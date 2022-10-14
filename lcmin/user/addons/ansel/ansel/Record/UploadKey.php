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
 * Class UploadKey
 *
 * @property int $id
 * @property string $key
 * @property int $created
 * @property int $expires
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class UploadKey extends Record
{
	/**
	 * @var string $_table_name
	 */
	// @codingStandardsIgnoreStart
	protected static $_table_name = 'ansel_upload_keys';
	// @codingStandardsIgnoreEnd

	/**
	 * @var string $_primary_key
	 */
	// @codingStandardsIgnoreStart
	protected static $_primary_key = 'id';
	// @codingStandardsIgnoreEnd

	/**
	 * Record properties
	 */
	protected $id;
	protected $key;
	protected $created;
	protected $expires;

	/**
	 * @var array $_db_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_db_columns = array( // @codingStandardsIgnoreEnd
		'key' => array(
			'type' => 'TEXT'
		),
		'created' => array(
			'default' => 0,
			'type' => 'INT',
			'unsigned' => true
		),
		'expires' => array(
			'default' => 0,
			'type' => 'INT',
			'unsigned' => true
		)
	);

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'id' => 'int',
		'key' => 'string',
		'created' => 'int',
		'expires' => 'int'
	);

	/**
	 * UploadKey constructor
	 */
	public function __construct()
	{
		// Run the parent constructor
		parent::__construct();

		// Set the key
		$this->setRawProperty('key', uniqid());
	}

	/**
	 * @var array $_events
	 */
	// @codingStandardsIgnoreStart
	protected static $_events = array( // @codingStandardsIgnoreEnd
		'beforeSave',
		'beforeUpdate'
	);

	/**
	 * Before save
	 */
	public function onBeforeSave()
	{
		$this->beforeSaveUpdate();
	}

	/**
	 * Before update
	 */
	public function onBeforeUpdate()
	{
		$this->beforeSaveUpdate();
	}

	/**
	 * Before save or update
	 */
	private function beforeSaveUpdate()
	{
		// Set created date
		$this->setRawProperty('created', $this->created ?: time());

		// Set expires date
		$this->setRawProperty(
			'expires',
			$this->expires ?: strtotime('+ 2 hours', time())
		);
	}

	/**
	 * Prevent tampering with model
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	public function __set($key, $val)
	{
		return;
	}
}
