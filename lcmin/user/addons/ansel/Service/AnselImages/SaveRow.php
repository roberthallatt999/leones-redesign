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

namespace BoldMinded\Ansel\Service\AnselImages;

use BoldMinded\Ansel\Model\FieldSettings as FieldSettingsModel;
use BoldMinded\Ansel\Record\Image;
use BoldMinded\Ansel\Service\Sources\UploadLocation;
use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BoldMinded\Ansel\Service\Sources\SourceRouter;
use BoldMinded\Ansel\Service\FileCacheService;
use BoldMinded\Ansel\Service\ImageManipulation\ManipulateImage;
use BoldMinded\Ansel\Record\Image as ImageRecord;
use RuntimeException;

/**
 * Class SaveRow
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class SaveRow
{
    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

    /**
     * @var SourceRouter $sourceRouter
     */
    private $sourceRouter;

    /**
     * @var FileCacheService $fileCacheService
     */
    private $fileCacheService;

    /**
     * @var ManipulateImage $manipulateImage
     */
    private $manipulateImage;

    /**
     * @var int $memberId
     */
    private $memberId;

    /**
     * @var int $siteId
     */
    private $siteId;

    /**
     * Constructor
     *
     * @param RecordBuilder $recordBuilder
     * @param SourceRouter $sourceRouter
     * @param FileCacheService $fileCacheService
     * @param ManipulateImage $manipulateImage
     * @param int $memberId
     * @param int $siteId
     */
    public function __construct(
        RecordBuilder $recordBuilder,
        SourceRouter $sourceRouter,
        FileCacheService $fileCacheService,
        ManipulateImage $manipulateImage,
        $memberId,
        $siteId
    ) {
        // Inject dependencies
        $this->recordBuilder = $recordBuilder;
        $this->sourceRouter = $sourceRouter;
        $this->fileCacheService = $fileCacheService;
        $this->manipulateImage = $manipulateImage;
        $this->memberId = (int) $memberId;
        $this->siteId = (int) $siteId;
    }

    /**
     * Save
     *
     * @param array $data
     * @param FieldSettingsModel $fieldSettings
     * @param int $sourceId
     * @param int $contentId
     * @param string $contentType
     * @param int $rowId
     * @param int $colId
     */
    public function save(
        $data,
        FieldSettingsModel $fieldSettings,
        $sourceId,
        $contentId,
        $contentType,
        $rowId = null,
        $colId = null,
        bool $isLivePreview = false,
    ) {
        // Set the time
        $time = time();

        // Reinitialize the field settings
        $fieldSettings->retinizeReturnValues();

        $uploadType = $fieldSettings->getUploadDirectory()->type;
        $uploadLocation = $fieldSettings->getUploadDirectory();

        $saveType = $fieldSettings->getSaveDirectory()->type;
        $saveLocation = $fieldSettings->getSaveDirectory();

        $previewLocation = $fieldSettings->getPreviewDirectory();

        // It's an LP request and an upload directory has been defined for it.
        $isLivePreview = $isLivePreview && $previewLocation && $previewLocation->uploadLocationId;

        if ($isLivePreview) {
            $saveType = $previewLocation->type;
            $saveLocation = $previewLocation;
            $this->sourceRouter->setIsLivePreview(true);
        }

        // Set up the sourceRouter for upload location
        $this->sourceRouter->setSource($saveType);

        // If there is an image file_location, upload file to source dir
        if (isset($data['file_location']) && is_file($data['file_location'])) {
            // Upload the file and get the return file model
            $sourceFileModel = $this->sourceRouter->addFile(
                $uploadLocation,
                $data['file_location']
            );

            // Set source file id on data
            $data['source_file_id'] = $sourceFileModel->file_id;

            // Remove the file
            if (!$isLivePreview && file_exists($data['file_location'])) {
                unlink($data['file_location']);
            }

        // Otherwise we need to get the source file model
        } else {
            // Upload the file and get the return file model
            $sourceFileModel = $this->sourceRouter->getFileModel(
                $data['source_file_id']
            );
        }

        // Set image modified variable
        $imageModified = false;

        if (defined('CLONING_MODE') && CLONING_MODE === true) {
            unset($data['ansel_image_id']);
        }

        // Check if there is an image id
        if (isset($data['ansel_image_id']) && $data['ansel_image_id']) {
            // Get record query builder
            $anselRecord = $this->recordBuilder->get('ansel:Image');

            // Filter the record
            $anselRecord->filter('id', $data['ansel_image_id']);

            // Get the record
            $anselRecord = $anselRecord->first();

            /** @var ImageRecord $anselRecord */

            // Check if crop properties have been modified
            $x = (int) $data['x'];
            $y = (int) $data['y'];
            $width = (int) $data['width'];
            $height = (int) $data['height'];
            $sourceFileId = (int) $data['source_file_id'];

            if ($anselRecord &&
                (
                    $x !== $anselRecord->x ||
                    $y !== $anselRecord->y ||
                    $width !== $anselRecord->width ||
                    $height !== $anselRecord->height ||
                    $sourceFileId !== $anselRecord->original_file_id
                )
            ) {
                $imageModified = true;
            }
        } else {
            // If not, create a new record
            /** @var ImageRecord $anselRecord */
            $anselRecord = $this->recordBuilder->make('ansel:Image');

            // Set the upload date
            $anselRecord->setProperty('upload_date', $time);

            // The image has been "modified"
            $imageModified = true;
        }

        if (!$anselRecord) {
            return false;
        }

        // Update info on record
        $anselRecord->setProperty('site_id', $this->siteId);
        $anselRecord->setProperty('source_id', $sourceId);
        $anselRecord->setProperty('content_id', $contentId);
        $anselRecord->setProperty('field_id', $fieldSettings->field_id);
        $anselRecord->setProperty('content_type', $fieldSettings->type);
        $anselRecord->setProperty('original_location_type', $uploadType);
        $anselRecord->setProperty('original_file_id', $data['source_file_id']);
        $anselRecord->setProperty('width', $data['width']);
        $anselRecord->setProperty('height', $data['height']);
        $anselRecord->setProperty('x', $data['x']);
        $anselRecord->setProperty('y', $data['y']);
        $anselRecord->setProperty('position', $data['order']);
        $anselRecord->setProperty('member_id', $this->memberId);

        $anselRecord->setProperty('title', $data['title'] ?? '');
        $anselRecord->setProperty('description', $data['description'] ?? '');

        // Old checkbox submits "true", new toggle field submits "y" or "n"
        $anselRecord->setProperty(
            'cover',
            (isset($data['cover']) && in_array($data['cover'], ['true', 'y'])) ? 1 : 0
        );

        if ($sourceFileModel) {
            $anselRecord->setProperty(
                'original_extension',
                $sourceFileModel->extension
            );

            $anselRecord->setProperty(
                'original_filesize',
                $sourceFileModel->filesize
            );
        }

        if ($rowId) {
            $anselRecord->setProperty('row_id', $rowId);
        }

        if ($colId) {
            $anselRecord->setProperty('col_id', $colId);
        }

        // Check if order is over max
        if ($fieldSettings->max_qty) {
            $order = (int) $data['order'];
            $anselRecord->setProperty(
                'disabled',
                $order > $fieldSettings->max_qty ? 1 : 0
            );
        } else {
            $anselRecord->setProperty('disabled', 0);
        }

        if (ee()->extensions->active_hook('ansel_save_row')) {
            $anselRecord = ee()->extensions->call('ansel_save_row', $anselRecord);
        }

        // Save the record (this will make the ID available for us later
        // if this is a new record, and also save data if the image exists
        // and is not modified
        if (!$isLivePreview) {
            $anselRecord->save();

            $this->updateFileUsage($anselRecord, $contentId, $contentType);

            // If image has not been modified, end processing
            if (! $imageModified || ! $sourceFileModel) {
                // @todo add setting to optionally keep this in sync
                // If the image is not changed, but the metadata is,
                // then we need to update the native files table.
                $this->sourceRouter->updateFileAttributes(
                    $anselRecord->file_id,
                    [
                        'title' => $anselRecord->title,
                        'description' => $anselRecord->description,
                    ]
                );

                return $anselRecord;
            }
        }

        $oldHighQualDirName = $anselRecord->getHighQualityDirectoryName();
        $oldThumbDirName = $anselRecord->getThumbDirectoryName();
        $anselRecord->setProperty('upload_location_type', $saveType);
        $anselRecord->setProperty('upload_location_id', $saveLocation->uploadLocationId);
        $anselRecord->setProperty('directory_id', $saveLocation->directoryId);
        $highQualityDirName = $anselRecord->getHighQualityDirectoryName();
        $thumbDirName = $anselRecord->getThumbDirectoryName();

        // Let's get a locally cached version of the source file
        $localSourceFile = $this->sourceRouter->cacheFileLocallyById(
            $sourceFileModel->file_id
        );

        /**
         *  Run image manipulations
         */

        // Get high quality image
        $this->manipulateImage->x = $data['x'];
        $this->manipulateImage->y = $data['y'];
        $this->manipulateImage->width = $data['width'];
        $this->manipulateImage->height = $data['height'];
        $this->manipulateImage->maxWidth = $fieldSettings->max_width;
        $this->manipulateImage->maxHeight = $fieldSettings->max_height;
        $this->manipulateImage->forceJpg = $fieldSettings->force_jpg;
        $this->manipulateImage->forceWebp = $fieldSettings->force_webp;
        $this->manipulateImage->quality = 100;
        $this->manipulateImage->optimize = false;
        $highQualImage = $this->manipulateImage->run($localSourceFile);
        $pathInfo = pathinfo($highQualImage);
        $upload = "{$pathInfo['dirname']}/{$sourceFileModel->filename}";
        $upload .= "-{$anselRecord->id}-{$time}.{$pathInfo['extension']}";
        $this->copy($highQualImage, $upload);

        // Get final image size
        $finalImageSize = getimagesize($highQualImage);

        // Set up the sourceRouter for save location
        $this->sourceRouter->setSource($saveType);

        // Upload high quality image
        $this->sourceRouter->uploadFile(
            location: $saveLocation,
            filePath: $upload,
            subFolder: "{$highQualityDirName}/{$anselRecord->id}",
            anselRecord: $anselRecord
        );

        // Remove the file
        if (file_exists($upload)) {
            unlink($upload);
        }

        // Get ansel thumbnail
        $thumbSize = $this->calcThumbSize($finalImageSize[0]);
        $this->manipulateImage->x = 0;
        $this->manipulateImage->y = 0;
        $this->manipulateImage->width = $finalImageSize[0];
        $this->manipulateImage->height = $finalImageSize[1];
        $this->manipulateImage->maxWidth = $thumbSize ?
            $thumbSize['width'] :
            $finalImageSize[0];
        $this->manipulateImage->maxHeight = $thumbSize ?
            $thumbSize['height'] :
            $finalImageSize[1];
        $this->manipulateImage->quality = 90;
        $this->manipulateImage->optimize = true;
        $thumbImage = $this->manipulateImage->run($highQualImage);
        $pathInfo = pathinfo($thumbImage);
        $upload = "{$pathInfo['dirname']}/{$sourceFileModel->filename}";
        $upload .= "-{$anselRecord->id}-{$time}.{$pathInfo['extension']}";
        $this->copy($thumbImage, $upload);

        // Upload the thumbnail
        $this->sourceRouter->uploadFile(
            location: $saveLocation,
            filePath: $upload,
            subFolder: "{$thumbDirName}/{$anselRecord->id}",
            anselRecord: $anselRecord,
        );

        // Remove the file
        if (file_exists($upload)) {
            unlink($upload);
        }

        // Get standard image
        $this->manipulateImage->x = $data['x'];
        $this->manipulateImage->y = $data['y'];
        $this->manipulateImage->width = $data['width'];
        $this->manipulateImage->height = $data['height'];
        $this->manipulateImage->maxWidth = $fieldSettings->max_width;
        $this->manipulateImage->maxHeight = $fieldSettings->max_height;
        $this->manipulateImage->quality = $fieldSettings->quality;
        $this->manipulateImage->optimize = true;
        $standardImage = $this->manipulateImage->run($localSourceFile);
        $pathInfo = pathinfo($standardImage);
        $upload = "{$pathInfo['dirname']}/{$sourceFileModel->filename}";
        $upload .= "-{$anselRecord->id}-{$time}.{$pathInfo['extension']}";
        $this->copy($standardImage, $upload);

        // Add the file to the source
        $saveFileModel = $this->sourceRouter->addFile(
            location: $saveLocation,
            filePath: $upload,
            anselRecord: $anselRecord
        );

        // Remove the file
        if (file_exists($upload)) {
            unlink($upload);
        }

        // Remove other temp files
        if (file_exists($localSourceFile)) {
            unlink($localSourceFile);
        }

        if (file_exists($highQualImage)) {
            unlink($highQualImage);
        }

        if (file_exists($thumbImage)) {
            unlink($thumbImage);
        }

        if (file_exists($standardImage)) {
            unlink($standardImage);
        }

        // Check if we should delete old images
        if ($anselRecord->getProperty('file_id')) {
            $location = UploadLocation::getUploadLocationByIdentifier($anselRecord->getProperty('upload_location_id'));

            // Set up the source router location type
            $this->sourceRouter->setSource(
                $location->type
            );

            // Delete the high quality file
            $this->sourceRouter->deleteFile(
                $location,
                $anselRecord->getBasename(),
                "{$oldHighQualDirName}/{$anselRecord->id}"
            );

            // Delete the thumbnail file
            $this->sourceRouter->deleteFile(
                $location,
                $anselRecord->getBasename(),
                "{$oldThumbDirName}/{$anselRecord->id}"
            );

            // Delete the standard file
            $this->sourceRouter->deleteFile(
                $location,
                $anselRecord->getProperty('file_id')
            );
        }

        // Update the record with final items
        $anselRecord->setProperty('file_id', $saveFileModel->file_id);
        $anselRecord->setProperty('filename', $saveFileModel->filename);
        $anselRecord->setProperty('extension', $saveFileModel->extension);
        $anselRecord->setProperty('filesize', $saveFileModel->filesize);
        $anselRecord->setProperty('modify_date', $time);

        // Final record save
        if (!$isLivePreview) {
            $anselRecord->save();
        }

        return $anselRecord;
    }

    private function copy(
        string $imageUrl,
        string $uploadPath
    ): void  {
        try {
            $ch = curl_init($imageUrl);

            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            ];

            if (
                config_item('ansel_http_user')
                && config_item('ansel_http_pass')
            ) {
                $curlOptions[CURLOPT_USERPWD] = sprintf(
                    '%s:%s',
                    config_item('ansel_http_user'),
                    config_item('ansel_http_pass')
                );
            }

            curl_setopt_array($ch, $curlOptions);

            $data = curl_exec($ch);

            if ($data === false) {
                throw new RuntimeException(sprintf(
                    'copy(%s) download failed: %s',
                    $uploadPath,
                    curl_error($ch),
                ));
            }

            curl_close($ch);

            if (file_put_contents($uploadPath, $data) === false) {
                throw new RuntimeException(sprintf(
                    'copy(%s) saving file failed',
                    $uploadPath
                ));
            }
        } catch (\Exception $curlException) {
            try {
                copy($imageUrl, $uploadPath);
            } catch (\Exception $copyException) {
                throw new RuntimeException($curlException->getMessage() . $copyException->getMessage());
            }
        }
    }

    /**
     * Append each file used to the session cache, which will be used to
     * set flashdata in the AfterChannelEntrySave hook, which will then
     * be used by the CoreBoot hook on next page load to save the usage
     * data for the files. There is no need to manage removed or deleted
     * files form the Ansel field b/c the core model just wipes them all
     * out every time an entry is saved. We can thank the core Model->save()
     * function, specifically the part that updates all the related associations.
     */
    private function updateFileUsage(
        Image $anselRecord,
        int $contentId,
        string $contentType
    ): void {
        if (!in_array($contentType, ['channel', 'category'])) {
            return;
        }

        $fileId = $anselRecord->file_id;
        $column = $contentType === 'category' ? 'cat_id' : 'entry_id';

        $usages = ee()->session->cache('ansel', 'usages');

        if (!$usages) {
            $usages = [];
        }

        $updates = array_merge($usages, [[
            $column => $contentId,
            'file_id' => $fileId,
        ]]);

        ee()->session->set_cache('ansel', 'usages', $updates);
    }

    /**
     * Calculate thumbnail size
     *
     * @param int $imageWidth
     * @return bool|array {
     *     @var float $ratio
     *     @var int $width
     *     @var int $width
     * }
     */
    private function calcThumbSize($imageWidth)
    {
        // Set the thumbnail max width
        $maxWidth = 336;

        // Check if generating thumbnail is necesary
        if ($imageWidth <= $maxWidth) {
            return false;
        }

        // Get the ratio
        $ratio = (float) $maxWidth / $imageWidth;

        return array(
            'ratio' => $ratio,
            'width' => $maxWidth,
            'height' => (int) round($imageWidth * $ratio)
        );
    }
}
