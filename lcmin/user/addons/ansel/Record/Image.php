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

use BoldMinded\Ansel\Service\Sources\UploadLocation;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Model\Model as Record;
use BoldMinded\Ansel\Service\Sources\SourceRouter;

/**
 * @property string $file_location
 * @property string $delete
 * @property int $id
 * @property int $site_id
 * @property int $source_id
 * @property int $content_id
 * @property int $field_id
 * @property string $content_type
 * @property int $row_id
 * @property int $col_id
 * @property int $file_id
 * @property string $original_location_type
 * @property int $original_file_id
 * @property string $upload_location_type
 * @property int $upload_location_id
 * @property int $directory_id
 * @property string $filename
 * @property string $extension
 * @property string $original_extension
 * @property int $filesize
 * @property int $original_filesize
 * @property int $width
 * @property int $height
 * @property int $x
 * @property int $y
 * @property string $title
 * @property string $description
 * @property int $member_id
 * @property int $position
 * @property bool $cover
 * @property int $upload_date
 * @property int $modify_date
 * @property bool $disabled
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
class Image extends Record
{
    /**
     * @var string $_table_name
     */
    // @codingStandardsIgnoreStart
    protected static $_table_name = 'ansel_images';
    // @codingStandardsIgnoreEnd

    /**
     * @var string $_primary_key
     */
    // @codingStandardsIgnoreStart
    protected static $_primary_key = 'id';
    // @codingStandardsIgnoreEnd

    /**
     * Public properties
     */
    // @codingStandardsIgnoreStart
    public $_file_location = '';
    public $_delete = '';
    // @codingStandardsIgnoreEnd

    /**
     * Record properties
     */
    protected $id;
    protected $site_id;
    protected $source_id;
    protected $content_id;
    protected $field_id;
    protected $content_type;
    protected $row_id;
    protected $col_id;
    protected $file_id;
    protected $original_location_type;
    protected $original_file_id;
    protected $upload_location_type;
    protected $upload_location_id;
    protected $directory_id;
    protected $filename;
    protected $extension;
    protected $original_extension;
    protected $filesize;
    protected $original_filesize;
    protected $width;
    protected $height;
    protected $x;
    protected $y;
    protected $title;
    protected $description;
    protected $member_id;
    protected $position;
    protected $cover;
    protected $upload_date;
    protected $modify_date;
    protected $disabled;

    protected $publisher_lang_id;
    protected $publisher_status;

    /**
     * @var array $_db_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_db_columns = [ // @codingStandardsIgnoreEnd
        'site_id' => [
            'default' => 1,
            'type' => 'TINYINT',
            'unsigned' => true
        ],
        'source_id' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'content_id' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'field_id' => [
            'default' => 0,
            'type' => 'MEDIUMINT',
            'unsigned' => true
        ],
        'content_type' => [
            'default' => 'channel',
            'null' => false,
            'type' => 'VARCHAR',
            'constraint' => 255
        ],
        'row_id' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'col_id' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'file_id' => [
            'type' => 'INT',
            'unsigned' => true
        ],
        'original_location_type' => [
            'default' => 'ee',
            'type' => 'VARCHAR',
            'constraint' => 10
        ],
        'original_file_id' => [
            'type' => 'INT',
            'unsigned' => true
        ],
        'upload_location_type' => [
            'default' => 'ee',
            'type' => 'VARCHAR',
            'constraint' => 10
        ],
        'upload_location_id' => [
            'default' => '',
            'type' => 'VARCHAR',
            'constraint' => 255
        ],
        'filename' => [
            'type' => 'TEXT'
        ],
        'extension' => [
            'default' => '',
            'type' => 'VARCHAR',
            'constraint' => 10
        ],
        'original_extension' => [
            'default' => '',
            'type' => 'VARCHAR',
            'constraint' => 10
        ],
        'filesize' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'original_filesize' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'width' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'height' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'x' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'y' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'title' => [
            'default' => '',
            'type' => 'VARCHAR',
            'constraint' => 255
        ],
        'description' => [
            'default' => '',
            'constraint' => 255,
            'type' => 'VARCHAR'
        ],
        'member_id' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'position' => [
            'default' => 1,
            'type' => 'TINYINT',
            'unsigned' => true
        ],
        'cover' => [
            'constraint' => 1,
            'default' => 0,
            'type' => 'TINYINT',
            'unsigned' => true
        ],
        'upload_date' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'modify_date' => [
            'default' => 0,
            'type' => 'INT',
            'unsigned' => true
        ],
        'disabled' => [
            'constraint' => 1,
            'default' => 0,
            'type' => 'TINYINT',
            'unsigned' => true
        ],
        'publisher_lang_id' => [
            'constraint' => 4,
            'default' => 1,
            'type'	=> 'INT',
            'unsigned' => true
        ],
        'publisher_status' => [
            'default' => '',
            'type' => 'VARCHAR',
            'constraint' => 255
        ],
    ];

    /**
     * Get columns
     */
    public static function getColumnNames()
    {
        return array_keys(self::$_db_columns);
    }

    /**
     * @var array $_typed_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_typed_columns = [ // @codingStandardsIgnoreEnd
        'id' => 'int',
        'site_id' => 'int',
        'source_id' => 'int',
        'content_id' => 'int',
        'field_id' => 'int',
        'content_type' => 'string',
        'row_id' => 'int',
        'col_id' => 'int',
        'file_id' => 'int',
        'original_location_type' => 'string',
        'original_file_id' => 'int',
        'upload_location_type' => 'string',
        'upload_location_id' => 'string',
        'filename' => 'string',
        'extension' => 'string',
        'original_extension' => 'string',
        'filesize' => 'int',
        'original_filesize' => 'int',
        'width' => 'int',
        'height' => 'int',
        'x' => 'int',
        'y' => 'int',
        'title' => 'string',
        'description' => 'string',
        'member_id' => 'int',
        'position' => 'int',
        'cover' => 'bool',
        'upload_date' => 'int',
        'modify_date' => 'int',
        'disabled' => 'bool',
        'publisher_lang_id' => 'int',
        'publisher_status' => 'string',
    ];

    private string $sourceUrl = '';
    private string $sourcePath = '';

    private function validateUploadLocation()
    {
        if (!$this->upload_location_id) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('missing_upload_location_title'))
                ->addToBody(sprintf(lang('missing_upload_location_desc'), $this->upload_location_id))
                ->now();
        }

        if (!$this->directory_id) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('missing_upload_directory_title'))
                ->addToBody(sprintf(lang('missing_upload_directory_desc'), $this->directory_id))
                ->now();
        }

        if (is_string($this->upload_location_id)) {
            $this->upload_location_id = intval($this->upload_location_id);
        }

        if (is_string($this->directory_id)) {
            $this->directory_id = intval($this->directory_id);
        }
    }

    /**
     * Get source URL
     */
    public function getSourceUrl()
    {
        // Check if we've already got the URL
        if ($this->sourceUrl) {
            return $this->sourceUrl;
        }

        $this->validateUploadLocation();

        // Get the SourceRouter class
        /** @var SourceRouter $sourceRouter */
        $sourceRouter = ee('ansel:SourceRouter');

        // Set the source type
        $sourceRouter->setSource($this->upload_location_type);

        // Get the file URL
        $this->sourceUrl = $sourceRouter->getSourceUrl(
            new UploadLocation(
                uploadLocationId: $this->upload_location_id,
                directoryId: $this->directory_id,
            )
        );

        // Return the file URL
        return $this->sourceUrl;
    }

    public function getSourcePath()
    {
        // Check if we've already got the URL
        if ($this->sourcePath) {
            return $this->sourcePath;
        }

        $this->validateUploadLocation();

        // Get the SourceRouter class
        /** @var SourceRouter $sourceRouter */
        $sourceRouter = ee('ansel:SourceRouter');

        // Set the source type
        $sourceRouter->setSource($this->upload_location_type);

        // Get the file URL
        $this->sourcePath = $sourceRouter->getSourcePath(
            new UploadLocation(
                uploadLocationId: $this->upload_location_id,
                directoryId: $this->directory_id,
            )
        );

        // Return the file URL
        return $this->sourcePath;
    }

    /**
     * Get basename
     */
    public function getBasename()
    {
        // Put the filename together
        return "{$this->filename}.{$this->extension}";
    }

    /**
     * @var string $url
     */
    private $url;

    /**
     * Get url
     */
    public function getUrl()
    {
        // Check if we've already got the URL
        if ($this->url) {
            return $this->url;
        }

        // Put the URL together
        $basename = rawurlencode($this->getBasename());
        $this->url = "{$this->getSourceUrl()}{$basename})}";

        // Return the file URL
        return $this->url;
    }

    /**
     * @var string $highQualUrl
     */
    private $highQualUrl;

    /**
     * Get high quality URL
     */
    public function getHighQualityUrl()
    {
        // Check if we've already got the URL
        if ($this->highQualUrl) {
            return $this->highQualUrl;
        }

        // Put the URL together
        $dirName = $this->getHighQualityDirectoryName();
        $this->highQualUrl = "{$this->getSourceUrl()}{$dirName}/";
        $this->highQualUrl .= "{$this->id}/{$this->getBasename()}";

        // Return the file URL
        return $this->highQualUrl;
    }

    /**
     * @var string $thumbPath
     */
    private $thumbPath;

    /**
     * Get thumb path
     */
    public function getThumbPath()
    {
        // Check if we've already got the path
        if ($this->thumbPath) {
            return $this->thumbPath;
        }

        // Put the path together
        $dirName = $this->getThumbDirectoryName();
        $this->thumbPath = "{$dirName}/{$this->id}/";
        $this->thumbPath .= rawurlencode($this->getBasename());

        // Return the thumb path
        return $this->thumbPath;
    }

    /**
     * @var string $highQualUrl
     */
    private $thumbUrl;

    /**
     * Get high quality URL
     */
    public function getThumbUrl()
    {
        // Check if we've already got the URL
        if ($this->thumbUrl) {
            return $this->thumbUrl;
        }

        // Put the URL together
        $dirName = $this->getThumbDirectoryName();
        $this->thumbUrl = "{$this->getSourceUrl()}{$dirName}/{$this->id}/";
        $this->thumbUrl .= rawurlencode($this->getBasename());

        // Return the file URL
        return $this->thumbUrl;
    }

    /**
     * @var string $highQualUrl
     */
    private $eeThumbUrl;

    /**
     * Get high quality URL
     */
    public function getEeThumbUrl()
    {
        // Check if we've already got the URL
        if ($this->eeThumbUrl) {
            return $this->eeThumbUrl;
        }

        // Put the URL together
        $this->eeThumbUrl = "{$this->getSourceUrl()}_thumbs/";
        $this->eeThumbUrl .= rawurlencode($this->getBasename());

        // Return the file URL
        return $this->eeThumbUrl;
    }

    /**
     * @var string $orignalUrl
     */
    private $orignalUrl;

    /**
     * Get url
     */
    public function getOriginalUrl()
    {
        // Check if we've already got the URL
        if ($this->orignalUrl) {
            return $this->orignalUrl;
        }

        // Get the SourceRouter class
        /** @var SourceRouter $sourceRouter */
        $sourceRouter = ee('ansel:SourceRouter');

        // Set the source type
        $sourceRouter->setSource($this->original_location_type);

        // Get the file URL
        $this->orignalUrl = $sourceRouter
            ->getFileUrl($this->original_file_id);

        // Return the file URL
        return $this->orignalUrl;
    }

    /**
     * Get high quality directory name
     */
    public function getHighQualityDirectoryName()
    {
        if ($this->upload_location_type === 'assets') {
            return 'ansel_high_qual';
        } else {
            return '_ansel_high_qual';
        }
    }

    /**
     * Get thumb directory name
     */
    public function getThumbDirectoryName()
    {
        if ($this->upload_location_type === 'assets') {
            return 'ansel_thumbs';
        } else {
            return '_ansel_thumbs';
        }
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
