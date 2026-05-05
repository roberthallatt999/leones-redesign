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

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Logger;
use BoldMinded\Ansel\Record\Image;
use BoldMinded\Ansel\Traits\FileUploadDestinations;
use ExpressionEngine\Addons\FilePicker\Service\FilePicker\FilePicker;
use ExpressionEngine\Model\File\FileSystemEntity;
use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BoldMinded\Ansel\Model\Source as SourceModel;
use BoldMinded\Ansel\Model\File as FileModel;
use ExpressionEngine\Addons\FilePicker\Service\FilePicker\Link;
use ExpressionEngine\Model\File\UploadDestination;
use ExpressionEngine\Model\File\File;
use BoldMinded\Ansel\Service\FileCacheService;
use ExpressionEngine\Service\Model\Model;

/**
 * Class Ee
 */
class Ee extends BaseSource
{
    private static $directoryCache = [];

    /**
     * @var FilePicker $filePicker
     */
    private $filePicker;

    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

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
     * @var \File_model $eeFileModel
     */
    private $eeFileModel;

    /**
     * @var \Filemanager $eeFileManager
     */
    private $eeFileManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var int $siteId
     */
    private $siteId;

    /**
     * @var int $userId
     */
    private $userId;

    use FileUploadDestinations;

    public function __construct(
        FilePicker       $filePicker,
        RecordBuilder    $recordBuilder,
        SourceModel      $sourceModel,
        FileModel        $fileModel,
        FileCacheService $fileCacheService,
        \File_model      $eeFileModel,
        \Filemanager     $eeFileManager,
        Logger           $logger,
        int              $siteId,
        int              $userId
    ) {
        // Inject dependencies
        $this->filePicker = $filePicker;
        $this->recordBuilder = $recordBuilder;
        $this->sourceModel = $sourceModel;
        $this->fileModel = $fileModel;
        $this->fileCacheService = $fileCacheService;
        $this->eeFileModel = $eeFileModel;
        $this->eeFileManager = $eeFileManager;
        $this->logger = $logger;
        $this->siteId = $siteId;
        $this->userId = $userId;
    }

    public function isSymLink(UploadLocation $location): bool
    {
        $file = ee('Model')->make('FileSystemEntity');

        $fileData = [
            'upload_location_id' => $location->uploadLocationId,
            'directory_id' => $location->directoryId,
        ];
        $file->set($fileData);

        $isLink = is_link(rtrim($file->getAbsolutePath(), '/'));

        return $isLink;
    }

    public function getFileChooserLink(
        UploadLocation $location,
        string $lang = ''
    ): string {
        // Get the upload destination
        $uploadDestination = $this->recordBuilder->get('UploadDestination');

        // Filter the upload destination
        $uploadDestination->filter('id', $location->uploadLocationId);

        // Get upload destination result
        $uploadDestination = $uploadDestination->first();

        if (!$uploadDestination) {
            return ee('CP/Alert')->makeInline('ansel-releases')
                ->asWarning()
                ->cannotClose()
                ->addToBody('No valid upload location has been defined for this field, or the upload location does not exist. Double check this field\'s settings.')
                ->render();
        }

        /** @var UploadDestination $uploadDestination */

        // Get the modal view
        $modalView = $uploadDestination->getProperty('default_modal_view');

        // Set directories
        $this->filePicker->setDirectories($location->uploadLocationId);

        // Set up language
        $lang = $lang ?: lang('choose_an_existing_image');

        // Get link
        /** @var Link $link */
        $link = $this->filePicker->getLink($lang);

        // Set attributes
        $link->setAttribute(
            'class',
            'button button--default button--small js-ansel-ee-choose-existing-image'
        );

        // Set modal view
        if ($modalView === 'list') {
            $link->asList();
        } elseif ($modalView === 'thumb') {
            $link->asThumbs();
        }

        $link
            ->enableFilters()
            ->enableUploads();

        // Because the FilePicker link generator doesn't let you set which subdirectory to open directly into.
        // Modify the rendered URL by appending a new query string param for the specific directory ID.
        if ($location->directoryId !== 0) {
            return preg_replace(
                '/requested_directory=(\d+)/',
                sprintf('requested_directory=%d&directory_id=%d', $location->uploadLocationId, $location->directoryId),
                $link->render()
            );
        }

        return $link->render();
    }

