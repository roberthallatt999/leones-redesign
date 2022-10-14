<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\ImageManipulation;

/**
 * Class CropImage
 *
 * @property int $width
 * @property int $height
 * @property int $x
 * @property int $y
 * @property int $quality
 */
class CropImage extends Base
{
	/**
	 * Crop the image
	 *
	 * @param string $sourceFilePath
	 * @return string
	 */
	public function run($sourceFilePath)
	{
		// Get resource
		$resource = $this->getResource($sourceFilePath);

		// Crop the image with the correct library
		if ($resource instanceof \Imagick) {
			// Crop the image
			$resource->cropImage(
				$this->width,
				$this->height,
				$this->x,
				$this->y
			);

			// Set the image page geometry because of a bug in ImageMagick
			// when cropping GIFs
			$resource->setImagePage(
				$this->width,
				$this->height,
				0,
				0
			);
		} else {
			// // Get source image size
			// $sourceImageSize = getimagesize($sourceFilePath);
			// $sourceWidth = $sourceImageSize[0];
			// $sourceHeight = $sourceImageSize[1];

			// Create a new GD image
			$newImage = $this->createGDImage();

			// Crop the image
			imagecopyresampled(
				$newImage, // Destination image
				$resource, // Source image
				0, // Destination x
				0, // Destination y
				$this->x, // Source x
				$this->y, // Source y
				$this->width, // Desintation width
				$this->height, // Destination height
				$this->width, // Source width - although doesn't work properly with that, so who knows what's going on
				$this->height // Source height - ^^ same
			);

			// Set the new image as the resource
			$resource = $newImage;
		}

		// Write the image to its destination and return the path
		return $this->writeImageToDestination($resource);
	}
}
