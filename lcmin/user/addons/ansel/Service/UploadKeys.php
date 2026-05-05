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

/**
 * Class UploadKeys
 */
class UploadKeys
{
    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

    /**
     * @var string $siteUrl
     */
    private $siteUrl;

    /**
     * @var string $siteIndex
     */
    private $siteIndex;

    /**
     * UploadKeys constructor
     *
     * @param RecordBuilder $recordBuilder
     * @param string $siteUrl
     * @param string $siteIndex
     */
    public function __construct(
        RecordBuilder $recordBuilder,
        $siteUrl,
        $siteIndex
    ) {
        // Start a record query
        $expiredRecords = $recordBuilder->get('ansel:UploadKey');
        $expiredRecords->filter('expires', '<', time());

        // Delete expired records
        $expiredRecords->delete();

        // Inject dependencies
        $this->recordBuilder = $recordBuilder;
        $this->siteUrl = $siteUrl;
        $this->siteIndex = $siteIndex;
    }

    /**
     * Create a new upload key
     */
    public function createNew()
    {
        // Get new record
        $record = $this->recordBuilder->make('ansel:UploadKey');

        // Save record to the database
        $record->save();

        // Return the key
        return $record->key;
    }

    /**
     * Validate a key
     *
     * @param string $key
     * @return bool
     */
    public function isValidKey($key)
    {
        // Filter the record to the appropriate key
        $record = $this->recordBuilder->get('ansel:UploadKey');
        $record->filter('key', $key);

        // Return true if count is greater than 0
        return $record->count() > 0;
    }

    /**
     * Get upload URL
     */
    public function getUploadUrl()
    {
        // Get the action record
        $actionRecord = $this->recordBuilder->get('Action');
        $actionRecord->filter('class', 'Ansel');
        $actionRecord->filter('method', 'imageUploader');
        $actionRecord = $actionRecord->first();

        // Set the URL
        $url = rtrim($this->siteUrl, '/') . '/' . $this->siteIndex;
        $url = "{$url}?ACT={$actionRecord->action_id}";

        // Return the URL
        return $url;
    }
}
