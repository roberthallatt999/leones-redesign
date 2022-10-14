<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\AnselImages;

use BuzzingPixel\Ansel\Model\InternalTagParams;
use BuzzingPixel\Ansel\Model\File as FileModel;
use BuzzingPixel\Ansel\Model\Source as SourceModel;
use BuzzingPixel\Ansel\Service\Sources\SourceRouter;
use BuzzingPixel\Ansel\Service\ImageManipulation\ManipulateImage;

/**
 * Class InternalTag
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class InternalTag
{
	/**
	 * @var SourceRouter $sourceRouter
	 */
	private $sourceRouter;

	/**
	 * @var ManipulateImage $manipulateImage
	 */
	private $manipulateImage;

	/**
	 * Constructor
	 *
	 * @param SourceRouter $sourceRouter
	 * @param ManipulateImage $manipulateImage
	 */
	public function __construct(
		SourceRouter $sourceRouter,
		ManipulateImage $manipulateImage
	) {
		$this->sourceRouter = $sourceRouter;
		$this->manipulateImage = $manipulateImage;
	}

	/**
	 * Process
	 *
	 * @param FileModel $file
	 * @param SourceModel $source
	 * @param array $tags
	 * @param int $defaultQuality
	 * @return array
	 */
	public function processTags(
		$file,
		$source,
		$tags,
		$defaultQuality = 90
	) {
		// Vars array
		$vars = array();

		// Iterate through tags to process them
		foreach ($tags as $tag) {
			$vars[$tag->id] = $this->processTag(
				$file,
				$source,
				$tag->params,
				$defaultQuality
			);
		}

		// Return the vars
		return $vars;
	}

	/**
	 * Process a tag
	 *
	 * @param FileModel $file
	 * @param SourceModel $source
	 * @param InternalTagParams $tagParams
	 * @param int $defaultQuality
	 * @return string
	 */
	public function processTag(
		$file,
		$source,
		InternalTagParams $tagParams,
		$defaultQuality = 90
	) {
		// Set cache location
		$cacheLoc = ANSEL_CACHE_PERSISTENT;
		$tempCacheLoc = ANSEL_CACHE;

		// Source filename
		$sourceFileUrl = "{$source->url}{$file->getUrlSafeParam('basename')}";

		// Check if quality has been set or if we should use default
		if ($tagParams->checkIfPropertyModified('quality')) {
			$quality = $tagParams->quality;
		} else {
			$quality = $defaultQuality;
		}

		// Normalize quality
		$quality = $this->normalizeQuality($quality);

		// Not resizing default
		$resizing = false;

		// Check params to see if we should resize
		if ($tagParams->checkIfPropertyModified('width') ||
			$tagParams->checkIfPropertyModified('height') ||
			$tagParams->checkIfPropertyModified('force_jpg') ||
			$tagParams->checkIfPropertyModified('quality')
		) {
			$resizing = true;
		}

		// If we're not resizing, return the URL
		if (! $resizing) {
			return $sourceFileUrl;
		}

		// Set source router location type
		$this->sourceRouter->setSource($file->location_type);

		// Create a hash for this file
		$hash = md5(serialize(array_filter(array(
			$sourceFileUrl,
			$tagParams->width,
			$tagParams->height,
			$tagParams->crop,
			$tagParams->background,
			$tagParams->force_jpg,
			$quality,
			$tagParams->scale_up
		))));

		// Set image cache directory name
		$imageCacheDirName = $file->location_type === 'assets' ?
			'ansel_image_cache' :
			'_ansel_image_cache';

		// Set cache file name
		$cacheFileName = $hash;

		// Add extension to URL
		if ($tagParams->force_jpg) {
			$cacheFileName .= '.jpg';
		} else {
			$cacheFileName .= ".{$file->extension}";
		}

		// Set URL to cache file
		$cacheFileUrl = "{$source->url}{$imageCacheDirName}/{$cacheFileName}";

		// Create cache text file name
		$cacheTxtFileName = "{$file->location_type}-";
		$cacheTxtFileName .= "{$file->location_identifier}-{$hash}";
		$cacheTxtFilePath = "{$cacheLoc}{$cacheTxtFileName}";

		// Check if the cache file exists
		if (is_file($cacheTxtFilePath)) {
			// If cache time is forever, return url
			if ($tagParams->cache_time === 0) {
				return $cacheFileUrl;
			}

			// Get cache file contents as timestamp
			$cacheTimeStamp = (int) file_get_contents($cacheTxtFilePath);

			// Get the expiration date
			$expiration = $cacheTimeStamp + $tagParams->cache_time;

			// If the file has not expired, return the url
			if ($expiration > time()) {
				return $cacheFileUrl;
			}
		}

		// Cache the file locally for manipulation
		$localFileCache = $this->sourceRouter->cacheFileLocallyById(
			$file->file_id
		);

		// Set vars
		$xLoc = 0;
		$yLoc = 0;
		$width = $file->width;
		$height = $file->height;
		$minWidth = 0;
		$minHeight = 0;
		$maxWidth = $tagParams->width;
		$maxHeight = $tagParams->height;

		// Make sure width and height tag params is defined
		$tagParams->width = $tagParams->width ?: $file->width;
		$tagParams->height = $tagParams->height ?: $file->height;

		// Calculate manipulation values
		if ($file->width < $tagParams->width ||
			$file->height < $tagParams->height
		) {
			if ($tagParams->scale_up) {
				// Get ratios
				$imgRatio = $file->width / $file->height;
				$cropRatio = $tagParams->width / $tagParams->height;

				// Check which way we need to crop
				$cropVertical = $imgRatio < $cropRatio;

				// Check if we're cropping or maintaining aspect
				if ($tagParams->crop) {
					// Size up exactly
					$minWidth = $tagParams->width;
					$minHeight = $tagParams->height;

					// Set up crop
					if ($cropVertical) {
						$height = round(($file->width * $tagParams->height) / $tagParams->width);
						$difference = $file->height - $height;
						$yLoc = round($difference / 2);
					} else {
						$width = round(($file->height * $tagParams->width) / $tagParams->height);
						$difference = $file->width - $width;
						$xLoc = round($difference / 2);
					}
				} else {
					// Check which way the resize should go
					if ($cropVertical) {
						// Resize to width
						$minWidth =0;
						$minHeight = $tagParams->height;
					} else {
						// Resize to height
						$minHeight = 0;
						$minWidth = $tagParams->width;
					}
				}
			}
		} else {
			// Get ratios
			$imgRatio = $file->width / $file->height;
			$cropRatio = $tagParams->width / $tagParams->height;

			// Check which way we need to crop
			$cropVertical = $imgRatio < $cropRatio;

			// Check if we're cropping or maintaining aspect
			if ($tagParams->crop) {
				// Size down exactly
				$maxWidth = $tagParams->width;
				$maxHeight = $tagParams->height;

				// Set up crop
				if ($cropVertical) {
					$height = round(($file->width * $tagParams->height) / $tagParams->width);
					$difference = $file->height - $height;
					$yLoc = round($difference / 2);
				} else {
					$width = round(($file->height * $tagParams->width) / $tagParams->height);
					$difference = $file->width - $width;
					$xLoc = round($difference / 2);
				}
			} else {
				// Check which way the resize should go
				if ($cropVertical) {
					// Resize to width
					$maxWidth = 0;
					$maxHeight = $tagParams->height;
				} else {
					// Resize to height
					$maxHeight = 0;
					$maxWidth = $tagParams->width;
				}
			}
		}

		// Run image manipulation
		$this->manipulateImage->x = $xLoc;
		$this->manipulateImage->y = $yLoc;
		$this->manipulateImage->width = $width;
		$this->manipulateImage->height = $height;
		$this->manipulateImage->minWidth = $minWidth;
		$this->manipulateImage->minHeight = $minHeight;
		$this->manipulateImage->maxWidth = $maxWidth;
		$this->manipulateImage->maxHeight = $maxHeight;
		$this->manipulateImage->forceJpg = $tagParams->force_jpg;
		$this->manipulateImage->quality = $quality;
		$this->manipulateImage->optimize = true;
		$manipulatedImage = $this->manipulateImage->run($localFileCache);

		// Set cache file path
		$cacheFilePath = "{$tempCacheLoc}{$cacheFileName}";

		// Rename the manipulated image to the correct cache file name
		if ($manipulatedImage) {
			rename($manipulatedImage, $cacheFilePath);
		}

		// Delete old cache file in case it exists
		$this->sourceRouter->deleteFile(
			$file->location_identifier,
			$cacheFileName,
			$imageCacheDirName
		);

		// Upload the manipulated file
		if ($file->location_identifier) {
			$this->sourceRouter->uploadFile(
				$file->location_identifier,
				$cacheFilePath,
				$imageCacheDirName
			);
		}

		// Place ansel_persistent file
		file_put_contents($cacheTxtFilePath, time());

		// Delete cache files

		if (is_file($cacheFilePath)) {
			unlink($cacheFilePath);
		}

		if (is_file($localFileCache)) {
			unlink($localFileCache);
		}

		// Return the cache file url
		return $cacheFileUrl;
	}

	/**
	 * Normalize quality
	 *
	 * @param int $quality
	 * @return int
	 */
	private function normalizeQuality($quality)
	{
		$quality = $quality > 100 ? 100 : $quality;
		$quality = $quality < 0 ? 0 : $quality;
		return $quality;
	}
}
