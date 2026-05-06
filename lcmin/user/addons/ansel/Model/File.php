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
 * Class File
 *
 * @property string $location_type
 * @property string|int $location_identifier
 * @property string|int $directory_id
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
    protected $directory_id;
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
    protected $title;
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
        'title' => 'string',
        'file_description' => 'string',
        'file_credit' => 'string',
        'file_location' => 'string'
    );

    /**
     * Predefined location types
     */
    private $predefinedLocationTypes = array(
        'ee',
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
     * location_identifier setter
     *
     * @param mixed $val
     */
    // @codingStandardsIgnoreStart
    protected function set__directory_id($val) // @codingStandardsIgnoreEnd
    {
        if (is_numeric($val)) {
            $val = (int) $val;
        }

        $this->setRawProperty('directory_id', $val);
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
