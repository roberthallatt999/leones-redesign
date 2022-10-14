<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\ImageManipulation;

/**
 * Class Base
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
abstract class Base
{
	/**
	 * @var bool $forceGD
	 */
	private $forceGD = false;

	/**
	 * Properties
	 *
	 * @var array $properties
	 */
	private $properties = array(
		'width' => 0,
		'height' => 0,
		'x' => 0,
		'y' => 0,
		'quality' => 90,
		'background' => '',
		'forceJpg' => false
	);

	/**
	 * @var int $sourceFileType
	 */
	private $sourceFileType = 0;

	/**
	 * Constructor
	 *
	 * @param bool $forceGD
	 */
	public function __construct($forceGD = false)
	{
		$this->forceGD = $forceGD;
	}

	/**
	 * Set magic method
	 *
	 * @param string $name
	 * @param mixed $val
	 */
	public function __set($name, $val)
	{
		// Check if settable
		if (! isset($this->properties[$name])) {
			return;
		}

		// Get the type
		$type = gettype($this->properties[$name]);

		// Cast type
		if ($type === 'integer') {
			$this->properties[$name] = (int) $val;
		} elseif ($type === 'boolean') {
			$this->properties[$name] = $val === 'y' ||
				$val === 'yes' ||
				$val === 'true' ||
				$val === '1' ||
				$val === 1 ||
				$val === true;
		} elseif ($type === 'string') {
			$this->properties[$name] = (string) $val;
		}

		return;
	}

	/**
	 * Get magic method
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}

		if (isset($this->{$name})) {
			return $this->{$name};
		}

		return null;
	}

	/**
	 * Run method to be implemented by extending class
	 *
	 * @param string $sourceFilePath
	 * @return string
	 */
	abstract public function run($sourceFilePath);

	/**
	 * Get resource
	 *
	 * @param string $sourceFilePath
	 * @return bool|resource|\Imagick
	 */
	protected function getResource($sourceFilePath)
	{
		// Get the source image file type
		$this->sourceFileType = exif_imagetype($sourceFilePath);

		// Make sure file type is one we can work with
		if ($this->sourceFileType !== 1 &&
			$this->sourceFileType !== 2 &&
			$this->sourceFileType !== 3
		) {
			return false;
		}

		// Make sure image quality is not less than 0 or more than 100
		$this->quality = (int) $this->quality;
		if ($this->quality < 1) {
			$this->quality = 1;
		} elseif ($this->quality > 100) {
			$this->quality = 100;
		}

		// Create the correct image resource based on file type
		if (! $this->forceGD && extension_loaded('imagick')) {
			// Create new Imagick class
			$resource = new \Imagick();

			// Read in the image
			$resource->readImage($sourceFilePath);
		} else {
			if ($this->sourceFileType === 1) {
				$resource = imagecreatefromgif($sourceFilePath);
			} elseif ($this->sourceFileType === 2) {
				$resource = imagecreatefromjpeg($sourceFilePath);
			} else { // $this->sourceFileType === 3
				$resource = imagecreatefrompng($sourceFilePath);
			}
		}

		return $resource;
	}

	/**
	 * Create image and fill background appropriately
	 *
	 * @return resource GD resource
	 */
	protected function createGDImage()
	{
		// Create the new image resource
		$newImage = imagecreatetruecolor(
			$this->width,
			$this->height
		);

		// Set the image background color
		if ($this->sourceFileType === 2 || $this->forceJpg) {
			$color = $this->background ?: 'ffffff';

			$rgb = $this->convertHex($color);

			$background = imagecolorallocate(
				$newImage,
				$rgb['r'],
				$rgb['g'],
				$rgb['b']
			);

			imagefill($newImage, 0, 0, $background);
		} else {
			if ($this->background) {
				$rgb = $this->convertHex($this->background);

				imagefilter(
					$newImage,
					IMG_FILTER_COLORIZE,
					$rgb['r'],
					$rgb['g'],
					$rgb['b']
				);
			} else {
				$transparent = imagecolortransparent(
					$newImage,
					imagecolorallocatealpha($newImage, 0, 0, 0, 0)
				);

				if ($this->sourceFileType === 3) {
					imagealphablending($newImage, false);
					imagesavealpha($newImage, true);
				}

				imagefill($newImage, 0, 0, $transparent);
			}
		}

		return $newImage;
	}

	/**
	 * Convert HEX color to RGB
	 *
	 * @param string $hex
	 * @return bool|array
	 */
	private function convertHex($hex)
	{
		// Make sure this is a hex value
		if (strlen($hex) !== 6) {
			return false;
		}

		list($r, $g, $b) = array(
			$hex[0] . $hex[1],
			$hex[2] . $hex[3],
			$hex[4] . $hex[5]
		);

		return array(
			'r' => hexdec($r),
			'g' => hexdec($g),
			'b' => hexdec($b)
		);
	}

	/**
	 * Write image to destination
	 *
	 * @param resource|\Imagick $resource
	 * @return string
	 */
	protected function writeImageToDestination($resource)
	{
		// Set the destination file path
		$destFilePath = ANSEL_CACHE . uniqid();

		// Set the file extension
		if ($this->sourceFileType === 2 || $this->forceJpg) {
			$destFilePath .= '.jpg';
		} elseif ($this->sourceFileType === 1) {
			$destFilePath .= '.gif';
		} else { // $this->sourceFileType === 3
			$destFilePath .= '.png';
		}

		// Crop the image with the correct library
		if ($resource instanceof \Imagick) {
			// Set format to jpeg if applicable
			if ($this->forceJpg) {
				$resource->setImageFormat('jpeg');
				$resource->setImageCompression(\Imagick::COMPRESSION_JPEG);
				$resource->setImageCompressionQuality($this->quality);
			}

			// Write out the image
			$resource->writeImage($destFilePath);
		} else {
			// Write image
			if ($this->sourceFileType === 2 || $this->forceJpg) {
				imagejpeg($resource, $destFilePath, $this->quality);
			} elseif ($this->sourceFileType === 1) {
				imagegif($resource, $destFilePath);
			} elseif ($this->sourceFileType === 3) {
				imagepng($resource, $destFilePath, 9);
			}
		}

		// Return the file location
		return $destFilePath;
	}
}
