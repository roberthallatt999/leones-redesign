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

use ExpressionEngine\Service\Model\Model;

/**
 * Class InternalTagParams
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class InternalTagParams extends Model
{
    /**
     * Model properties
     */
    protected $width;
    protected $height;
    protected $crop;
    protected $background;
    protected $force_jpg;
    protected $force_webp;
    protected $quality;
    protected $scale_up;
    protected $cache_time;

    /**
     * @var array $_typed_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
        'width' => 'int',
        'height' => 'int',
        'crop' => 'bool',
        'background' => 'string',
        'force_jpg' => 'bool',
        'force_webp' => 'bool',
        'quality' => 'int',
        'scale_up' => 'bool',
        'cache_time' => 'int',
    );

    /**
     * Check if property is modified
     *
     * @param string $param
     * @return bool
     */
    public function checkIfPropertyModified($param)
    {
        // Get modified properties
        $modifiedProperties = $this->getModified();

        // Return boolean isset
        return isset($modifiedProperties[$param]);
    }

    /**
     * crop setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__crop($val) // @codingStandardsIgnoreEnd
    {
        $this->customBoolSetter('crop', $val);
    }

    /**
     * force_jpg setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__force_jpg($val) // @codingStandardsIgnoreEnd
    {
        $this->customBoolSetter('force_jpg', $val);
    }

    /**
     * force_jpg setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__force_webp($val) // @codingStandardsIgnoreEnd
    {
        $this->customBoolSetter('force_webp', $val);
    }

    /**
     * scale_up setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__scale_up($val) // @codingStandardsIgnoreEnd
    {
        $this->customBoolSetter('scale_up', $val);
    }

    /**
     * Set boolean
     *
     * @param string $key
     * @param mixed $val
     */
    private function customBoolSetter($key, $val)
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
}
