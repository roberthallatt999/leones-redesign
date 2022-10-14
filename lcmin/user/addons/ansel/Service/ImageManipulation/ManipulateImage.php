<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\ImageManipulation;

use BuzzingPixel\Ansel\Service\FileCacheService;
use ImageOptimizer\OptimizerFactory;
use EE_Config as EEConfigService;

/**
 * Class ManipulateImage
 *
 * @property int $x
 * @property int $y
 * @property int $width
 * @property int $height
 * @property int $minWidth
 * @property int $minHeight
 * @property int $maxWidth
 * @property int $maxHeight
 * @property bool $forceJpg
 * @property int $quality
 * @property bool $optimize
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ManipulateImage
{
	/**
	 * Properties
	 *
	 * @var array $properties
	 */
	private $properties = array(
		'x' => 0,
		'y' => 0,
		'width' => 0,
		'height' => 0,
		'minWidth' => 0,
		'minHeight' => 0,
		'maxWidth' => 0,
		'maxHeight' => 0,
		'forceJpg' => false,
		'quality' => 90,
		'optimize' => true
	);

	/**
	 * @var FileCacheService $fileCacheService
	 */
	private $fileCacheService;

	/**
	 * @var CropImage $cropImageService
	 */
	private $cropImageService;

	/**
	 * @var ResizeImage $resizeImageService
	 */
	private $resizeImageService;

	/**
	 * @var CopyImage $copyImageService
	 */
	private $copyImageService;

	/**
	 * @var OptimizerFactory $optimizerFactory
	 */
	private $optimizerFactory;

	/**
	 * @var EEConfigService $eeConfigService
	 */
	private $eeConfigService;

	/**
	 * Constructor
	 *
	 * @param FileCacheService $fileCacheService
	 * @param CropImage $cropImageService
	 * @param ResizeImage $resizeImageService
	 * @param CopyImage $copyImageService
	 * @param OptimizerFactory $optimizerFactory
	 * @param EEConfigService $eeConfigService
	 */
	public function __construct(
		FileCacheService $fileCacheService,
		CropImage $cropImageService,
		ResizeImage $resizeImageService,
		CopyImage $copyImageService,
		$optimizerFactory,
		EEConfigService $eeConfigService
	) {
		// Inject dependencies
		$this->fileCacheService = $fileCacheService;
		$this->cropImageService = $cropImageService;
		$this->resizeImageService = $resizeImageService;
		$this->copyImageService = $copyImageService;
		$this->optimizerFactory = $optimizerFactory;
		$this->eeConfigService = $eeConfigService;
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
	 * Run
	 *
	 * @param string $filePath Path to the image to manipulate
	 * @return string
	 */
	public function run($filePath)
	{
		if (! $filePath) {
			return null;
		}

		// Get image size
		$imageSize = getimagesize($filePath);
		$imgWidth = $imageSize[0];
		$imgHeight = $imageSize[1];

		// Start a high quality image variable
		$runningImage = $filePath;

		// Check if cropping is needed
		if (($this->width && $this->width < $imgWidth) ||
			($this->height && $this->height < $imgHeight)
		) {
			// Set cropping values
			$this->cropImageService->width = $this->width;
			$this->cropImageService->height = $this->height;
			$this->cropImageService->x = $this->x;
			$this->cropImageService->y = $this->y;

			// Set quality to 100 for this operation
			$this->cropImageService->quality = 100;

			// Set old image path
			$oldImagePath = $runningImage;

			// Get the cropped image
			$runningImage = $this->cropImageService->run($runningImage);

			// If the old image path is not equal to the file path, remove it
			if ($oldImagePath !== $filePath) {
				unlink($oldImagePath);
			}
		}

		// Check if up-scaling is needed
		if (($this->minWidth && $imgWidth < $this->minWidth) ||
			($this->minHeight && $imgHeight < $this->minHeight)
		) {
			// Calculate the upscale by width
			$dimensions = $this->calcUpscaleByWidth();

			// Check if height meets requirements
			if ($this->minHeight && $dimensions['height'] < $this->minHeight) {
				// Calculate dimensions by height
				$dimensions = $this->calcUpscaleByHeight();
			}

			// Because of pixel rounding, determine how close width is and set
			// to min if within tolerance
			$difference = 0;
			if ($dimensions['width'] > $this->minWidth) {
				$difference = $dimensions['width'] - $this->minWidth;
			} elseif ($this->minWidth > $dimensions['width']) {
				$difference = $this->minWidth - $dimensions['width'];
			}
			if ($difference < 4) {
				$dimensions['width'] = $this->minWidth;
			}

			// Because of pixel rounding, determine how close height is and set
			// to min if within tolerance
			$difference = 0;
			if ($dimensions['height'] > $this->minHeight) {
				$difference = $dimensions['height'] - $this->minHeight;
			} elseif ($this->minHeight > $dimensions['height']) {
				$difference = $this->minHeight - $dimensions['height'];
			}
			if ($difference < 4) {
				$dimensions['height'] = $this->minHeight;
			}

			// Set resize parameters
			$this->resizeImageService->width = $dimensions['width'];
			$this->resizeImageService->height = $dimensions['height'];

			// Set quality to 100 for this operation
			$this->cropImageService->quality = 100;

			// Set old image path
			$oldImagePath = $runningImage;

			// Get the re-sized image
			$runningImage = $this->resizeImageService->run($runningImage);

			// If the old image path is not equal to the file path, remove it
			if ($oldImagePath !== $filePath) {
				unlink($oldImagePath);
			}
		}

		// Check if resizing is needed
		if (($this->maxWidth && $this->width > $this->maxWidth) ||
			($this->maxHeight && $this->height > $this->maxHeight)
		) {
			// Calculate resize by width
			$dimensions = $this->calcResizeByWidth();

			// Check if height meets requirements this way
			if (! $this->maxWidth ||
				$this->maxHeight && $dimensions['height'] > $this->maxHeight
			) {
				// Calculate dimensions by height if height is over
				$dimensions = $this->calcResizeByHeight();
			}

			// Set resize parameters
			$this->resizeImageService->width = $dimensions['width'];
			$this->resizeImageService->height = $dimensions['height'];

			// Set quality to 100 for this operation
			$this->cropImageService->quality = 100;

			// Set old image path
			$oldImagePath = $runningImage;

			// Get the re-sized image
			$runningImage = $this->resizeImageService->run($runningImage);

			// If the old image path is not equal to the file path, remove it
			if ($oldImagePath !== $filePath) {
				unlink($oldImagePath);
			}
		}

		// Copy image at specified quality
		$this->copyImageService->quality = $this->quality;

		// Set whether jpeg should be forced
		$this->copyImageService->forceJpg = $this->forceJpg;

		// Set old image path
		$oldImagePath = $runningImage;

		// Get the copied image
		$runningImage = $this->copyImageService->run($runningImage);

		// If the old image path is not equal to the file path, remove it
		if ($oldImagePath !== $filePath) {
			unlink($oldImagePath);
		}

		// Bail out here if optimization is not supported or not requested
		if (! ANSEL_SUPPORTS_OPTIM || ! $this->optimize) {
			return $runningImage;
		}

		// Get the image type
		$imageType = exif_imagetype($runningImage);

		// Get the appropriate image optimizer
		$imageOptimizer = null;
		if ($imageType === IMAGETYPE_GIF) {
			// Check if image optimization for gifsicle is disabled
			if ($this->eeConfigService->item('disableGifsicle', 'ansel')) {
				return $runningImage;
			}

			// Get optimizer
			$imageOptimizer = $this->optimizerFactory->get('gifsicle');
		} elseif ($imageType === IMAGETYPE_JPEG) {
			// Check if image optimization for jpegoptim is disabled
			if ($this->eeConfigService->item('disableJpegoptim', 'ansel')) {
				return $runningImage;
			}

			// Get optimizer
			$imageOptimizer = $this->optimizerFactory->get('jpegoptim');
		} elseif ($imageType === IMAGETYPE_PNG) {
			// Check if image optimization for optipng is disabled
			if ($this->eeConfigService->item('disableOptipng', 'ansel')) {
				return $runningImage;
			}

			// Get optimizer
			$imageOptimizer = $this->optimizerFactory->get('optipng');
		}

		// Make sure we have an image optimizer here
		if (! $imageOptimizer) {
			return $runningImage;
		}

		// Run the optimizer
		$imageOptimizer->optimize($runningImage);

		// Return the image path
		return $runningImage;
	}

	/**
	 * Calculate resize by width
	 *
	 * @return array {
	 *     @var float $ratio
	 *     @var int $width
	 *     @var int $height
	 * }
	 */
	private function calcResizeByWidth()
	{
		// Get the ratio
		$ratio = (float) $this->maxWidth / $this->width;

		return array(
			'ratio' => $ratio,
			'width' => $this->maxWidth,
			'height' => (int) round($this->height * $ratio)
		);
	}

	/**
	 * Calculate Resize by height
	 *
	 * @return array {
	 *     @var float $ratio
	 *     @var int $height
	 *     @var int $width
	 * }
	 */
	private function calcResizeByHeight()
	{
		// Get the ratio
		$ratio = (float) $this->maxHeight / $this->height;

		return array(
			'ratio' => $ratio,
			'height' => $this->maxHeight,
			'width' => (int) round($this->width * $ratio)
		);
	}

	/**
	 * Calculate resize by width
	 *
	 * @return array {
	 *     @var float $ratio
	 *     @var int $width
	 *     @var int $height
	 * }
	 */
	private function calcUpscaleByWidth()
	{
		// Get the ratio
		$ratio = (float) $this->minWidth / $this->width;

		return array(
			'ratio' => $ratio,
			'width' => $this->minWidth,
			'height' => (int) round($this->height * $ratio)
		);
	}

	/**
	 * Calculate Resize by height
	 *
	 * @return array {
	 *     @var float $ratio
	 *     @var int $height
	 *     @var int $width
	 * }
	 */
	private function calcUpscaleByHeight()
	{
		// Get the ratio
		$ratio = (float) $this->minHeight / $this->height;

		return array(
			'ratio' => $ratio,
			'height' => $this->minHeight,
			'width' => (int) round($this->width * $ratio)
		);
	}
}
