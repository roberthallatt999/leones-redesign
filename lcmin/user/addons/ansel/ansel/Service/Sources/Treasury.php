<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Sources;

use BuzzingPixel\Treasury\Service\FilePicker\FilePicker as TreasuryFilePicker;
use BuzzingPixel\Treasury\Service\FilePicker\Link;
use BuzzingPixel\Ansel\Model\Source as SourceModel;
use BuzzingPixel\Ansel\Model\File as FileModel;
use BuzzingPixel\Treasury\API\Upload as TreasuryUploadApi;
use BuzzingPixel\Treasury\API\Files as TreasuryFilesApi;
use BuzzingPixel\Treasury\API\Locations as TreasuryLocationsApi;
use BuzzingPixel\Treasury\Model\Locations as TreasuryLocationsModel;
use BuzzingPixel\Treasury\Model\Files as TreasuryFileModel;
use BuzzingPixel\Ansel\Service\FileCacheService;

/**
 * Class Ee
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Treasury extends BaseSource
{
	/**
	 * @var TreasuryFilePicker $treasuryFilePicker
	 */
	private $treasuryFilePicker;

	/**
	 * @var TreasuryUploadApi $treasuryUploadApi
	 */
	private $treasuryUploadApi;

	/**
	 * @var TreasuryFilesApi $treasuryFilesApi
	 */
	private $treasuryFilesApi;

	/**
	 * @var TreasuryLocationsApi $treasuryLocationsApi
	 */
	private $treasuryLocationsApi;

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
	 * @param TreasuryFilePicker $treasuryFilePicker
	 * @param TreasuryUploadApi $treasuryUploadApi
	 * @param TreasuryFilesApi $treasuryFilesApi
	 * @param TreasuryLocationsApi $treasuryLocationsApi
	 * @param SourceModel $sourceModel
	 * @param FileModel $fileModel
	 * @param FileCacheService $fileCacheService
	 */
	public function __construct(
		$treasuryFilePicker,
		TreasuryUploadApi $treasuryUploadApi,
		TreasuryFilesApi $treasuryFilesApi,
		TreasuryLocationsApi $treasuryLocationsApi,
		SourceModel $sourceModel,
		FileModel $fileModel,
		FileCacheService $fileCacheService
	) {
		// Inject dependencies
		$this->treasuryFilePicker = $treasuryFilePicker;
		$this->treasuryUploadApi = $treasuryUploadApi;
		$this->treasuryFilesApi = $treasuryFilesApi;
		$this->treasuryLocationsApi = $treasuryLocationsApi;
		$this->sourceModel = $sourceModel;
		$this->fileModel = $fileModel;
		$this->fileCacheService = $fileCacheService;
	}

	/**
	 * Get file chooser link
	 *
	 * @param int $identifier Source identifier
	 * @param string $lang
	 * @return string
	 */
	public function getFileChooserLink($identifier, $lang = null)
	{
		// Set the location
		$this->treasuryFilePicker->setlocation($identifier);

		// Set up language
		$lang = $lang ?: lang('choose_an_existing_image');

		// Get link
		/** @var Link $link */
		$link = $this->treasuryFilePicker->getLink($lang);

		// Set attributes
		$link->setAttribute(
			'class',
			'btn action js-ansel-treasury-choose-existing-image'
		);

		// Render and return
		return $link->render();
	}

	/**
	 * Upload file to the source storage
	 *
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @param string $subFolder
	 * @param bool $insertTimestamp
	 * @return string Full file upload path
	 */
	public function uploadFile(
		$identifier,
		$filePath,
		$subFolder = null,
		$insertTimestamp = false
	) {
		// Get the separator
		$sep = DIRECTORY_SEPARATOR;

		// Get file path info
		$path = pathinfo($filePath);

		// Start file name
		$fileName = '';

		// Check if there is a subfolder
		if ($subFolder) {
			// Normalize the subfolder
			$fileName = rtrim($subFolder, $sep);
			$fileName = ltrim($fileName, $sep) . '/';
		}

		// Add path info filename
		if ($insertTimestamp) {
			$time = time();
			$fileName .= "{$path['filename']}-{$time}.{$path['extension']}";
		} else {
			$fileName .= $path['basename'];
		}

		// Check if the file exists
		if ($this->treasuryFilesApi->fileExists($identifier, $fileName)) {
			$path = pathinfo($fileName);
			$unique = uniqid();
			$fileName = $path['dirname'] !== '.' ? "{$path['dirname']}/" : '';
			$fileName .= "{$path['filename']}-{$unique}.{$path['extension']}";
		}

		// Upload the file
		$this->treasuryUploadApi->locationHandle($identifier);
		$this->treasuryUploadApi->filePath($filePath);
		$this->treasuryUploadApi->fileName($fileName);
		$this->treasuryUploadApi->uploadFile();

		// Return the file name
		return $fileName;
	}

	/**
	 * Delete file from source storage
	 *
	 * @param mixed $identifier Source identifier
	 * @param string $fileName
	 * @param string $subFolder
	 */
	public function deleteFile(
		$identifier,
		$fileName,
		$subFolder = null
	) {
		// Get the seperator
		$sep = DIRECTORY_SEPARATOR;

		// Start file name
		$finalFileName = '';

		// Check if there is a subfolder
		if ($subFolder) {
			// Normalize the subfolder
			$finalFileName = rtrim($subFolder, $sep);
			$finalFileName = ltrim($finalFileName, $sep) . '/';
		}

		// Add path info filename
		$finalFileName .= $fileName;

		// Delete the file
		$this->treasuryFilesApi->deleteFileByPath(
			$identifier,
			$finalFileName
		);
	}

	/**
	 * Add file and record to the source
	 *
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @return FileModel
	 */
	public function addFile($identifier, $filePath)
	{
		// Get file path info
		$path = pathinfo($filePath);

		$fileName = $path['basename'];

		// Check if the file exists
		if ($this->treasuryFilesApi->fileExists($identifier, $fileName)) {
			$unique = uniqid();
			$fileName = "{$path['filename']}-{$unique}.{$path['extension']}";
		}

		// Add the file
		$this->treasuryUploadApi->locationHandle($identifier);
		$this->treasuryUploadApi->filePath($filePath);
		$this->treasuryUploadApi->fileName($fileName);
		$this->treasuryUploadApi->addFile();

		// Get location
		/** @var TreasuryLocationsModel $location */
		$location = $this->treasuryLocationsApi->getLocationByHandle(
			$identifier
		);

		// Reset the files API
		$this->treasuryFilesApi->__construct();

		// Get the file
		$this->treasuryFilesApi->filter('file_name', $fileName);
		$this->treasuryFilesApi->filter('location_id', $location->id);
		$file = $this->treasuryFilesApi->getFirst();

		$fileModel = clone $this->fileModel;

		// Update the file model
		$fileModel->location_type = 'treasury';
		$fileModel->location_identifier = $identifier;
		$fileModel->file_id = $file->id;
		$fileModel->setFileLocation($fileName);
		$fileModel->filesize = $file->file_size;
		$fileModel->width = $file->width;
		$fileModel->height = $file->height;

		// Return the file model
		return $fileModel;
	}

	/**
	 * Remove file and record from the source
	 *
	 * @param mixed $fileIdentifier
	 */
	public function removeFile($fileIdentifier)
	{
		$this->treasuryFilesApi->deleteFilesById(array(
			$fileIdentifier
		));
	}

	/**
	 * Get source URL
	 *
	 * @param mixed $identifier Source identifier
	 * @return string
	 */
	public function getSourceUrl($identifier)
	{
		// Get location
		/** @var TreasuryLocationsModel $location */
		$location = $this->treasuryLocationsApi->getLocationByHandle(
			$identifier
		);

		// Return normalized URL
		return rtrim($location->full_url, '/') . '/';
	}

	/**
	 * Get file URL
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	public function getFileUrl($fileIdentifier)
	{
		$this->treasuryFilesApi->__construct();
		$this->treasuryFilesApi->filter('id', $fileIdentifier);
		$file = $this->treasuryFilesApi->getFirst();

		if ($file instanceof \BuzzingPixel\Treasury\Model\Files) {
			return $file->file_url ? $file->file_url : '';
		}

		return '';
	}

	/**
	 * Get file model
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return null|FileModel
	 */
	public function getFileModel($fileIdentifier)
	{
		// Get the file
		$this->treasuryFilesApi->__construct();
		$this->treasuryFilesApi->filter('id', $fileIdentifier);
		$file = $this->treasuryFilesApi->getFirst();

		/** @var TreasuryFileModel $file */

		$fileModel = clone $this->fileModel;

		// Update the file model
		$fileModel->location_type = 'treasury';
		$fileModel->location_identifier = $file->location->handle;
		$fileModel->file_id = $file->id;
		$fileModel->setFileLocation($file->file_name);
		$fileModel->filesize = $file->file_size;
		$fileModel->width = $file->width;
		$fileModel->height = $file->height;

		// Return the file model
		return $fileModel;
	}

	/**
	 * Cache file locally by ID
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	public function cacheFileLocallyById($fileIdentifier)
	{
		// Get the file
		$this->treasuryFilesApi->__construct();
		$this->treasuryFilesApi->filter('id', $fileIdentifier);
		$file = $this->treasuryFilesApi->getFirst();

		// Cache by local path if file is local
		if ($file->location->type === 'local') {
			// Set up the location path
			$path = rtrim($file->location->path, '/') . '/';

			// Return the cache file
			return $this->fileCacheService->cacheByPath("{$path}{$file->file_name}");
		} else { // Else cache by URL
			// Return the cache file
			return $this->fileCacheService->cacheByPath($file->file_url);
		}
	}

	/**
	 * Get source models
	 *
	 * @param array $ids
	 * @return array
	 */
	public function getSourceModels($ids)
	{
		// Get all locations so that it all happens in one query and caches
		$this->treasuryLocationsApi->getAllLocations();

		// Start an array for sources
		$sources = array();

		// Iterate through IDs and get locations
		foreach ($ids as $id) {
			// Get location
			/** @var TreasuryLocationsModel $location */
			$location = $this->treasuryLocationsApi->getLocationByHandle($id);

			// Clone the source model
			$sourceModel = clone $this->sourceModel;

			// Set URL
			$sourceModel->url = rtrim($location->full_url, '/') . '/';

			// Set path
			$sourceModel->path = rtrim($location->path, '/') . '/';

			// Add the model to the sources array
			$sources[$location->handle] = $sourceModel;
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
	public function getFileModels($ids)
	{
		// Get the files
		$this->treasuryFilesApi->__construct();
		$this->treasuryFilesApi->filter('id', 'IN', $ids);
		$files = $this->treasuryFilesApi->getFiles();

		// Return files
		$returnFiles = array();

		// Iterate through files
		foreach ($files as $file) {
			/** @var TreasuryFileModel $file */

			// Clone the file model
			$fileModel = clone $this->fileModel;

			// Update the file model
			$fileModel->location_type = 'treasury';
			$fileModel->location_identifier = $file->location->handle;
			$fileModel->file_id = $file->id;
			$fileModel->setFileLocation("{$file->location->path}{$file->file_name}");
			$fileModel->filesize = $file->file_size;
			$fileModel->width = $file->width;
			$fileModel->height = $file->height;
			$fileModel->file_description =  $file->description;

			$returnFiles[$fileModel->file_id] = $fileModel;
		}

		// Return the array of models
		return $returnFiles;
	}
}
