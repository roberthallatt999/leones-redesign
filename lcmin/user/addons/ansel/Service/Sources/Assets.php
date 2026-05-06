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

use BoldMinded\Ansel\Model\Source as SourceModel;
use BoldMinded\Ansel\Model\File as FileModel;
use ExpressionEngine\Service\Database\Query as QueryBuilder;
use ExpressionEngine\Service\Model\Model;
use BoldMinded\Ansel\Service\FileCacheService;

/**
 * Class Assets
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class Assets extends BaseSource
{
    /**
     * @var \Assets_helper $assetsHelper
     */
    private $assetsHelper;

    /**
     * @var \Assets_lib $assetsLib
     */
    private $assetsLib;

    /**
     * @var QueryBuilder $queryBuilder
     */
    private $queryBuilder;

    /**
     * @var SourceModel $sourceModel
     */
    private $sourceModel;

    /**
     * @var FileModel $fileModel
     */
    private $fileModel;

    /**
     * @var FileCacheService $fileCacheService
     */
    private $fileCacheService;

    /**
     * Constructor
     *
     * @param \Assets_helper $assetsHelper
     * @param \Assets_lib $assetsLib
     * @param QueryBuilder $queryBuilder
     * @param SourceModel $sourceModel
     * @param FileModel $fileModel
     * @param FileCacheService $fileCacheService
     */
    public function __construct(
        \Assets_helper $assetsHelper,
        \Assets_lib $assetsLib,
        QueryBuilder $queryBuilder,
        SourceModel $sourceModel,
        FileModel $fileModel,
        FileCacheService $fileCacheService
    ) {
        // Inject dependencies
        $this->assetsHelper = $assetsHelper;
        $this->assetsLib = $assetsLib;
        $this->queryBuilder = $queryBuilder;
        $this->sourceModel = $sourceModel;
        $this->fileModel = $fileModel;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * Get file chooser link
     *
     * @param UploadLocation $location Source identifier
     * @param string $lang
     * @return string
     */
    public function getFileChooserLink(
        UploadLocation $location,
        $lang = null
    ): string {
        // Include sheet resources
        $this->assetsHelper->include_sheet_resources();

        // Split up the identifier
        $identifier = explode('-', $location->uploadLocationId);

        // Set up language
        $lang = $lang ?: lang('choose_existing_images');

        // Build the link
        $link = '<a class="btn action js-ansel-assets-choose-existing-image" ';
        $link .= 'data-file-dir="';
        $link .= "{$identifier[0]}:{$identifier[1]}" . '">';
        $link .= "{$lang}</a>";

        // Return link
        return $link;
    }

    /**
     * Upload file to the source storage
     *
     * @param UploadLocation $identifier Source identifier
     * @param string $filePath
     * @param string $subFolder
     * @param bool $insertTimestamp
     * @return string Full file upload path
     * @throws \Exception
     */
    public function uploadFile(
        UploadLocation $location,
        $filePath,
        $subFolder = null,
        $insertTimestamp = false
    ): string {
        // Upload the file
        $file = $this->addOrUploadFile(
            $location,
            $filePath,
            $subFolder,
            $insertTimestamp
        );

        // Make sure there is a result
        if (! $file) {
            return '';
        }

        // Return the path
        return $file->server_path();
    }

    /**
     * Delete file from source storage
     *
     * @param mixed $identifier Source identifier
     * @param string $fileName
     * @param string $subFolder
     * @throws \Exception
     */
    public function deleteFile(
        UploadLocation $location,
        $fileName,
        $subFolder = null
    ): void {
        // Get the separator
        $sep = DIRECTORY_SEPARATOR;

        // Split the identifier appropriately
        $identifier = explode('-', $location->uploadLocationId);
        $dirType = $identifier[0];
        $dirId = $identifier[1];

        // Start params
        $params = array(
            'parent_id' => null,
        );

        // Check if this is EE or Other
        if ($dirType === 'ee') {
            // Set params
            $params['filedir_id'] = $dirId;

            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                (object) array(
                    'source_type' => 'ee',
                    'filedir_id' => $dirId
                )
            );
        } else {
            // Set params
            $params['source_id'] = $dirId;

            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                $this->assetsLib->get_source_row_by_id($dirId)
            );
        }

        // Get the folder ID
        $folderId = $this->assetsLib->get_folder_id_by_params(
            $params
        );

        // Set full path variable
        $fullPath = '';

        // Start parent ID variable
        $parentId = $folderId;

        // Check if sub folder
        if ($subFolder) {
            // Normalize sub folder
            $subFolder = rtrim($subFolder, $sep);
            $subFolder = ltrim($subFolder, $sep);

            // Get sub folder array
            $subFolderArray = explode($sep, $subFolder);

            foreach ($subFolderArray as $path) {
                $fullPath .= $path . '/';

                $subDirResult = $this->queryBuilder->select('*')
                    ->from('assets_folders')
                    ->where('parent_id', $parentId)
                    ->where('full_path', $fullPath)
                    ->get();

                if ($subDirResult->num_rows < 1) {
                    return;
                }

                $dir = $subDirResult->row_array();
                $parentId = $dir['folder_id'];
            }
        }

        // Get the folder row
        $fileRow = $this->queryBuilder->select('*')
            ->from('assets_files')
            ->where('folder_id', $parentId)
            ->where('file_name', $fileName)
            ->get()
            ->row();

        // Make sure the file row exists
        if (! $fileRow) {
            return;
        }

        // Delete the file
        @$source->delete_file($fileRow->file_id, true);
    }

    /**
     * Add file and record to the source
     *
     * @param UploadLocation $location Source identifier
     * @param string $filePath
     * @return FileModel
     * @throws \Exception
     */
    public function addFile(UploadLocation $location, $filePath): Model
    {
        // Get file info
        $fileSize = filesize($filePath);
        $imageSize = getimagesize($filePath);

        // Upload the file
        $file = $this->addOrUploadFile(
            $location,
            $filePath
        );

        // Clone the file model
        $fileModel = clone $this->fileModel;

        // Update the file model
        $fileModel->location_type = 'assets';
        $fileModel->location_identifier = $location->uploadLocationId;
        $fileModel->file_id = $file->file_id();
        $fileModel->setFileLocation($file->server_path());
        $fileModel->filesize = $fileSize;
        $fileModel->width = $imageSize[0];
        $fileModel->height = $imageSize[1];
        $fileModel->url = $file->url();

        // Return the file model
        return $fileModel;
    }

    /**
     * Remove file and record from the source
     *
     * @param mixed $fileId
     * @throws \Exception
     */
    public function removeFile($fileId)
    {
        // Get file
        $file = $this->assetsLib->get_file_by_id($fileId);

        // Get folder row
        $folderRow = $file->folder_row();

        // Check if this is EE or Other
        if ($folderRow->source_type === 'ee') {
            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                (object) array(
                    'source_type' => 'ee',
                    'filedir_id' => $folderRow->filedir_id
                )
            );
        } else {
            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                $this->assetsLib->get_source_row_by_id($folderRow->source_id)
            );
        }

        // Delete the file
        @$source->delete_file($fileId, true);
    }

    /**
     * Get source URL
     *
     * @param UploadLocation $location Source identifier
     * @return string
     * @throws \Exception
     */
    public function getSourceUrl(UploadLocation $location): string
    {
        // Split the identifier appropriately
        $identifier = explode('-', $location->uploadLocationId);
        $dirType = $identifier[0];
        $dirId = $identifier[1];

        // Check if this is EE or Other
        if ($dirType === 'ee') {
            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                (object) array(
                    'source_type' => 'ee',
                    'filedir_id' => $dirId
                )
            );

            // Get source settings
            $settings = $source->settings();

            // Set the URL
            $url = rtrim($settings->url, '/') . '/';
        } else {
            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                $this->assetsLib->get_source_row_by_id($dirId)
            );

            // Get the source settings
            $settings = $source->settings();

            // Build URL
            $url = $settings->url_prefix;
            $url = rtrim($url, '/') . '/';
            $url .= $settings->subfolder;
            $url = rtrim($url, '/') . '/';
        }

        // Return the URL
        return $url;
    }

    /**
     * Get file URL
     *
     * @param mixed $fileId File identifier
     * @return string
     */
    public function getFileUrl($fileId): string
    {
        // Get file
        $file = $this->assetsLib->get_file_by_id($fileId);

        if (! $file) {
            return '';
        }

        // Return file URL
        return $file->url();
    }

    /**
     * Get file model
     *
     * @param mixed $fileId File identifier
     * @return Model|null
     */
    public function getFileModel($fileId): null|Model
    {
        // Get file
        $file = $this->assetsLib->get_file_by_id($fileId);

        if (! $file) {
            return null;
        }

        // Get source
        $source = $file->source();

        // Set folder ID
        $folderId = "{$source->get_source_type()}-{$source->get_source_id()}";

        // Clone the file model
        $fileModel = clone $this->fileModel;

        // Update the file model
        $fileModel->location_type = 'assets';
        $fileModel->location_identifier = $folderId;
        $fileModel->file_id = $file->file_id();
        $fileModel->setFileLocation($file->server_path());
        $fileModel->filesize = $file->size();
        $fileModel->width = $file->width();
        $fileModel->height = $file->height();
        $fileModel->url = $file->url();

        // Return the file model
        return $fileModel;
    }

    /**
     * Cache file locally by ID
     *
     * @param mixed $fileId File identifier
     * @return string
     */
    public function cacheFileLocallyById($fileId): string
    {
        // Get file
        $file = $this->assetsLib->get_file_by_id($fileId);

        // Get source
        $source = $file->source();

        // Set folder ID
        if ($source->get_source_type() === 'ee') {
            // Return the cache file
            return $this->fileCacheService->cacheByPath($file->server_path());
        } else {
            // Return the cache file
            return $this->fileCacheService->cacheByPath($file->url());
        }
    }

    /**
     * Get source models
     *
     * @param array $ids
     * @return array
     * @throws \Exception
     */
    public function getSourceModels($ids): array
    {
        $sources = array();

        foreach ($ids as $id) {
            $key = $id;

            // Split the identifier appropriately
            $id = explode('-', $id);
            $dirType = $id[0];
            $dirId = $id[1];

            // Clone the source model
            $sourceModel = clone $this->sourceModel;

            // Check if this is EE or Other
            if ($dirType === 'ee') {
                // Get source
                $source = $this->assetsLib->instantiate_source_type(
                    (object) array(
                        'source_type' => 'ee',
                        'filedir_id' => $dirId
                    )
                );

                // Get the source settings
                $settings = $source->settings();

                // Set the URL
                $sourceModel->url = rtrim($settings->url, '/') . '/';
            } else {
                // Get source
                $source = $this->assetsLib->instantiate_source_type(
                    $this->assetsLib->get_source_row_by_id($dirId)
                );

                // Get the source settings
                $settings = $source->settings();

                // Build URL
                $url = $settings->url_prefix;
                $url = rtrim($url, '/') . '/';
                $url .= $settings->subfolder;
                $url = rtrim($url, '/') . '/';

                // Set the URL
                $sourceModel->url = $url;
            }

            // Set the path
            $sourceModel->path = $source->get_folder_server_path('/');

            // Add the source model to the array
            $sources[$key] = $sourceModel;
        }

        // Return the sources
        return $sources;
    }

    /**
     * Get file models
     *
     * @param array $ids
     * @return array
     */
    public function getFileModels($ids): array
    {
        // Get files
        $files = $this->assetsLib->get_files(array(
            'file_ids' => $ids
        ));

        // Return files
        $returnFiles = array();

        // Iterate through files
        foreach ($files as $file) {
            /** @var \Assets_base_file $file */

            // Get source
            $source = $file->source();

            // Set folder ID
            $folderId = "{$source->get_source_type()}-{$source->get_source_id()}";

            // Clone the file model
            $fileModel = clone $this->fileModel;

            // Update the file model
            $fileModel->location_type = 'assets';
            $fileModel->location_identifier = $folderId;
            $fileModel->file_id = $file->file_id();
            $fileModel->setFileLocation($file->server_path());
            $fileModel->filesize = $file->size();
            $fileModel->width = $file->width();
            $fileModel->height = $file->height();
            $fileModel->url = $file->url();

            $returnFiles[$fileModel->file_id] = $fileModel;
        }

        // Return the array of models
        return $returnFiles;
    }

    /**
     * Upload or add file (called from either uploadFile or addFile method)
     *
     * @param UploadLocation $location Source identifier
     * @param string $filePath
     * @param string $subFolder
     * @param bool $insertTimestamp
     * @return null|\Assets_base_file
     * @throws \Exception
     */
    private function addOrUploadFile(
        UploadLocation $location,
        $filePath,
        $subFolder = null,
        $insertTimestamp = false
    ) {
        // Get the separator
        $sep = DIRECTORY_SEPARATOR;

        // Get filename
        $path = pathinfo($filePath);

        // Add path info filename
        if ($insertTimestamp) {
            $time = time();
            $fileName = "{$path['filename']}-{$time}.{$path['extension']}";
        } else {
            $fileName = $path['basename'];
        }

        // Split the identifier appropriately
        $identifier = explode('-', $location->uploadLocationId);
        $dirType = $identifier[0];
        $dirId = $identifier[1];

        // Start params
        $params = array(
            'parent_id' => null,
        );

        // Check if this is EE or Other
        if ($dirType === 'ee') {
            // Set params
            $params['filedir_id'] = $dirId;

            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                (object) array(
                    'source_type' => 'ee',
                    'filedir_id' => $dirId
                )
            );
        } else {
            // Set params
            $params['source_id'] = $dirId;

            // Get source
            $source = $this->assetsLib->instantiate_source_type(
                $this->assetsLib->get_source_row_by_id($dirId)
            );
        }

        // Get the folder ID
        $folderId = $this->assetsLib->get_folder_id_by_params(
            $params
        );

        // Set full path variable
        $fullPath = '';

        // Start parent ID variable
        $parentId = $folderId;

        // Check if sub folder
        if ($subFolder) {
            // Normalize sub folder
            $subFolder = rtrim($subFolder, $sep);
            $subFolder = ltrim($subFolder, $sep);

            // Get sub folder array
            $subFolderArray = explode($sep, $subFolder);

            foreach ($subFolderArray as $path) {
                $fullPath .= $path . '/';

                $subDirResult = $this->queryBuilder->select('*')
                    ->from('assets_folders')
                    ->where('parent_id', $parentId)
                    ->where('full_path', $fullPath)
                    ->get();

                if ($subDirResult->num_rows < 1) {
                    $dir = $source->create_folder("{$parentId}/{$path}");
                } else {
                    $dir = $subDirResult->row_array();
                }

                $parentId = $dir['folder_id'];
            }
        }

        // Get the folder row
        $folderRow = $this->queryBuilder->select('*')
            ->from('assets_folders')
            ->where('folder_id', $parentId)
            ->get()
            ->row();

        // Check if the source file exists
        if ($source->source_file_exists($folderRow, $fileName)) {
            $path = pathinfo($fileName);
            $unique = uniqid();
            $fileName = $path['dirname'] !== '.' ? "{$path['dirname']}/" : '';
            $fileName .= "{$path['filename']}-{$unique}.{$path['extension']}";
        }

        // Upload image
        $result = $source->upload_file($parentId, $filePath, $fileName);

        // Make sure there is a result
        if (! $result) {
            return null;
        }

        // Get the file from the assets api and return it
        return $this->assetsLib->get_file_by_id($result['file_id']);
    }

    public function isSymLink(UploadLocation $location): bool
    {
        // @todo
        return false;
    }
}
