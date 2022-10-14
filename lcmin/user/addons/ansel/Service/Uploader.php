<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use BuzzingPixel\Ansel\Model\PHPFileUpload;

/**
 * Class Uploader
 */
class Uploader
{
	/**
	 * @var string $anselCachePath
	 */
	private $anselCachePath;

	/**
	 * Uploader constructor
	 *
	 * @param string $anselCachePath
	 */
	public function __construct($anselCachePath)
	{
		// Make sure cache path exists
		if (! is_dir($anselCachePath)) {
			mkdir($anselCachePath, DIR_WRITE_MODE, true);
		}

		// Inject dependencies
		$this->anselCachePath = rtrim($anselCachePath, '/') . '/';
	}

	/**
	 * Post upload
	 *
	 * @param PHPFileUpload $phpFileUpload
	 * @return PHPFileUpload
	 */
	public function postUpload(PHPFileUpload $phpFileUpload)
	{
		// Create a unique ID for the directory
		$uniqueId = uniqid();

		// Create the upload directory path
		$uploadPath = "{$this->anselCachePath}{$uniqueId}/";

		// Create the directory
		mkdir($uploadPath, DIR_WRITE_MODE, true);

		// Add the file name to the $uploadPath
		$uploadPath .= $phpFileUpload->name;

		// Copy the file into place
		copy($phpFileUpload->tmp_name, $uploadPath);

		// Set the cache path
		$phpFileUpload->anselCachePath = $uploadPath;

		// Get the type file contents for base64 encoding
		$type = pathinfo($phpFileUpload->anselCachePath, PATHINFO_EXTENSION);
		$contents = file_get_contents($phpFileUpload->anselCachePath);

		// Set base 64 encoded file
		$base64 = "data:image/{$type};base64,";
		$base64 .= base64_encode($contents);

		// Set the base 64 encoded file to the model
		$phpFileUpload->base64 = $base64;

		// Return the model
		return $phpFileUpload;
	}
}
