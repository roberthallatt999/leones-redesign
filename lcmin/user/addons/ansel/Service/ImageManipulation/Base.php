<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Add-ons
 * @category    Ansel
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/ansel
 * @license
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace BoldMinded\Ansel\Service\ImageManipulation;

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
    private bool $forceGD;

    /**
     * Properties
     *
     * @var array $properties
     */
    private array $properties = [
        'width' => 0,
        'height' => 0,
        'x' => 0,
        'y' => 0,
        'quality' => 90,
        'background' => '',
        'forceJpg' => false,
        'forceWebp' => false,
    ];

    /**
     * @var int $sourceFileType
     */
    private int $sourceFileType = 0;

    const FILE_TYPE_GIF = 1;
    const FILE_TYPE_JPG = 2;
    const FILE_TYPE_PNG = 3;
    const FILE_TYPE_WEBP = 18;

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
        if (!in_array($this->sourceFileType, [
            self::FILE_TYPE_GIF,
            self::FILE_TYPE_JPG,
            self::FILE_TYPE_PNG,
            self::FILE_TYPE_WEBP,
        ])) {
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
            if ($this->sourceFileType === self::FILE_TYPE_GIF) {
                $resource = imagecreatefromgif($sourceFilePath);
            } elseif ($this->sourceFileType === self::FILE_TYPE_JPG) {
                $resource = imagecreatefromjpeg($sourceFilePath);
            } elseif ($this->sourceFileType === self::FILE_TYPE_WEBP) {
                $resource = imagecreatefromwebp($sourceFilePath);
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
        if ($this->sourceFileType === self::FILE_TYPE_JPG || $this->forceJpg) {
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

                if ($this->sourceFileType === self::FILE_TYPE_PNG && !$this->forceWebP) {
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
        if (
            ($this->sourceFileType === self::FILE_TYPE_JPG || $this->forceJpg)
            && !$this->forceWebp
        ) {
            $destFilePath .= '.jpg';
        } elseif ($this->sourceFileType === self::FILE_TYPE_GIF) {
            $destFilePath .= '.gif';
        } elseif ($this->sourceFileType === self::FILE_TYPE_WEBP || $this->forceWebp) {
            $destFilePath .= '.webp';
        } else {
            $destFilePath .= '.png';
        }

        // Crop the image with the correct library
        if ($resource instanceof \Imagick) {
            // Set format to jpeg if applicable
            if (
                ($this->sourceFileType === self::FILE_TYPE_JPG || $this->forceJpg)
                && !$this->forceWebp
            ) {
                $resource->setImageFormat('jpeg');
                $resource->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $resource->setImageCompressionQuality($this->quality);
            }

            if ($this->sourceFileType === self::FILE_TYPE_WEBP || $this->forceWebp) {
                $resource->setImageFormat('webp');
                $resource->setImageCompressionQuality($this->quality);
            }

            // Write out the image
            $resource->writeImage($destFilePath);
        } else {
            // Write image
            if (
                ($this->sourceFileType === self::FILE_TYPE_JPG || $this->forceJpg)
                && !$this->forceWebp
            ) {
                imagejpeg($resource, $destFilePath, $this->quality);
            } elseif ($this->sourceFileType === self::FILE_TYPE_GIF) {
                imagegif($resource, $destFilePath);
            } elseif ($this->sourceFileType === self::FILE_TYPE_PNG) {
                imagepng($resource, $destFilePath, 9);
            } elseif ($this->sourceFileType === self::FILE_TYPE_WEBP || $this->forceWebp) {
                imagewebp($resource, $destFilePath);
            }
        }

        // Return the file location
        return $destFilePath;
    }
}
