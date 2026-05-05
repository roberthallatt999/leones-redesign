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

        if (!$resource) {
            ee('ansel:Logger')->developer(
                sprintf('[Ansel] Could not load resource for %s', $sourceFilePath),
                true
            );

            return '';
        }

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
