<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\AnselImages;

use BuzzingPixel\Ansel\Model\FieldSettings as FieldSettingsModel;
use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BuzzingPixel\Ansel\Service\Sources\SourceRouter;
use BuzzingPixel\Ansel\Service\FileCacheService;
use BuzzingPixel\Ansel\Service\ImageManipulation\ManipulateImage;
use BuzzingPixel\Ansel\Record\Image as ImageRecord;

/**
 * Class SaveRow
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class SaveRow
{
	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * @var SourceRouter $sourceRouter
	 */
	private $sourceRouter;

	/**
	 * @var FileCacheService $fileCacheService
	 */
	private $fileCacheService;

	/**
	 * @var ManipulateImage $manipulateImage
	 */
	private $manipulateImage;

	/**
	 * @var int $memberId
	 */
	private $memberId;

	/**
	 * @var int $siteId
	 */
	private $siteId;

	/**
	 * Constructor
	 *
	 * @param RecordBuilder $recordBuilder
	 * @param SourceRouter $sourceRouter
	 * @param FileCacheService $fileCacheService
	 * @param ManipulateImage $manipulateImage
	 * @param int $memberId
	 * @param int $siteId
	 */
	public function __construct(
		RecordBuilder $recordBuilder,
		SourceRouter $sourceRouter,
		FileCacheService $fileCacheService,
		ManipulateImage $manipulateImage,
		$memberId,
		$siteId
	) {
		// Inject dependencies
		$this->recordBuilder = $recordBuilder;
		$this->sourceRouter = $sourceRouter;
		$this->fileCacheService = $fileCacheService;
		$this->manipulateImage = $manipulateImage;
		$this->memberId = (int) $memberId;
		$this->siteId = (int) $siteId;
	}

	/**
	 * Save
	 *
	 * @param array $data
	 * @param FieldSettingsModel $fieldSettings
	 * @param int $sourceId
	 * @param int $contentId
	 * @param int $rowId
	 * @param int $colId
	 */
	public function save(
		$data,
		FieldSettingsModel $fieldSettings,
		$sourceId,
		$contentId,
		$rowId = null,
		$colId = null
	) {
		// Set the time
		$time = time();

		// Retinize the field settings
		$fieldSettings->retinizeReturnValues();

		// Get the upload directory vars
		$uploadDirType = $fieldSettings->getUploadDirectory()->type;
		$uploadDirId = $fieldSettings->getUploadDirectory()->identifier;

		// Get save directory vars
		$saveDirType = $fieldSettings->getSaveDirectory()->type;
		$saveDirId = $fieldSettings->getSaveDirectory()->identifier;

		// Set up the sourceRouter for upload location
		$this->sourceRouter->setSource($uploadDirType);

		// If there is an image file_location, upload file to source dir
		if (isset($data['file_location']) && is_file($data['file_location'])) {
			// Upload the file and get the return file model
			$sourceFileModel = $this->sourceRouter->addFile(
				$uploadDirId,
				$data['file_location']
			);

			// Set source file id on data
			$data['source_file_id'] = $sourceFileModel->file_id;

			// Remove the file
			if (file_exists($data['file_location'])) {
				unlink($data['file_location']);
			}

		// Otherwise we need to get the source file model
		} else {
			// Upload the file and get the return file model
			$sourceFileModel = $this->sourceRouter->getFileModel(
				$data['source_file_id']
			);
		}

		// Set image modified variable
		$imageModified = false;

		// Check if there is an image id
		if ($data['ansel_image_id']) {
			// Get record query builder
			$anselRecord = $this->recordBuilder->get('ansel:Image');

			// Filter the record
			$anselRecord->filter('id', $data['ansel_image_id']);

			// Get the record
			$anselRecord = $anselRecord->first();

			/** @var ImageRecord $anselRecord */

			// Check if crop properties have been modified
			$x = (int) $data['x'];
			$y = (int) $data['y'];
			$width = (int) $data['width'];
			$height = (int) $data['height'];
			$sourceFileId = (int) $data['source_file_id'];

			if ($x !== $anselRecord->x ||
				$y !== $anselRecord->y ||
				$width !== $anselRecord->width ||
				$height !== $anselRecord->height ||
				$sourceFileId !== $anselRecord->original_file_id
			) {
				$imageModified = true;
			}
		} else {
			// If not, create a new record
			/** @var ImageRecord $anselRecord */
			$anselRecord = $this->recordBuilder->make('ansel:Image');

			// Set the upload date
			$anselRecord->setProperty('upload_date', $time);

			// The image has been "modified"
			$imageModified = true;
		}

		// Update info on record
		$anselRecord->setProperty('site_id', $this->siteId);
		$anselRecord->setProperty('source_id', $sourceId);
		$anselRecord->setProperty('content_id', $contentId);
		$anselRecord->setProperty('field_id', $fieldSettings->field_id);
		$anselRecord->setProperty('content_type', $fieldSettings->type);
		$anselRecord->setProperty('original_location_type', $uploadDirType);
		$anselRecord->setProperty('original_file_id', $data['source_file_id']);
		$anselRecord->setProperty('width', $data['width']);
		$anselRecord->setProperty('height', $data['height']);
		$anselRecord->setProperty('x', $data['x']);
		$anselRecord->setProperty('y', $data['y']);
		$anselRecord->setProperty('position', $data['order']);
		$anselRecord->setProperty('member_id', $this->memberId);

		$anselRecord->setProperty(
			'title',
			isset($data['title']) ? $data['title'] : ''
		);

		$anselRecord->setProperty(
			'caption',
			isset($data['caption']) ? $data['caption'] : ''
		);

		if ($sourceFileModel) {
			$anselRecord->setProperty(
				'original_extension',
				$sourceFileModel->extension
			);

			$anselRecord->setProperty(
				'original_filesize',
				$sourceFileModel->filesize
			);
		}

		$anselRecord->setProperty(
			'cover',
			(isset($data['cover']) && $data['cover'] === 'true') ? 1 : 0
		);

		if ($rowId) {
			$anselRecord->setProperty('row_id', $rowId);
		}

		if ($colId) {
			$anselRecord->setProperty('col_id', $colId);
		}

		// Check if order is over max
		if ($fieldSettings->max_qty) {
			$order = (int) $data['order'];
			$anselRecord->setProperty(
				'disabled',
				$order > $fieldSettings->max_qty ? 1 : 0
			);
		} else {
			$anselRecord->setProperty('disabled', 0);
		}

		// Save the record (this will make the ID available for us later
		// if this is a new record, and also save data if the image exists
		// and is not modified
		$anselRecord->save();

		// If image has not been modified, end processing
		if (! $imageModified || ! $sourceFileModel) {
			return;
		}

		$oldHighQualDirName = $anselRecord->getHighQualityDirectoryName();
		$oldThumbDirName = $anselRecord->getThumbDirectoryName();
		$anselRecord->setProperty('upload_location_type', $saveDirType);
		$anselRecord->setProperty('upload_location_id', $saveDirId);
		$highQualityDirName = $anselRecord->getHighQualityDirectoryName();
		$thumbDirName = $anselRecord->getThumbDirectoryName();

		// Let's get a locally cached version of the source file
		$localSourceFile = $this->sourceRouter->cacheFileLocallyById(
			$sourceFileModel->file_id
		);


		/**
		 *  Run image manipulations
		 */

		// Get high quality image
		$this->manipulateImage->x = $data['x'];
		$this->manipulateImage->y = $data['y'];
		$this->manipulateImage->width = $data['width'];
		$this->manipulateImage->height = $data['height'];
		$this->manipulateImage->maxWidth = $fieldSettings->max_width;
		$this->manipulateImage->maxHeight = $fieldSettings->max_height;
		$this->manipulateImage->quality = 100;
		$this->manipulateImage->optimize = false;
		$highQualImage = $this->manipulateImage->run($localSourceFile);
		$pathInfo = pathinfo($highQualImage);
		$upload = "{$pathInfo['dirname']}/{$sourceFileModel->filename}";
		$upload .= "-{$anselRecord->id}-{$time}.{$pathInfo['extension']}";
		copy($highQualImage, $upload);

		// Get final image size
		$finalImageSize = getimagesize($highQualImage);

		// Set up the sourceRouter for save location
		$this->sourceRouter->setSource($saveDirType);

		// Upload high quality image
		$this->sourceRouter->uploadFile(
			$saveDirId,
			$upload,
			"{$highQualityDirName}/{$anselRecord->id}"
		);

		// Remove the file
		if (file_exists($upload)) {
			unlink($upload);
		}

		// Get ansel thumbnail
		$thumbSize = $this->calcThumbSize($finalImageSize[0]);
		$this->manipulateImage->x = 0;
		$this->manipulateImage->y = 0;
		$this->manipulateImage->width = $finalImageSize[0];
		$this->manipulateImage->height = $finalImageSize[1];
		$this->manipulateImage->maxWidth = $thumbSize ?
			$thumbSize['width'] :
			$finalImageSize[0];
		$this->manipulateImage->maxHeight = $thumbSize ?
			$thumbSize['height'] :
			$finalImageSize[1];
		$this->manipulateImage->quality = 90;
		$this->manipulateImage->optimize = true;
		$thumbImage = $this->manipulateImage->run($highQualImage);
		$pathInfo = pathinfo($thumbImage);
		$upload = "{$pathInfo['dirname']}/{$sourceFileModel->filename}";
		$upload .= "-{$anselRecord->id}-{$time}.{$pathInfo['extension']}";
		copy($thumbImage, $upload);

		// Upload the thumbnail
		$this->sourceRouter->uploadFile(
			$saveDirId,
			$upload,
			"{$thumbDirName}/{$anselRecord->id}"
		);

		// Remove the file
		if (file_exists($upload)) {
			unlink($upload);
		}

		// Get standard image
		$this->manipulateImage->x = $data['x'];
		$this->manipulateImage->y = $data['y'];
		$this->manipulateImage->width = $data['width'];
		$this->manipulateImage->height = $data['height'];
		$this->manipulateImage->maxWidth = $fieldSettings->max_width;
		$this->manipulateImage->maxHeight = $fieldSettings->max_height;
		$this->manipulateImage->forceJpg = $fieldSettings->force_jpg;
		$this->manipulateImage->quality = $fieldSettings->quality;
		$this->manipulateImage->optimize = true;
		$standardImage = $this->manipulateImage->run($localSourceFile);
		$pathInfo = pathinfo($standardImage);
		$upload = "{$pathInfo['dirname']}/{$sourceFileModel->filename}";
		$upload .= "-{$anselRecord->id}-{$time}.{$pathInfo['extension']}";
		copy($standardImage, $upload);

		// Add the file to the source
		$saveFileModel = $this->sourceRouter->addFile($saveDirId, $upload);

		// Remove the file
		if (file_exists($upload)) {
			unlink($upload);
		}

		// Remove other temp files
		if (file_exists($localSourceFile)) {
			unlink($localSourceFile);
		}

		if (file_exists($highQualImage)) {
			unlink($highQualImage);
		}

		if (file_exists($thumbImage)) {
			unlink($thumbImage);
		}

		if (file_exists($standardImage)) {
			unlink($standardImage);
		}

		// Check if we should delete old images
		if ($anselRecord->getProperty('file_id')) {
			// Set up the source router location type
			$this->sourceRouter->setSource(
				$anselRecord->getProperty('upload_location_type')
			);

			// Delete the high quality file
			$this->sourceRouter->deleteFile(
				$anselRecord->getProperty('upload_location_id'),
				$anselRecord->getBasename(),
				"{$oldHighQualDirName}/{$anselRecord->id}"
			);

			// Delete the thumbnail file
			$this->sourceRouter->deleteFile(
				$anselRecord->getProperty('upload_location_id'),
				$anselRecord->getBasename(),
				"{$oldThumbDirName}/{$anselRecord->id}"
			);

			// Delete the standard file
			$this->sourceRouter->removeFile(
				$anselRecord->getProperty('file_id')
			);
		}

		// Update the record with final items
		$anselRecord->setProperty('file_id', $saveFileModel->file_id);
		$anselRecord->setProperty('filename', $saveFileModel->filename);
		$anselRecord->setProperty('extension', $saveFileModel->extension);
		$anselRecord->setProperty('filesize', $saveFileModel->filesize);
		$anselRecord->setProperty('modify_date', $time);

		// Final record save
		$anselRecord->save();
	}

	/**
	 * Calculate thumbnail size
	 *
	 * @param int $imageWidth
	 * @return bool|array {
	 *     @var float $ratio
	 *     @var int $width
	 *     @var int $width
	 * }
	 */
	private function calcThumbSize($imageWidth)
	{
		// Set the thumbnail max width
		$maxWidth = 336;

		// Check if generating thumbnail is necesary
		if ($imageWidth <= $maxWidth) {
			return false;
		}

		// Get the ratio
		$ratio = (float) $maxWidth / $imageWidth;

		return array(
			'ratio' => $ratio,
			'width' => $maxWidth,
			'height' => (int) round($imageWidth * $ratio)
		);
	}
}
