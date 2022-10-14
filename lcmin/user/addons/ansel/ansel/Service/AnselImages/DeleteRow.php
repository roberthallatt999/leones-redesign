<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\AnselImages;

use BuzzingPixel\Ansel\Record\Image as ImageRecord;
use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BuzzingPixel\Ansel\Service\Sources\SourceRouter;

/**
 * Class DeleteRow
 */
class DeleteRow
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
	 * Constructor
	 *
	 * @param RecordBuilder $recordBuilder
	 * @param SourceRouter $sourceRouter
	 */
	public function __construct(
		RecordBuilder $recordBuilder,
		SourceRouter $sourceRouter
	) {
		// Inject dependencies
		$this->recordBuilder = $recordBuilder;
		$this->sourceRouter = $sourceRouter;
	}

	/**
	 * Delete
	 *
	 * @param int $anselId
	 */
	public function delete($anselId)
	{
		// Get record query builder
		$anselRecord = $this->recordBuilder->get('ansel:Image');

		// Filter the record
		$anselRecord->filter('id', $anselId);

		// Get the record
		$anselRecord = $anselRecord->first();

		/** @var ImageRecord $anselRecord */

		// Make sure record exists
		if (! $anselRecord) {
			return;
		}

		// Set up the source router location type
		$this->sourceRouter->setSource(
			$anselRecord->getProperty('upload_location_type')
		);

		// Delete the high quality file
		$this->sourceRouter->deleteFile(
			$anselRecord->getProperty('upload_location_id'),
			$anselRecord->getBasename(),
			"{$anselRecord->getHighQualityDirectoryName()}/{$anselRecord->id}"
		);

		// Delete the thumbnail file
		$this->sourceRouter->deleteFile(
			$anselRecord->getProperty('upload_location_id'),
			$anselRecord->getBasename(),
			"{$anselRecord->getThumbDirectoryName()}/{$anselRecord->id}"
		);

		// Delete the standard file
		$this->sourceRouter->removeFile(
			$anselRecord->getProperty('file_id')
		);

		// Delete the ansel record
		$anselRecord->delete();
	}
}
