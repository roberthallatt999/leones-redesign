<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;

/**
 * Class UploadKeys
 */
class UploadKeys
{
	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * @var string $siteUrl
	 */
	private $siteUrl;

	/**
	 * @var string $siteIndex
	 */
	private $siteIndex;

	/**
	 * UploadKeys constructor
	 *
	 * @param RecordBuilder $recordBuilder
	 * @param string $siteUrl
	 * @param string $siteIndex
	 */
	public function __construct(
		RecordBuilder $recordBuilder,
		$siteUrl,
		$siteIndex
	) {
		// Start a record query
		$expiredRecords = $recordBuilder->get('ansel:UploadKey');
		$expiredRecords->filter('expires', '<', time());

		// Delete expired records
		$expiredRecords->delete();

		// Inject dependencies
		$this->recordBuilder = $recordBuilder;
		$this->siteUrl = $siteUrl;
		$this->siteIndex = $siteIndex;
	}

	/**
	 * Create a new upload key
	 */
	public function createNew()
	{
		// Get new record
		$record = $this->recordBuilder->make('ansel:UploadKey');

		// Save record to the database
		$record->save();

		// Return the key
		return $record->key;
	}

	/**
	 * Validate a key
	 *
	 * @param string $key
	 * @return bool
	 */
	public function isValidKey($key)
	{
		// Filter the record to the appropriate key
		$record = $this->recordBuilder->get('ansel:UploadKey');
		$record->filter('key', $key);

		// Return true if count is greater than 0
		return $record->count() > 0;
	}

	/**
	 * Get upload URL
	 */
	public function getUploadUrl()
	{
		// Get the action record
		$actionRecord = $this->recordBuilder->get('Action');
		$actionRecord->filter('class', 'Ansel');
		$actionRecord->filter('method', 'imageUploader');
		$actionRecord = $actionRecord->first();

		// Set the URL
		$url = rtrim($this->siteUrl, '/') . '/' . $this->siteIndex;
		$url = "{$url}?ACT={$actionRecord->action_id}";

		// Return the URL
		return $url;
	}
}
