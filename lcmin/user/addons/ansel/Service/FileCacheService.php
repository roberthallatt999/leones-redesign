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

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Logger;
use Exception;
use ExpressionEngine\Dependency\League\Flysystem\Filesystem;
use ExpressionEngine\Model\File\File;
use ExpressionEngine\Model\File\FileSystemEntity;

/**
 * Class FileCacheService
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class FileCacheService
{
    /**
     * Check file cache path
     */
    public function __construct(
        private Logger $logger,
        private string $httpUser = '',
        private string $httpPass = ''
    )
    {
        if (! is_dir(ANSEL_CACHE)) {
            mkdir(ANSEL_CACHE, DIR_WRITE_MODE, true);
        }

        if (! is_dir(ANSEL_CACHE_PERSISTENT)) {
            mkdir(ANSEL_CACHE_PERSISTENT, DIR_WRITE_MODE, true);
        }

        // Clean up
        $this->cleanUp();
    }

    /**
     * Clean up directory
     *
     * @param string $dirPath
     */
    private function cleanUpDir($dirPath)
    {
        // Normalize dir path
        $dirPath = rtrim($dirPath, '/');

        // Some environments can't distinguish between empty match and an error
        $glob = glob("{$dirPath}/*") ?: array();

        // Iterate through items in directory
        foreach ($glob as $item) {
            // Check if item is directory
            if (is_dir($item)) {
                // Run clean up
                $this->cleanUpDir($item);

                // Check for items in directory
                $items = glob("{$item}/*");

                // If there are no items in the directory, remove it
                if (! $items) {
                    rmdir($item);
                }
            } elseif (file_exists($item)) { // File
                // Check if file is older than 1 day
                if (strtotime('+ 1 day', filemtime($item)) < time()) {
                    // Delete the file
                    unlink($item);
                }
            }
        }
    }

    /**
     * Clean up
     */
    public function cleanUp()
    {
        // Clean up
        $this->cleanUpDir(ANSEL_CACHE);
    }

    /**
     * Check if cache file exists
     *
     * @param string $fileName
     * @param bool $persistent
     * @return bool
     */
    public function cacheFileExists($fileName, $persistent = false)
    {
        // Check if persistent or not
        if ($persistent) {
            $cachePath = ANSEL_CACHE_PERSISTENT;
        } else {
            $cachePath = ANSEL_CACHE;
        }

        // Get full path
        $fullPath = "{$cachePath}{$fileName}";

        // Return results
        return file_exists($fullPath);
    }

    /**
     * Create an empty cache file
     *
     * @param string $ext Optional
     * @param bool $persistent Optional
     * @return bool|string
     */
    public function createEmptyFile($ext = '', $persistent = false)
    {
        // Add a period to the file extension
        if ($ext) {
            $ext = ".{$ext}";
        }

        // Check if persistent or not
        if ($persistent) {
            $cachePath = ANSEL_CACHE_PERSISTENT;
        } else {
            $cachePath = ANSEL_CACHE;
        }

        // Set the cache file name
        $cacheFile = $cachePath . uniqid() . $ext;

        // Write the file to cache
        file_put_contents($cacheFile, '');

        // Return the file path/name
        return $cacheFile;
    }

    /**
     * Cache a file
     *
     * @param string|File|FileSystemEntity $file Path on disk or URL
     * @param string|bool $extension
     * @return string|null Path to cached file
     */
    public function cacheByPath($file, $extension = false)
    {
        if (!is_string($file)) {
            $file = $file->getAbsoluteURL();
        }

        // Get separator
        $sep = DIRECTORY_SEPARATOR;

        // Set unique ID
        $uniqueId = uniqid();

        // Set path
        $path = ANSEL_CACHE . $uniqueId;

        if ($file instanceof FileSystemEntity) {
            $fileInfo = pathinfo($file->file_name);

            /** @var Filesystem $fs */
            $fs = $file->UploadDestination->getFilesystem();

            if (!$fs->isLocal()) {
                $fileContents = $fs->read($file->getAbsolutePath());
            } else {
                $fileContents = $this->getFileContents($file);
            }
        } else {
            // Get file info so we can get the extension
            $fileInfo = pathinfo($file);

            // Get the file contents
            $fileContents = $this->getFileContents($file);
        }

        // If there is no file, end processing
        if (! $fileContents) {
            $this->logger->developer(sprintf(
                '[Ansel] Could not cache %s because contains no contents.',
                $file
            ));

            return null;
        }

        // If extension not passed in and file to cache has extension
        if (! $extension and isset($fileInfo['extension'])) {
            $extension = $fileInfo['extension'];
        }

        // Set the cache file name
        $cacheFile = $path . $sep . $fileInfo['filename'];
        $cacheFile .= $extension ? ".{$extension}" : '';

        // Create the directory
        mkdir($path, DIR_WRITE_MODE, true);

        try {
            $this->logger->developer(sprintf(
                '[Ansel] Attempting cacheByPath: %s',
                $cacheFile,
            ));

            // Write the file to cache
            file_put_contents($cacheFile, $fileContents);
        } catch (Exception $exception) {
            $this->logger->developer(sprintf(
                '[Ansel] Error in cacheByPath attempting to write file: %s',
                $exception->getMessage(),
            ));
        }

        // Return the file path/name
        return $cacheFile;
    }

    /**
     * Set a cache file
     *
     * @param string $fileName
     * @param string $contents
     * @param bool $persistent
     */
    public function setCacheFile($fileName, $contents, $persistent = false)
    {
        // Check if persistent or not
        if ($persistent) {
            $cachePath = ANSEL_CACHE_PERSISTENT;
        } else {
            $cachePath = ANSEL_CACHE;
        }

        // Get full path
        $fullPath = "{$cachePath}{$fileName}";

        // Write the file to cache
        file_put_contents($fullPath, $contents);
    }

    /**
     * Get cache file contents
     *
     * @param string $fileName
     * @param bool $persistent
     * @return string|null
     */
    public function getCacheFileContents($fileName, $persistent = false)
    {
        // Check if the file exists
        if (! $this->cacheFileExists($fileName, $persistent)) {
            return null;
        }

        // Check if persistent or not
        if ($persistent) {
            $cachePath = ANSEL_CACHE_PERSISTENT;
        } else {
            $cachePath = ANSEL_CACHE;
        }

        return file_get_contents("{$cachePath}{$fileName}");
    }

    /**
     * Get file contents
     *
     * @param string|File|FileSystemEntity $file
     * @return string
     */
    private function getFileContents($file)
    {
        if (!is_string($file)) {
            $file = $file->getAbsoluteURL();
        }

        $context = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        ));

        // Set a url safe filename
        $fileArr = explode(DIRECTORY_SEPARATOR, $file);
        foreach ($fileArr as $key => $val) {
            if (strpos($val, 'http') === false) {
                $fileArr[$key] = rawurlencode($val);
            }
        }
        $fileUrlSafe = implode(DIRECTORY_SEPARATOR, $fileArr);

        // Try the file as is and return it if applicable
        $tryFile = $file;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try the file as url safe
        $tryFile = $fileUrlSafe;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Get the site URL
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $protocol = $secure ? 'https://' : 'http://';
        $auth = '';

        if ($this->httpUser && $this->httpPass) {
            $auth = $this->httpUser . ':' . $this->httpPass . '@';
        }

        $siteUrl = $protocol . $auth . $_SERVER['SERVER_NAME'];

        // Try the file by pre-pending the site URL
        $tryFile = $siteUrl . $file;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try the file url safe
        $tryFile = $siteUrl . $fileUrlSafe;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try the file by pre-pending the site url and a forward slash
        $tryFile = $siteUrl .'/' . $file;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try the file url safe
        $tryFile = $siteUrl .'/' . $fileUrlSafe;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try with the server port
        $tryFile = rtrim($siteUrl, '/') .":{$_SERVER['SERVER_PORT']}" . $file;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try url safe
        $tryFile = rtrim($siteUrl, '/') .":{$_SERVER['SERVER_PORT']}" . $fileUrlSafe;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try the file by adding a forward slash after port
        $tryFile = rtrim($siteUrl, '/') .":{$_SERVER['SERVER_PORT']}/" . $file;
        $fileContents = @file_get_contents($tryFile, false, $context);
        if ($fileContents) {
            return $fileContents;
        }

        // Try the file url safe
        $tryFile = rtrim($siteUrl, '/') .":{$_SERVER['SERVER_PORT']}/" . $fileUrlSafe;
        $fileContents = @file_get_contents($tryFile, false, $context);
        return $fileContents;
    }
}
