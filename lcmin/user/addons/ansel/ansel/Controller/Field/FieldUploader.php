<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\Field;

use BuzzingPixel\Ansel\Service\UploadKeys;
use EllisLab\ExpressionEngine\Core\Request;
use BuzzingPixel\Ansel\Model\PHPFileUpload;
use BuzzingPixel\Ansel\Service\Uploader;

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