    private function cleanFileName(string $input): string
    {
        $input = trim($input);

        // Split on the last dot only
        $dotPos = strrpos($input, '.');
        $base   = $dotPos === false ? $input : substr($input, 0, $dotPos);
        $ext    = $dotPos === false ? ''     : substr($input, $dotPos + 1);

        if (function_exists('transliterator_transliterate')) {
            $base = transliterator_transliterate('Any-Latin; Latin-ASCII', $base);
        } elseif (function_exists('iconv')) {
            $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        }

        // Replace disallowed chars in base with hyphen; allow A-Z a-z 0-9 _ -
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base);
        // Collapse multiple separators and trim
        $base = preg_replace('/[-_]{2,}/', '-', $base);
        $base = trim($base, '-_');

        if ($base === '') {
            $base = 'file';
        }

        // Sanitize extension strictly to alphanumerics (and lowercase)
        $ext = strtolower(preg_replace('~[^A-Za-z0-9]+~', '', $ext) ?? '');

        return $ext !== '' ? "{$base}.{$ext}" : $base;
    }

    public function uploadFile(
        UploadLocation $location,
        string $filePath,
        string|null $subFolder = null,
        Image|null $anselRecord = null,
        bool $insertTimestamp = false,
    ): string {
        try {
            ee()->load->library('filemanager');
            ee()->load->library('upload');

            // Get the real path
            $filePath = realpath($filePath);

            $uploadPrefs = $this->eeFileManager->fetch_upload_dir_prefs($location->uploadLocationId, true);
            /** @var \ExpressionEngine\Library\Filesystem\Filesystem $fs */
            $fs = $uploadPrefs['directory']->getFilesystem();

            // Get path info
            $path = pathinfo($filePath);

            // Set the destination filename
            if ($insertTimestamp) {
                $time = time();
                $destFileName = "{$path['filename']}-{$time}.{$path['extension']}";
            } else {
                $destFileName = $path['basename'];
            }

            $destFileName = $this->cleanFileName($destFileName);

            /** @var \ExpressionEngine\Model\File\FileSystemEntity $file */
            $file = ee('Model')->make('FileSystemEntity');
            $fileData = [
                'upload_location_id' => $location->uploadLocationId,
                'directory_id' => $location->directoryId,
                'file_name' => $destFileName,
            ];
            if ($subFolder) {
                $fileData['_subfolderPath'] = $subFolder;
            }
            $file->set($fileData);

            if ($subFolder) {
                $fileAbsolutePath = $file->getBaseServerPath() . $subFolder . DIRECTORY_SEPARATOR . $destFileName;
            } else {
                $fileAbsolutePath = $file->getAbsolutePath();
            }

            $existsOnFileSystem = $fs->exists(
                $fileAbsolutePath
            );

            // Check if the file exists and set a non-conflicting name if so
            if ($existsOnFileSystem) {
                $destPathInfo = pathinfo($destFileName);
                $unique = uniqid();
                $destFileName = "{$destPathInfo['filename']}";
                $destFileName .= "-{$unique}.{$destPathInfo['extension']}";

                $file->setProperty('file_name', $destFileName);
            }

            ee()->upload->overwrite = true;
            ee()->upload->upload_destination = $uploadPrefs['directory'];

            if ($file->getSubfoldersPath() !== '') {
                $uploadPath = $file->getBaseServerPath() . $file->getSubfoldersPath();
            } else {
                $uploadPath = $uploadPrefs['server_path'];
            }

            if ($subFolder) {
                $subFolderPath = $uploadPath . $subFolder;

                if (!$fs->isDir($subFolderPath)) {
                    $fs->mkDir($subFolderPath);
                }

                $uploadPath = $subFolderPath;
            }

            ee()->upload->upload_path = $uploadPath;

            $content = file_get_contents($filePath);
            $fileAbsolutePath = $uploadPath . DIRECTORY_SEPARATOR . $destFileName;

            if ($fs->isFile($fileAbsolutePath)) {
                return $fileAbsolutePath;
            }

            $rawUploadResult = ee()->upload->raw_upload($destFileName, $content);

            if (!$rawUploadResult) {
                $this->logger->developer(
                    sprintf(
                        '[Ansel] Unable to upload raw file %s to %s',
                        $filePath,
                        $destFileName
                    )
                );
            }

            return $file->getAbsolutePath();
        } catch (\Exception $exception) {
            $this->logger->developer($exception->getMessage());
            show_error($exception->getMessage());

            return  '';
        }
    }

    public function deleteFile(
        UploadLocation $location,
        string $fileName,
        string|null $subFolder = null
    ): void {
        try {
            // Set the separator
            $sep = DIRECTORY_SEPARATOR;

            // Get the upload destination
            $uploadDestination = $this->recordBuilder->get('UploadDestination');

            // Filter the upload destination
            $uploadDestination->filter('id', $location->uploadLocationId);

            // Get upload destination result
            $uploadDestination = $uploadDestination->first();

            // Make sure everything is okay
            if (!$uploadDestination) {
                return;
            }

            // Get the path
            $path = realpath($uploadDestination->getProperty('server_path'));
            $path = rtrim($path, $sep) . $sep;
            $origPath = $path;

            // Check if a sub folder has been specified
            if ($subFolder) {
                $path .= rtrim(ltrim($subFolder, $sep), $sep) . $sep;
            }

            // Set the full file path
            $fullFilePath = "{$path}{$fileName}";

            // If the file exists, remove it
            if (is_file($fullFilePath)) {
                unlink($fullFilePath);
            }


            /**
             * Check if we can remove the subdirectories
             */

            if (!$subFolder) {
                return;
            }

            // Break up the sub folder
            $subArray = explode($sep, $subFolder);
            $subCount = count($subArray);

            // Iterate through directories and see if they can be removed
            for ($i = 0; $i < $subCount; $i++) {
                // Set up the sub directory path
                $dirPath = $origPath . implode($sep, $subArray) . $sep;

                // Check if the directory is empty
                if (count(glob("{$dirPath}*")) === 0 &&
                    is_dir($dirPath)
                ) {
                    rmdir($dirPath);
                }

                // Remove the last directory from the sub dir array
                array_pop($subArray);
            }
        } catch (\Exception $exception) {
            $this->logger->developer($exception->getMessage());
            show_error($exception->getMessage());
        }
    }

    public function updateFileAttributes(
        int $fileId,
        array $attributes
    ): void {
        // Get the file record query builder
        $file = $this->recordBuilder
            ->get('File')
            ->filter('file_id', $fileId)
            ->first();

        if (!$file) {
            return;
        }

        foreach ($attributes as $attribute => $value) {
            $file->setProperty($attribute, $value);
        }

        $file->save();
    }

    public function addFile(
        UploadLocation $location,
        string $filePath = '',
        Image|null $anselRecord = null
    ): Model {
        try {
            // Place the file
            $this->uploadFile(
                location: $location,
                filePath: $filePath
            );

            // Set the timestamp
            $timeStamp = time();

            // Get path info
            $pathInfo = pathinfo($filePath);

            // Get image size
            $imageSize = getimagesize($filePath);

            $fileName = $this->cleanFileName($pathInfo['basename']);

            $exists = $this->recordBuilder->get('File')
                ->filter('file_name', $fileName)
                ->filter('site_id', $this->siteId)
                ->filter('title', $pathInfo['filename'])
                ->filter('upload_location_id', $location->uploadLocationId)
                ->filter('directory_id', $location->directoryId)
                ->first();

            // Live Previews will ultimately call this multiple times, so don't
            // upload and create duplicate records of the same image.
            if ($this->isLivePreview && $exists) {
                return $exists;
            }

            // Make a file record
            /** @var File $file */
            $file = $this->recordBuilder->make('File');

            // Set model properties
            $file->setProperty('site_id', $this->siteId);
            $file->setProperty('title', $pathInfo['filename']);
            $file->setProperty('upload_location_id', $location->uploadLocationId);
            $file->setProperty('directory_id', $location->directoryId);
            $file->setProperty('mime_type', mime_content_type($filePath));
            $file->setProperty('file_name', $fileName);
            $file->setProperty('file_size', filesize($filePath));
            $file->setProperty('uploaded_by_member_id', $this->userId);
            $file->setProperty('upload_date', $timeStamp);
            $file->setProperty('modified_by_member_id', $this->userId);
            $file->setProperty('modified_date', $timeStamp);
            $file->setProperty(
                'file_hw_original',
                "{$imageSize[1]} {$imageSize[0]}"
            );

            // @todo add setting to optionally keep this in sync
            // Keep the native file table in sync with the cropped image
            if ($anselRecord && $anselRecord->title) {
                $file->setProperty('title', $anselRecord->title);
            }
            if ($anselRecord && $anselRecord->description) {
                $file->setProperty('description', $anselRecord->description);
            }

            // Save the file
            $file->save();

            // Get dimensions for manipulations
            /** @var \CI_DB_mysqli_result $dimensions */
            $dimensions = $this->eeFileModel->get_dimensions_by_dir_id($location->uploadLocationId);
            $dimensions = $dimensions->result_array();

            /**
             * @var UploadDestination $uploadDestination
             * @noinspection PhpUndefinedFieldInspection
             */
            $uploadDestination = $file->UploadDestination;

            // Run manipulations and thumbnails
            $this->eeFileManager->create_thumb(
                $file->getAbsolutePath(),
                array(
                    'directory' => $uploadDestination,
                    'server_path' => $uploadDestination->server_path,
                    'file_name' => $file->getProperty('file_name'),
                    'dimensions' => $dimensions,
                    'mime_type' => $file->getProperty('mime_type')
                ),
                true,
                false
            );

            $fileModel = clone $this->fileModel;

            // Update the file model
            $fileModel->location_type = 'ee';
            $fileModel->location_identifier = $location->uploadLocationId;
            $fileModel->file_id = $file->getProperty('file_id');
            $fileModel->setFileLocation($file->getAbsolutePath());
            $fileModel->filesize = $file->getProperty('file_size');
            $fileModel->width = $imageSize[0];
            $fileModel->height = $imageSize[1];

            // Return the file model
            return $fileModel;
        } catch (\Exception $exception) {
            $this->logger->developer($exception->getMessage());
            show_error($exception->getMessage());
        }

        return $this->fileModel;
    }

    public function removeFile(int $fileId)
    {
        try {
            // Get the file record query builder
            $file = $this->recordBuilder->get('File');

            // Filter the file
            $file->filter('file_id', $fileId);

            // Get the file result
            $file = $file->first();

            // Make sure everything is okay
            if (!$file) {
                return;
            }

            /** @var File $file */

            // Delete the file
            $this->deleteFile(
                new UploadLocation(
                    uploadLocationId: (int)$file->getProperty('upload_location_id'),
                    directoryId: (int)$file->getProperty('directory_id')
                ),
                $file->getProperty('file_name')
            );

            // Delete thumbnail
            $this->deleteFile(
                new UploadLocation(
                    uploadLocationId: (int)$file->getProperty('upload_location_id'),
                    directoryId: (int)$file->getProperty('directory_id')
                ),
                $file->getProperty('file_name'),
                '_thumbs'
            );

            // Get manipulations
            /** @var \CI_DB_mysqli_result $manipulations */
            $manipulations = $this->eeFileModel->get_dimensions_by_dir_id(
                $file->getProperty('upload_location_id')
            );
            $manipulations = $manipulations->result_array();

            // Iterate through manipulations
            foreach ($manipulations as $manipulation) {
                if (isset($manipulation['short_name'])) {
                    // Delete manipulation
                    $this->deleteFile(
                        new UploadLocation(
                            uploadLocationId: (int)$file->getProperty('upload_location_id'),
                            directoryId: (int)$file->getProperty('directory_id')
                        ),
                        $file->getProperty('file_name'),
                        "_{$manipulation['short_name']}"
                    );
                }
            }

            // Delete the file record
            $file->delete();
        } catch (\Exception $exception) {
            $this->logger->developer($exception->getMessage());
            show_error($exception->getMessage());
        }
    }

    private function loadSourceCache(UploadLocation $location)
    {
        $key = $location->uploadLocationId . '.' . $location->directoryId;

        if (array_key_exists($key, self::$directoryCache)) {
            return;
        }

        if ($location->directoryId) {
            /** @var FileSystemEntity $fileSystemEntity */
            $fileSystemEntity = ee('Model')->get('FileSystemEntity')
                ->filter('upload_location_id', $location->uploadLocationId)
                ->filter('directory_id', $location->directoryId)
                ->first();

            $url = $fileSystemEntity->getBaseUrl();
            $path = $fileSystemEntity->getBaseServerPath() . $fileSystemEntity->getSubfoldersPath();

            self::$directoryCache[$key] = [
                'url' => $url,
                'path' => $path,
            ];

            return;
        }

        // Get the upload destination
        $uploadDestination = $this->recordBuilder->get('UploadDestination');

        // Filter the upload destination
        $uploadDestination->filter('id', $location->uploadLocationId);

        // Get upload destination result
        $uploadDestination = $uploadDestination->first();

        // Make sure everything is okay
        if (! $uploadDestination) {
            self::$directoryCache[$key] = [
                'url' => '',
                'path' => '',
            ];

            $this->logger->developer(
                sprintf(
                    '[Ansel] A field is requesting access to a file upload location with the ID of %s, which does not exist.
                    Check all your Ansel field settings to ensure the Upload, Save, and Preview directories are properly configured.',
                    $location->uploadLocationId,
                )
            );

            return;
        }

        $url = rtrim($uploadDestination->getProperty('url'), '/') . '/';
        $path = $uploadDestination->server_path;

        self::$directoryCache[$key] = [
            'url' => $url,
            'path' => $path,
        ];
    }

    public function getSourceUrl(UploadLocation $location): string
    {
        $this->loadSourceCache($location);

        $key = $location->uploadLocationId . '.' . $location->directoryId;

        if (array_key_exists($key, self::$directoryCache)) {
            return self::$directoryCache[$key]['url'];
        }

        return '';
    }

    public function getSourcePath(UploadLocation $location): string
    {
        $this->loadSourceCache($location);

        $key = $location->uploadLocationId . '.' . $location->directoryId;

        if (array_key_exists($key, self::$directoryCache)) {
            return self::$directoryCache[$key]['path'];
        }

        return '';
    }

    public function getFileUrl(int $fileId): string
    {
        // Get the file record query builder
        $file = $this->recordBuilder->get('File');

        // Filter the file
        $file->filter('file_id', $fileId);

        // Get the file result
        $file = $file->first();

        // Check if we have a file
        if (! $file) {
            return '';
        }

        // Get the source URL
        $sourceUrl = $this->getSourceUrl(
            new UploadLocation(
                uploadLocationId: $file->getProperty('upload_location_id'),
                directoryId: $file->getProperty('directory_id')
            )
        );

        return "{$sourceUrl}{$file->getProperty('file_name')}";
    }

    public function getFilePath(int $fileId): string
    {
        // Get the file record query builder
        $file = $this->recordBuilder->get('File');

        // Filter the file
        $file->filter('file_id', $fileId);

        // Get the file result
        $file = $file->first();

        // Check if we have a file
        if (! $file) {
            return '';
        }

        // Get the source path
        $sourcePath = $this->getSourcePath(
            new UploadLocation(
                uploadLocationId: $file->getProperty('upload_location_id'),
                directoryId: $file->getProperty('directory_id')
            )
        );

        return "{$sourcePath}{$file->getProperty('file_name')}";
    }

    public function getFileModel(int $fileId): ?FileModel
    {
        // Set the separator
        $sep = DIRECTORY_SEPARATOR;

        // Get the file record query builder
        $file = $this->recordBuilder->get('File');

        // Filter the file
        $file->filter('file_id', $fileId);

        // Get the file result
        $file = $file->first();

        // Make sure file exists
        if (! $file) {
            return null;
        }

        /** @var File $file */

        // Get upload destination identifier
        $uploadDestinationId = $file->getProperty('upload_location_id');

        // Get the upload destination
        $uploadDestination = $this->recordBuilder->get('UploadDestination');

        // Filter the upload destination
        $uploadDestination->filter('id', $uploadDestinationId);

        // Get upload destination result
        $uploadDestination = $uploadDestination->first();

        // Get the server path
        $serverPath = realpath($uploadDestination->getProperty('server_path'));
        $serverPath = rtrim($serverPath, $sep) . $sep;

        // Clone the file model
        $fileModel = clone $this->fileModel;

        // Update the file model
        $fileModel->location_type = 'ee';
        $fileModel->location_identifier = $uploadDestinationId;
        $fileModel->file_id = $file->getProperty('file_id');
        $fileModel->setFileLocation(
            "{$serverPath}{$file->getProperty('file_name')}"
        );
        $fileModel->filesize = $file->getProperty('file_size');
        $fileModel->width = (int) $file->width;
        $fileModel->height = (int) $file->height;

        return $fileModel;
    }

    public function cacheFileLocallyById(int $fileId): ?string
    {
        // Get the file record query builder
        $file = $this->recordBuilder->get('File');

        // Filter the file
        $file->filter('file_id', $fileId);

        // Get the file result
        $file = $file->first();

        // Make sure file exists
        if (! $file) {
            $this->logger->developer(sprintf(
                '[Ansel] Could not cache %s because it could not be found.',
                $file
            ));

            return '';
        }

        // Return the cache file
        return $this->fileCacheService->cacheByPath($file);
    }

    public function getSourceModels(array $ids): array
    {
        // Get the upload destination
        $uploadDestination = $this->recordBuilder->get('UploadDestination');

        // Filter the upload destination
        $uploadDestination->filter('id', 'IN', $ids);

        // Get upload destination result
        $uploadDestinations = $uploadDestination->all();

        // Start an array for sources
        $sources = array();

        // Iterate through upload destinations
        foreach ($uploadDestinations as $dest) {
            /** @var UploadDestination $dest */

            // Clone the source model
            $sourceModel = clone $this->sourceModel;

            // Set URL
            $sourceModel->url = rtrim($dest->getProperty('url'), '/') . '/';

            // Set path
            $sourceModel->path = rtrim($dest->getProperty('server_path'), '/') . '/';

            // Add the model to the sources array
            $sources[$dest->getProperty('id')] = $sourceModel;
        }

        // Return the sources
        return $sources;
    }

    public function getFileModels(array $ids): array
    {
        // Get the file record query builder
        $file = $this->recordBuilder->get('File');

        // Filter the files
        $file->filter('file_id', 'IN', $ids);

        // Get the file results
        $files = $file->all();

        // Return files
        $returnFiles = array();

        // Iterate through files
        foreach ($files as $file) {
            /** @var File $file */

            // Get upload destination identifier
            $uploadDestinationId = $file->getProperty('upload_location_id');
            $directoryId = $file->getProperty('directory_id');

            // Clone the file model
            $fileModel = clone $this->fileModel;

            // Update the file model
            $fileModel->location_type = 'ee';
            $fileModel->location_identifier = $uploadDestinationId;
            $fileModel->directory_id = $directoryId;
            $fileModel->file_id = $file->getProperty('file_id');
            // $fileModel->url = $file->getAbsoluteURL();
            $fileModel->setFileLocation($file->getProperty('file_name'));
            $fileModel->filesize = $file->getProperty('file_size');
            $fileModel->width = (int) $file->getProperty('width');
            $fileModel->height = (int) $file->getProperty('height');
            $fileModel->title = $file->getProperty('title');
            $fileModel->file_description =  $file->getProperty('description');
            $fileModel->file_credit = $file->getProperty('credit');
            $fileModel->file_location =  $file->getProperty('location');

            $returnFiles[$fileModel->file_id] = $fileModel;
        }

        // Return the array of models
        return $returnFiles;
    }
}
