<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Sources;

use BuzzingPixel\Ansel\Model\Source as SourceModel;
use BuzzingPixel\Ansel\Model\File as FileModel;
use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;
use BuzzingPixel\Ansel\Service\FileCacheService;

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
	 * @param int $identifier Source identifier
	 * @param string $lang
	 * @return string
	 */
	public function getFileChooserLink($identifier, $lang = null)
	{
		// Include sheet resources
		$this->assetsHelper->include_sheet_resources();

		// Split up the identifier
		$identifier = explode('-', $identifier);

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
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @param string $subFolder
	 * @param bool $insertTimestamp
	 * @return string Full file upload path
	 * @throws \Exception
	 */
	public function uploadFile(
		$identifier,
		$filePath,
		$subFolder = null,
		$insertTimestamp = false
	) {
		// Upload the file
		$file = $this->addOrUploadFile(
			$identifier,
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
		$identifier,
		$fileName,
		$subFolder = null
	) {
		// Get the separator
		$sep = DIRECTORY_SEPARATOR;

		// Split the identifier appropriately
		$identifier = explode('-', $identifier);
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
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @return FileModel
	 * @throws \Exception
	 */
	public function addFile($identifier, $filePath)
	{
		// Get file info
		$fileSize = filesize($filePath);
		$imageSize = getimagesize($filePath);

		// Upload the file
		$file = $this->addOrUploadFile(
			$identifier,
			$filePath
		);

		// Clone the file model
		$fileModel = clone $this->fileModel;

		// Update the file model
		$fileModel->location_type = 'assets';
		$fileModel->location_identifier = $identifier;
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
	 * @param mixed $fileIdentifier
	 * @throws \Exception
	 */
	public function removeFile($fileIdentifier)
	{
		// Get file
		$file = $this->assetsLib->get_file_by_id($fileIdentifier);

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
		@$source->delete_file($fileIdentifier, true);
	}

	/**
	 * Get source URL
	 *
	 * @param mixed $identifier Source identifier
	 * @return string
	 * @throws \Exception
	 */
	public function getSourceUrl($identifier)
	{
		// Split the identifier appropriately
		$identifier = explode('-', $identifier);
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
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	public function getFileUrl($fileIdentifier)
	{
		// Get file
		$file = $this->assetsLib->get_file_by_id($fileIdentifier);

		if (! $file) {
			return '';
		}

		// Return file URL
		return $file->url();
	}

	/**
	 * Get file model
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return null|FileModel
	 */
	public function getFileModel($fileIdentifier)
	{
		// Get file
		$file = $this->assetsLib->get_file_by_id($fileIdentifier);

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
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	public function cacheFileLocallyById($fileIdentifier)
	{
		// Get file
		$file = $this->assetsLib->get_file_by_id($fileIdentifier);

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
	public function getSourceModels($ids)
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
	public function getFileModels($ids)
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
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @param string $subFolder
	 * @param bool $insertTimestamp
	 * @return null|\Assets_base_file
	 * @throws \Exception
	 */
	private function addOrUploadFile(
		$identifier,
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
		$identifier = explode('-', $identifier);
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
}
