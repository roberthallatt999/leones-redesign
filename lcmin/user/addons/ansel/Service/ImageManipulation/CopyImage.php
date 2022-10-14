<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\ImageManipulation;

/**
 * Class CopyImage
 *
 * @property int $quality
 * @property bool $forceJpg
 */
class CopyImage extends Base
{
	/**
	 * Copy the image
	 *
	 * @param string $sourceFilePath
	 * @return string
	 */
	public function run($sourceFilePath)
	{
		// Get source image size
		$sourceImageSize = getimagesize($sourceFilePath);
		$sourceWidth = $sourceImageSize[0];
		$sourceHeight = $sourceImageSize[1];

		// Set the width and height
		$this->width = $sourceWidth;
		$this->height = $sourceHeight;

		// Get resource
		$resource = $this->getResource($sourceFilePath);

		// Resize the image with the correct library
		if (! $resource instanceof \Imagick) {
			// Create a new GD image
			$newImage = $this->createGDImage();

			// Resize the image
			imagecopyresampled(
				$newImage, // Destination image
				$resource, // Source image
				0, // Destination x
				0, // Destination y
				0, // Source x
				0, // Source y
				$sourceWidth, // Destination width
				$sourceHeight, // Destination height
				$sourceWidth, // Source width
				$sourceHeight // Source height
			);

			// Set the new image as the resource
			$resource = $newImage;
		}

		// Write the image to its destination and return the path
		return $this->writeImageToDestination($resource);
	}
}
