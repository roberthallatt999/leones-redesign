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

namespace BoldMinded\Ansel\Model;

use BoldMinded\Ansel\Service\Sources\UploadLocation;
use ExpressionEngine\Service\Model\Model;

/**
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
    protected $preview_directory;
    protected $min_qty;
    protected $max_qty;
    protected $prevent_upload_over_max;
    protected $quality;
    protected $force_jpg;
    protected $force_webp;
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
    protected $show_description;
    protected $require_description;
    protected $description_label;
    protected $show_cover;
    protected $require_cover;
    protected $cover_label;
    protected $prepend_to_table;
    protected $tile_view;

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

    private UploadLocation|null $uploadDirectoryObj = null;
    private UploadLocation|null $saveDirectoryObj = null;
    private UploadLocation|null $previewDirectoryObj = null;

    public function getUploadDirectory(): UploadLocation|null
    {
        // Check if we've already created the object
        if ($this->uploadDirectoryObj) {
            return $this->uploadDirectoryObj;
        }

        // Check if the upload directory has been set
        if (! $this->upload_directory) {
            ee('ansel:Logger')->developer('[Ansel] Upload Directory has not been set.', true);

            return $this->uploadDirectoryObj;
        }

        $location = UploadLocation::getUploadLocationByIdentifier($this->upload_directory);

        $this->uploadDirectoryObj = $location;

        return $location;
    }

    public function getSaveDirectory(): UploadLocation|null
    {
        // Check if we've already created the object
        if ($this->saveDirectoryObj) {
            return $this->saveDirectoryObj;
        }

        // Check if the upload directory has been set
        if (! $this->save_directory) {
            ee('ansel:Logger')->developer('[Ansel] Save Directory has not been set.', true);

            return $this->saveDirectoryObj;
        }

        $location = UploadLocation::getUploadLocationByIdentifier($this->save_directory);

        $this->saveDirectoryObj = $location;

        return $location;
    }

    public function getPreviewDirectory(): UploadLocation|null
    {
        // Check if we've already created the object
        if ($this->previewDirectoryObj) {
            return $this->previewDirectoryObj;
        }

        // Check if the upload directory has been set
        if (! $this->preview_directory) {
            ee('ansel:Logger')->developer('[Ansel] Preview Directory has not been set.', true);

            return $this->previewDirectoryObj;
        }

        $location = UploadLocation::getUploadLocationByIdentifier($this->preview_directory);

        $this->previewDirectoryObj = $location;

        return $location;
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
        'preview_directory' => 'string',
        'min_qty' => 'int',
        'max_qty' => 'int',
        'quality' => 'int',
        'min_width' => 'int',
        'min_height' => 'int',
        'max_width' => 'int',
        'max_height' => 'int',
        'ratio' => 'string',
        'title_label' => 'string',
        'description_label' => 'string',
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
     * force_webp setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__force_webp($val) // @codingStandardsIgnoreEnd
    {
        $this->setRawProperty('force_webp', $this->castBool($val));
    }

    /**
     * force_webp getter
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart
    protected function get__force_webp() // @codingStandardsIgnoreEnd
    {
        return $this->castBool($this->force_webp);
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

    public function getImageColumnLabel()
    {
        return lang('image');
    }

    public function getTitleColumnLabel()
    {
        return $this->title_label ?: lang('title');
    }

    public function getDescriptionColumnLabel()
    {
        return $this->description_label ?: lang('description');
    }

    public function getCoverColumnLabel()
    {
        return $this->cover_label ?: lang('cover');
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
     * show_description setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__show_description($val) // @codingStandardsIgnoreEnd
    {
        $this->setRawProperty('show_description', $this->castBool($val));
    }

    /**
     * show_description getter
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart
    protected function get__show_description() // @codingStandardsIgnoreEnd
    {
        return $this->castBool($this->show_description);
    }

    /**
     * require_description setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__require_description($val) // @codingStandardsIgnoreEnd
    {
        $this->setRawProperty('require_description', $this->castBool($val));
    }

    /**
     * require_description getter
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart
    protected function get__require_description() // @codingStandardsIgnoreEnd
    {
        return $this->castBool($this->require_description);
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
     * require_cover setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__prepend_to_table($val) // @codingStandardsIgnoreEnd
    {
        $this->setRawProperty('prepend_to_table', $this->castBool($val));
    }

    /**
     * show_cover getter
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart
    protected function get__prepend_to_table() // @codingStandardsIgnoreEnd
    {
        return $this->castBool($this->prepend_to_table);
    }

    /**
     * tile_view setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__tile_view($val) // @codingStandardsIgnoreEnd
    {
        $this->setRawProperty('tile_view', $this->castBool($val));
    }

    /**
     * tile_view getter
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart
    protected function get__tile_view() // @codingStandardsIgnoreEnd
    {
        return $this->castBool($this->tile_view);
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
