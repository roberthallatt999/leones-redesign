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

namespace BoldMinded\Ansel\Service\Sources;

use BoldMinded\Ansel\Record\Image;
use ExpressionEngine\Service\Model\Model;

/**
 * Class BaseSource
 */
abstract class BaseSource
{
    protected $isLivePreview = false;

    public function setIsLivePreview(bool $isLivePreview): void
    {
        $this->isLivePreview = $isLivePreview;
    }

    /**
     * Get file chooser link
     */
    abstract public function getFileChooserLink(
        UploadLocation $location,
        string $lang
    ): string;

    /**
     * Upload file to the source storage
     */
    abstract public function uploadFile(
        UploadLocation $location,
        string $filePath,
        string|null $subFolder = null,
        Image|null $anselRecord = null,
        bool $insertTimestamp = false
    ): string;

    /**
     * Delete file from source storage
     */
    abstract public function deleteFile(
        UploadLocation $location,
        string $fileName,
        string|null $subFolder = null,
    ): void;

    abstract function updateFileAttributes(
        int $fileId,
        array $attributes
    ): void;

    /**
     * Add file and record to the source
     */
    abstract public function addFile(
        UploadLocation $location,
        string $filePath,
        Image|null $anselRecord = null
    ): Model;

    /**
     * Remove file and record from the source
     */
    abstract public function removeFile(int $fileId);

    /**
     * Get source URL
     */
    abstract public function getSourceUrl(UploadLocation $location): string;

    /**
     * Get source server path
     */
    abstract public function getSourcePath(UploadLocation $location): string;

    /**
     * Get file URL
     */
    abstract public function getFileUrl(int $fileId): string;

    /**
     * Get file model
     */
    abstract public function getFileModel(int $fileId): ?Model;

    /**
     * Cache file locally by ID
     */
    abstract public function cacheFileLocallyById(int $fileId): ?string;

    /**
     * Get source models
     */
    abstract public function getSourceModels(array $ids): array;

    /**
     * Get file models
     */
    abstract public function getFileModels(array $ids): array;

    abstract public function isSymLink(UploadLocation $location): bool;
}
