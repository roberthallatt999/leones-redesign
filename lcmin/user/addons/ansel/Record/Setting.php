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
    protected static $_db_columns = [ // @codingStandardsIgnoreEnd
        'settings_type' => [
            'type' => 'TINYTEXT'
        ],
        'settings_key' => [
            'type' => 'TINYTEXT'
        ],
        'settings_value' => [
            'type' => 'TEXT'
        ]
    ];

    /**
     * @var array $_typed_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_typed_columns = [ // @codingStandardsIgnoreEnd
        'id' => 'int',
        'settings_type' => 'string',
        'settings_key' => 'string',
        'settings_value' => 'string'
    ];

    /**
     * @var string $_rows_key
     */
    // @codingStandardsIgnoreStart
    protected static $_rows_key = 'settings_key'; // @codingStandardsIgnoreEnd

    /**
     * @var array $_rows
     */
    // @codingStandardsIgnoreStart
    protected static $_rows = [ // @codingStandardsIgnoreEnd
        [
            'settings_type' => 'string',
            'settings_key' => 'license_key',
            'settings_value' => null
        ],
        [
            'settings_type' => 'int',
            'settings_key' => 'phone_home',
            'settings_value' => 0
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'default_host',
            'settings_value' => null
        ],
        [
            'settings_type' => 'int',
            'settings_key' => 'default_max_qty',
            'settings_value' => null
        ],
        [
            'settings_type' => 'int',
            'settings_key' => 'default_image_quality',
            'settings_value' => 90
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_jpg',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_webp',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_retina',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_show_title',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_require_title',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'default_title_label',
            'settings_value' => null
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_show_description',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_require_description',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'default_description_label',
            'settings_value' => null
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_show_cover',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_require_cover',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'default_cover_label',
            'settings_value' => null
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'hide_source_save_instructions',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'int',
            'settings_key' => 'check_for_updates',
            'settings_value' => 0
        ],
        [
            'settings_type' => 'int',
            'settings_key' => 'updates_available',
            'settings_value' => 0
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'update_feed',
            'settings_value' => ''
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'encoding',
            'settings_value' => ''
        ],
        [
            'settings_type' => 'string',
            'settings_key' => 'encoding_data',
            'settings_value' => ''
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_prepend_to_table',
            'settings_value' => 'n'
        ],
        [
            'settings_type' => 'bool',
            'settings_key' => 'default_tile_view',
            'settings_value' => 'y'
        ],
    ];

    /**
     * Get rows
     */
    public function getRows()
    {
        return self::$_rows;
    }
}
