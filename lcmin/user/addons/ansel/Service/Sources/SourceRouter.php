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

namespace BoldMinded\Ansel\Service\Sources;

use BoldMinded\Ansel\Model\File as FileModel;
use BoldMinded\Ansel\Record\Image;
use BoldMinded\Ansel\Service\Sources\Ee as EESource;
use BoldMinded\Ansel\Service\Sources\Assets as AssetsSource;

/**
 * Class SourceRouter
 *
 * @method string getFileChooserLink(mixed $identifier)
 * @method string uploadFile(UploadLocation $location, string $filePath, string $subFolder = null, Image $anselRecord = null, bool $insertTimestamp = false,)
 * @method void deleteFile(UploadLocation $location, string $fileName, string $subFolder = null, Image $anselRecord = null)
 * @method void updateFileAttributes(int $fileId, array $attributes)
 * @method FileModel addFile(UploadLocation $location, string $filePath, Image $anselRecord = null)
 * @method void removeFile(mixed $fileIdentifier)
 * @method string getSourceUrl(mixed $identifier)
 * @method string getFileUrl(mixed $fileIdentifier)
 * @method null|FileModel getFileModel(mixed $fileIdentifier)
 * @method string cacheFileLocallyById(mixed $fileIdentifier)
 * @method array getSourceModels(array $ids)
 * @method array getFileModels(array $ids)
 * @method bool isSymLink(UploadLocation $location)
 */
class SourceRouter
{
    /**
     * @var string $source
     */
    private $source = 'ee';

    /**
     * @var EESource $eeSource
     */
    private $eeSource;

    /**
     * Constructor
     *
     * @param EESource $eeSource
     * @param AssetsSource $assetsSource
     */
    public function __construct(
        EESource $eeSource,
    ) {
        // Inject dependencies
        $this->eeSource = $eeSource;
    }

    /**
     * Call magic method
     *
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        // Call the method on the source class and apply the arguments
        return call_user_func_array(
            array(
                $this->{"{$this->source}Source"},
                $name
            ),
            $args
        );
    }

    /**
     * Set the source to use
     *
     * @param string $source ee|assets
     */
    public function setSource($source)
    {
        // These are the accepted values of $source
        $accepted = [
            'ee',
        ];

        // Make sure the value is accepted
        if (! in_array($source, $accepted)) {
            return;
        }

        // Set the source
        $this->source = $source;
    }
}
