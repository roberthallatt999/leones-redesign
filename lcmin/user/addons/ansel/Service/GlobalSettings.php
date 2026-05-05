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

namespace BoldMinded\Ansel\Service;

use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use ExpressionEngine\Service\Model\Collection;
use BoldMinded\Ansel\Record\Setting;

/**
 * @property string $license_key
 * @property int $phone_home
 * @property string $default_host
 * @property int $default_max_qty
 * @property int $default_image_quality
 * @property bool $default_jpg
 * @property bool $default_webp
 * @property bool $default_retina
 * @property bool $default_show_title
 * @property bool $default_require_title
 * @property string $default_title_label
 * @property bool $default_show_description
 * @property bool $default_require_description
 * @property string $default_description_label
 * @property bool $default_show_cover
 * @property bool $default_require_cover
 * @property string $default_cover_label
 * @property bool $hide_source_save_instructions
 * @property int $check_for_updates
 * @property int $updates_available
 * @property string $update_feed
 * @property string $encoding
 * @property string $encoding_data
 * @property string $default_prepend_to_table
 * @property string $default_tile_view
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class GlobalSettings implements \Iterator, \Countable
{
    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

    /**
     * @var \EE_Config $eeConfig
     */
    private $eeConfig;

    /**
     * @var int $position
     *
     * required for iterator
     */
    private $position = 0;

    /**
     * @var Collection $records
     */
    private $records;

    /**
     * Settings constructor
     *
     * @param RecordBuilder $recordBuilder
     * @param \EE_Config $eeConfig
     */
    public function __construct(
        RecordBuilder $recordBuilder,
        \EE_Config $eeConfig
    ) {
        // Inject dependencies
        $this->recordBuilder = $recordBuilder;
        $this->eeConfig = $eeConfig;
    }

    /**
     * Get records
     */
    private function fetchRecords()
    {
        // Get settings records
        $records = $this->recordBuilder->get('ansel:Setting')
            ->all();


        /**
         * Records may not be in the order we want so we need to order them
         */

        // First record
        $first = $records->first();

        // Get rows
        $rows = $first->getRows();

        // Ordered records array
        $ordered = array();

        // Iterate over rows
        foreach ($rows as $item) {
            // Set the key
            $key = $item['settings_key'];

            // Filter the records
            $filteredRecord = $records->filter(function ($temp) use ($key) {
                return $key === $temp->settings_key;
            });

            // Get the first record (should be the only one in the filtered collection
            $filteredRecord = $filteredRecord->first();

            // Sanity check
            if ($filteredRecord) {
                // Add it to the array
                $ordered[] = $filteredRecord;
            }
        }

        // Hack the collection to set the new ordered elements
        $records->__construct($ordered);

        // Add the collection to this class's property
        $this->records = $records;
    }

    /**
     * Get magic method
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        /**
         * Check config first
         */
        if ($key !== 'encoding' &&
            $key !== 'encoding_data' &&
            isset($this->eeConfig->config['ansel'][$key])
        ) {
            return $this->eeConfig->config['ansel'][$key];
        }


        /**
         * Get config item from records
         */

        // Check if we need to retrieve records from DB
        if (! $this->records) {
            $this->fetchRecords();
        }

        // Get the setting
        $result = $this->records->filter(function ($record) use ($key) {
            return $record->settings_key === $key;
        });

        // If no setting, return null
        if (! $setting = $result->first()) {
            return null;
        }

        /** @var Setting $setting */

        if ($key === 'encoding') {
            // Check for lack of value and set initial value if required
            if (! $setting->settings_value) {
                $val = (string) strtotime('+30 days', time());
                $setting->settings_value = base64_encode($val);
                $this->save();
            }

            // Return the value
            return base64_decode($setting->settings_value);
        } elseif ($key === 'encoding_data') {
            if (! $setting->settings_value) {
                return '';
            }

            return base64_decode($setting->settings_value);
        } elseif ($setting->settings_key === 'default_host') {
            $val = (string) $setting->settings_value;
            if (! $val) {
                return (string) '';
            }
            return rtrim($val, '/') . '/';
        } elseif ($setting->settings_type === 'string') {
            return (string) $setting->settings_value;
        } elseif ($setting->settings_type === 'int') {
            return (int) $setting->settings_value;
        } elseif ($setting->settings_type === 'bool') {
            return $setting->settings_value === 'y';
        }

        // Return null if no criteria met
        return null;
    }

    /**
     * Set magic method
     *
     * @param string $key
     * @param mixed $val
     */
    public function __set($key, $val)
    {
        // We should not set the encoding key here ever
        if ($key === 'encoding') {
            return;
        }

        // Check if we need to retrieve records from DB
        if (! $this->records) {
            $this->fetchRecords();
        }

        // Get the setting
        $result = $this->records->filter(function ($record) use ($key) {
            return $record->settings_key === $key;
        });

        // Get the first result
        $setting = $result->first();

        // If no setting, end processing
        if (! $setting) {
            return;
        }

        /** @var Setting $setting */

        if ($key === 'encoding_data') {
            $setting->settings_value = base64_encode($val);
        } elseif ($setting->settings_type === 'string') {
            $setting->settings_value = (string) $val;
        } elseif ($setting->settings_type === 'int') {
            $setting->settings_value = (int) $val;
        } elseif ($setting->settings_type === 'bool') {
            $setting->settings_value = $val === 'y' ||
                $val === 'yes' ||
                $val === 'true' ||
                $val === true ?
                    'y' :
                    'n';
        }
    }

    /**
     * Get type
     *
     * @param string $key
     * @return string
     */
    public function getType($key)
    {
        // Check if we need to retrieve records from DB
        if (! $this->records) {
            $this->fetchRecords();
        }

        // Get the setting
        $result = $this->records->filter(function ($record) use ($key) {
            return $record->settings_key === $key;
        });

        // If no setting, return null
        if (! $setting = $result->first()) {
            return null;
        }

        // Return the type
        return $setting->settings_type;
    }

    /**
     * Save settings
     */
    public function save()
    {
        // Check if we have records to save
        if (! $this->records) {
            return;
        }

        // Save records
        $this->records->save();
    }

    /**
     * Implement count method
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        if (! $this->records) {
            $this->fetchRecords();
        }

        return $this->records->count();
    }


    /**
     * Required Iterator methods
     */

    /**
     * Current
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        if (! $this->records) {
            $this->fetchRecords();
        }

        return $this->__get($this->records[$this->position]->settings_key);
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        if (! $this->records) {
            $this->fetchRecords();
        }

        return $this->records[$this->position]->settings_key;
    }

    /**
     * Next
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        if (! $this->records) {
            $this->fetchRecords();
        }

        ++$this->position;
    }

    /**
     * Rewind
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        if (! $this->records) {
            $this->fetchRecords();
        }

        $this->position = 0;
    }

    /**
     * Valid
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if (! $this->records) {
            $this->fetchRecords();
        }

        return isset($this->records[$this->position]) &&
            isset($this->records[$this->position]->settings_key);
    }
}
