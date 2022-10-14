<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Sources;

use EllisLab\Addons\FilePicker\Service\FilePicker\FilePicker;
use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BuzzingPixel\Ansel\Model\Source as SourceModel;
use BuzzingPixel\Ansel\Model\File as FileModel;
use EllisLab\Addons\FilePicker\Service\FilePicker\Link;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;
use EllisLab\ExpressionEngine\Model\File\File;
use BuzzingPixel\Ansel\Service\FileCacheService;

/**
 * Class Ee
 */
class Ee extends BaseSource
{
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
	 * @var int $siteId
	 */
	private $siteId;

	/**
	 * @var int $userId
	 */
	private $userId;

	/**
	 * Constructor
	 *
	 * @param FilePicker $filePicker
	 * @param RecordBuilder $recordBuilder
	 * @param SourceModel $sourceModel
	 * @param FileModel $fileModel
	 * @param FileCacheService $fileCacheService
	 * @param \File_model $eeFileModel
	 * @param \Filemanager $eeFileManager
	 * @param int $siteId
	 * @param int $userId
	 */
	public function __construct(
		FilePicker $filePicker,
		RecordBuilder $recordBuilder,
		SourceModel $sourceModel,
		FileModel $fileModel,
		FileCacheService $fileCacheService,
		\File_model $eeFileModel,
		\Filemanager $eeFileManager,
		$siteId,
		$userId
	) {
		// Inject dependencies
		$this->filePicker = $filePicker;
		$this->recordBuilder = $recordBuilder;
		$this->sourceModel = $sourceModel;
		$this->fileModel = $fileModel;
		$this->fileCacheService = $fileCacheService;
		$this->eeFileModel = $eeFileModel;
		$this->eeFileManager = $eeFileManager;
		$this->siteId = $siteId;
		$this->userId = $userId;
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
		// Get the upload destination
		$uploadDestination = $this->recordBuilder->get('UploadDestination');

		// Filter the upload destination
		$uploadDestination->filter('id', $identifier);

		// Get upload destination result
		$uploadDestination = $uploadDestination->first();

		/** @var UploadDestination $uploadDestination */

		// Get the modal view
		$modalView = $uploadDestination->getProperty('default_modal_view');

		// Set directories
		$this->filePicker->setDirectories($identifier);

		// Set up language
		$lang = $lang ?: lang('choose_an_existing_image');

		// Get link
		/** @var Link $link */
		$link = $this->filePicker->getLink($lang);

		// Set attributes
		$link->setAttribute(
			'class',
			'btn action js-ansel-ee-choose-existing-image'
		);

		// Set modal view
		if ($modalView === 'list') {
			$link->asList();
		} elseif ($modalView === 'thumb') {
			$link->asThumbs();
		}

		// Enable filters
		$link->enableFilters();

		// Enable uploads
		$link->enableUploads();

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
		// Set the separator
		$sep = DIRECTORY_SEPARATOR;

		// Get the real path
		$filePath = realpath($filePath);

		// Get the upload destination
		$uploadDestination = $this->recordBuilder->get('UploadDestination');

		// Filter the upload destination
		$uploadDestination->filter('id', $identifier);

		// Get upload destination result
		$uploadDestination = $uploadDestination->first();

		// Get path info
		$path = pathinfo($filePath);

		// Set the destination filename
		if ($insertTimestamp) {
			$time = time();
			$destFileName = "{$path['filename']}-{$time}.{$path['extension']}";
		} else {
			$destFileName = $path['basename'];
		}

		// Set the upload path
		$uploadPath = realpath($uploadDestination->getProperty('server_path'));
		$uploadPath = rtrim($uploadPath, $sep) . $sep;

		// Check if a sub folder has been specified
		if ($subFolder) {
			$uploadPath .= rtrim(ltrim($subFolder, $sep), $sep) . $sep;
		}

		// Set the full file path
		$fullFilePath = "{$uploadPath}{$destFileName}";

		// Check if the file exists and set a non-conflicting name if so
		if (is_file($fullFilePath)) {
			$destPathInfo = pathinfo($destFileName);
			$unique = uniqid();
			$fullFilePath = "{$uploadPath}{$destPathInfo['filename']}";
			$fullFilePath .= "-{$unique}.{$destPathInfo['extension']}";
		}

		// Make sure PHP can write file permissions
		$oldmask = umask(0);

		// Write the directory if it doesn't exist
		if (! is_dir($uploadPath)) {
			mkdir($uploadPath, DIR_WRITE_MODE, true);
		}

		// Copy file into place
		copy($filePath, $fullFilePath);

		// Reset the umask
		umask($oldmask);

		// Return the upload path
		return $fullFilePath;
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
		// Set the separator
		$sep = DIRECTORY_SEPARATOR;

		// Get the upload destination
		$uploadDestination = $this->recordBuilder->get('UploadDestination');

		// Filter the upload destination
		$uploadDestination->filter('id', $identifier);

		// Get upload destination result
		$uploadDestination = $uploadDestination->first();

		// Make sure everything is okay
		if (! $uploadDestination) {
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
		 * Check if we can remove the sub directories
		 */

		if (! $subFolder) {
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
		// Place the file
		$filePath = $this->uploadFile($identifier, $filePath);

		// Set the timestamp
		$timeStamp = time();

		// Get path info
		$pathInfo = pathinfo($filePath);

		// Get image size
		$imageSize = getimagesize($filePath);

		// Make a file record
		/** @var File $file */
		$file = $this->recordBuilder->make('File');

		// Set model properties
		$file->setProperty('site_id', $this->siteId);
		$file->setProperty('title', $pathInfo['filename']);
		$file->setProperty('upload_location_id', $identifier);
		$file->setProperty('mime_type', mime_content_type($filePath));
		$file->setProperty('file_name', $pathInfo['basename']);
		$file->setProperty('file_size', filesize($filePath));
		$file->setProperty('uploaded_by_member_id', $this->userId);
		$file->setProperty('upload_date', $timeStamp);
		$file->setProperty('modified_by_member_id', $this->userId);
		$file->setProperty('modified_date', $timeStamp);
		$file->setProperty(
			'file_hw_original',
			"{$imageSize[1]} {$imageSize[0]}"
		);

		// Save the file
		$file->save();

		// Get dimensions for manipulations
		/** @var \CI_DB_mysqli_result $dimensions */
		$dimensions = $this->eeFileModel->get_dimensions_by_dir_id($identifier);
		$dimensions = $dimensions->result_array();

		// Run manipulations and thumbnails
		$this->eeFileManager->create_thumb(
			$filePath,
			array(
				'server_path' => $pathInfo['dirname'],
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
		$fileModel->location_identifier = $identifier;
		$fileModel->file_id = $file->getProperty('file_id');
		$fileModel->setFileLocation($filePath);
		$fileModel->filesize = $file->getProperty('file_size');
		$fileModel->width = $imageSize[0];
		$fileModel->height = $imageSize[1];

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
		// Get the file record query builder
		$file = $this->recordBuilder->get('File');

		// Filter the file
		$file->filter('file_id', $fileIdentifier);

		// Get the file result
		$file = $file->first();

		// Make sure everything is okay
		if (! $file) {
			return;
		}

		/** @var File $file */

		// Delete the file
		$this->deleteFile(
			$file->getProperty('upload_location_id'),
			$file->getProperty('file_name')
		);

		// Delete thumbnail
		$this->deleteFile(
			$file->getProperty('upload_location_id'),
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
					$file->getProperty('upload_location_id'),
					$file->getProperty('file_name'),
					"_{$manipulation['short_name']}"
				);
			}
		}

		// Delete the file record
		$file->delete();
	}

	/**
	 * Get source URL
	 *
	 * @param mixed $identifier Source identifier
	 * @return string
	 */
	public function getSourceUrl($identifier)
	{
		// Get the upload destination
		$uploadDestination = $this->recordBuilder->get('UploadDestination');

		// Filter the upload destination
		$uploadDestination->filter('id', $identifier);

		// Get upload destination result
		$uploadDestination = $uploadDestination->first();

		// Make sure everything is okay
		if (! $uploadDestination) {
			return null;
		}

		return rtrim($uploadDestination->getProperty('url'), '/') . '/';
	}

	/**
	 * Get file URL
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	public function getFileUrl($fileIdentifier)
	{
		// Get the file record query builder
		$file = $this->recordBuilder->get('File');

		// Filter the file
		$file->filter('file_id', $fileIdentifier);

		// Get the file result
		$file = $file->first();

		// Check if we have a file
		if (! $file) {
			return '';
		}

		// Get the source URL
		$sourceUrl = $this->getSourceUrl(
			$file->getProperty('upload_location_id')
		);

		return "{$sourceUrl}{$file->getProperty('file_name')}";
	}

	/**
	 * Get file model
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return null|FileModel
	 */
	public function getFileModel($fileIdentifier)
	{
		// Set the separator
		$sep = DIRECTORY_SEPARATOR;

		// Get the file record query builder
		$file = $this->recordBuilder->get('File');

		// Filter the file
		$file->filter('file_id', $fileIdentifier);

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

	/**
	 * Cache file locally by ID
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	public function cacheFileLocallyById($fileIdentifier)
	{
		// Get the file record query builder
		$file = $this->recordBuilder->get('File');

		// Filter the file
		$file->filter('file_id', $fileIdentifier);

		// Get the file result
		$file = $file->first();

		// Make sure file exists
		if (! $file) {
			return null;
		}

		// Return the cache file
		return $this->fileCacheService->cacheByPath($file->getAbsolutePath());
	}

	/**
	 * Get source models
	 *
	 * @param array $ids
	 * @return array
	 */
	public function getSourceModels($ids)
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

	/**
	 * Get file models
	 *
	 * @param array $ids
	 * @return array
	 */
	public function getFileModels($ids)
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

			// Clone the file model
			$fileModel = clone $this->fileModel;

			// Update the file model
			$fileModel->location_type = 'ee';
			$fileModel->location_identifier = $uploadDestinationId;
			$fileModel->file_id = $file->getProperty('file_id');
			// $fileModel->url = $file->getAbsoluteURL();
			$fileModel->setFileLocation($file->getProperty('file_name'));
			$fileModel->filesize = $file->getProperty('file_size');
			$fileModel->width = (int) $file->getProperty('width');
			$fileModel->height = (int) $file->getProperty('height');
			$fileModel->file_description =  $file->getProperty('description');
			$fileModel->file_credit = $file->getProperty('credit');
			$fileModel->file_location =  $file->getProperty('location');

			$returnFiles[$fileModel->file_id] = $fileModel;
		}

		// Return the array of models
		return $returnFiles;
	}
}
