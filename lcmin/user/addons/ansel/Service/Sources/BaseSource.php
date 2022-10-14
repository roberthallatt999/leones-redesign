<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Sources;

use BuzzingPixel\Ansel\Model\File as FileModel;

/**
 * Class BaseSource
 */
abstract class BaseSource
{
	/**
	 * Get file chooser link
	 *
	 * @param mixed $identifier Source identifier
	 * @return string
	 */
	abstract public function getFileChooserLink($identifier);

	/**
	 * Upload file to the source storage
	 *
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @param string $subFolder
	 * @param bool $insertTimestamp
	 * @return string Full file upload path
	 */
	abstract public function uploadFile(
		$identifier,
		$filePath,
		$subFolder = null,
		$insertTimestamp = false
	);

	/**
	 * Delete file from source storage
	 *
	 * @param mixed $identifier Source identifier
	 * @param string $fileName
	 * @param string $subFolder
	 */
	abstract public function deleteFile(
		$identifier,
		$fileName,
		$subFolder = null
	);

	/**
	 * Add file and record to the source
	 *
	 * @param mixed $identifier Source identifier
	 * @param string $filePath
	 * @return FileModel
	 */
	abstract public function addFile($identifier, $filePath);

	/**
	 * Remove file and record from the source
	 *
	 * @param mixed $fileIdentifier
	 */
	abstract public function removeFile($fileIdentifier);

	/**
	 * Get source URL
	 *
	 * @param mixed $identifier Source identifier
	 * @return string
	 */
	abstract public function getSourceUrl($identifier);

	/**
	 * Get file URL
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	abstract public function getFileUrl($fileIdentifier);

	/**
	 * Get file model
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return null|FileModel
	 */
	abstract public function getFileModel($fileIdentifier);

	/**
	 * Cache file locally by ID
	 *
	 * @param mixed $fileIdentifier File identifier
	 * @return string
	 */
	abstract public function cacheFileLocallyById($fileIdentifier);

	/**
	 * Get source models
	 *
	 * @param array $ids
	 * @return array
	 */
	abstract public function getSourceModels($ids);

	/**
	 * Get file models
	 *
	 * @param array $ids
	 * @return array
	 */
	abstract public function getFileModels($ids);
}
