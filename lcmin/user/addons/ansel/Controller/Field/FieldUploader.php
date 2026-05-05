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

namespace BoldMinded\Ansel\Controller\Field;

use BoldMinded\Ansel\Service\UploadKeys;
use ExpressionEngine\Core\Request;
use BoldMinded\Ansel\Model\PHPFileUpload;
use BoldMinded\Ansel\Service\Uploader;

/**
 * Class FieldUploader
 */
class FieldUploader
{
    /**
     * @var UploadKeys $uploadKeys
     */
    private $uploadKeys;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var PHPFileUpload $phpFileUpload
     */
    private $phpFileUpload;

    /**
     * @var Uploader $uploader
     */
    private $uploader;

    /**
     * @var \EE_Output $output
     */
    private $output;

    /**
     * FieldUploader constructor
     *
     * @param UploadKeys $uploadKeys
     * @param Request $request
     * @param PHPFileUpload $phpFileUpload
     * @param Uploader $uploader
     * @param \EE_Output $output
     */
    public function __construct(
        UploadKeys $uploadKeys,
        Request $request,
        PHPFileUpload $phpFileUpload,
        Uploader $uploader,
        \EE_Output $output
    ) {
        // Inject dependencies
        $this->uploadKeys = $uploadKeys;
        $this->request = $request;
        $this->phpFileUpload = $phpFileUpload;
        $this->uploader = $uploader;
        $this->output = $output;
    }

    /**
     * Post method
     */
    public function post()
    {
        // Get the upload key
        $key = $this->request->post('uploadKey');

        // Check the key
        if (! $this->uploadKeys->isValidKey($key)) {
            $this->output->send_ajax_response(array(
                'error' => 'Invalid upload key'
            ), true);
            return;
        }

        // Make sure we have a file
        $file = $this->request->file('file') ?: array();

        // Populate the upload model
        $this->phpFileUpload->__construct($file);

        // Check if the upload is valid
        if (! $this->phpFileUpload->isValidUpload()) {
            $this->output->send_ajax_response(array(
                'error' => 'Invalid file upload'
            ), true);
            return;
        }

        // Get size requirements
        $minWidth = (int) $this->request->post('minWidth');
        $minHeight = (int) $this->request->post('minHeight');

        $meetsMin = true;

        // Get image size
        $imageSize = getimagesize($this->phpFileUpload->tmp_name);

        // Get image width
        $width = $imageSize[0];

        // Get image height
        $height = $imageSize[1];

        // Check for min width
        if ($width < $minWidth) {
            $meetsMin = false;
        }

        // Check for min height
        if ($height < $minHeight) {
            $meetsMin = false;
        }

        // Check if the upload is valid
        if (! $meetsMin) {
            $this->output->send_ajax_response(array(
                'error' => 'Min not met'
            ), true);
            return;
        }

        // Remove spaces from name
        $this->phpFileUpload->name = str_replace(
            ' ',
            '-',
            $this->phpFileUpload->name
        );

        // Send the file upload model to the uploader
        $this->uploader->postUpload($this->phpFileUpload);

        // Send the ajax response
        $this->output->send_ajax_response($this->phpFileUpload->toArray());
    }
}
