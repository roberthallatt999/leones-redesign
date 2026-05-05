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

namespace BoldMinded\Ansel\Service;

use BoldMinded\Ansel\Model\PHPFileUpload;

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
