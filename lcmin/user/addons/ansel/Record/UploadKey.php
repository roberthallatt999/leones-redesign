<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Add-ons
 * @category    Ansel
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/ansel
 * @license
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace BoldMinded\Ansel\Record;

use ExpressionEngine\Service\Model\Model as Record;

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
    protected static $_db_columns = [ // @codingStandardsIgnoreEnd
        'key' => [
            'type' => 'TEXT'
        ],
        'created' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'expires' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ]
    ];

    /**
     * @var array $_typed_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_typed_columns = [ // @codingStandardsIgnoreEnd
        'id' => 'int',
        'key' => 'string',
        'created' => 'int',
        'expires' => 'int'
    ];

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
