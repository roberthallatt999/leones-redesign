<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use BuzzingPixel\Ansel\Service\GlobalSettings;
use BuzzingPixel\Ansel\Model\UpdateFeedItem;
use EllisLab\ExpressionEngine\Library\Data\Collection;

/**
 * Class UpdatesFeed
 */
class UpdatesFeed
{
	/**
	 * @var string $addonVersion
	 */
	private $addonVersion;

	/**
	 * @var GlobalSettings $globalSettings
	 */
	private $globalSettings;

	/**
	 * @var UpdateFeedItem $updateFeedItem
	 */
	private $updateFeedItem;

	/**
	 * @var Collection $collection
	 */
	private $collection;

	/**
	 * Updates feed constructor
	 *
	 * @param string $addonVersion
	 * @param GlobalSettings $globalSettings
	 * @param UpdateFeedItem $updateFeedItem
	 * @param Collection $collection
	 */
	public function __construct(
		$addonVersion,
		GlobalSettings $globalSettings,
		UpdateFeedItem $updateFeedItem,
		Collection $collection
	) {
		// Inject dependencies
		$this->addonVersion = $addonVersion;
		$this->globalSettings = $globalSettings;
		$this->updateFeedItem = $updateFeedItem;
		$this->collection = $collection;
	}

	/**
	 * Get the updates feed
	 *
	 * @param bool $bypassCache
	 * @return Collection
	 */
	public function get($bypassCache = false)
	{
		// Check if we should get an updated feed
		if ($this->globalSettings->check_for_updates < time() || $bypassCache) {
			// Set options
			$options = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
				)
			);

			// Set context
			$context = stream_context_create($options);

			// Get the feed
			$feed = file_get_contents(
				'https://buzzingpixel.com/software/ansel-ee/changelog/feed',
				false,
				$context
			);

			// Parse feed into json
			$json = json_decode($feed, true) ?: array();

			// Start a running variable for updates available
			$updatesAvailable = 0;

			// Loop through the feed to find updates
			foreach ($json as $key => $update) {
				$version = $update['version'];
				if (version_compare($version, $this->addonVersion, '>')) {
					$json[$key]['new'] = true;
					$updatesAvailable++;
				} else {
					$json[$key]['new'] = false;
				}
			}

			// Save json to settings
			$this->globalSettings->update_feed = json_encode($json);

			// Set the number of updates available
			$this->globalSettings->updates_available = $updatesAvailable;

			// Increment the check timer
			$this->globalSettings->check_for_updates = strtotime(
				'+1 day',
				time()
			);

			// Save settings
			$this->globalSettings->save();
		} else {
			// Get existing json
			$json = json_decode($this->globalSettings->update_feed, true);
		}

		// Create an array to temporarily store items for filling collection
		$tempItems = array();

		// Iterate through Json
		foreach ($json as $item) {
			// Get a new instance of model
			$model = clone $this->updateFeedItem;

			// Fill the model
			foreach ($item as $key => $val) {
				$model->{$key} = $val;
			}

			// Add the model to the temp array
			$tempItems[] = $model;
		}

		// Create a new collection
		$collection = clone $this->collection;

		// Add the elements (hacking because collections don't have an official
		// way to set items after construction)
		$collection->__construct($tempItems);

		// Return a collection
		return $collection;
	}

	/**
	 * Get number of updates
	 */
	public function getNumber()
	{
		// Run the get method to make sure number of updates is up to date
		$this->get();

		return $this->globalSettings->updates_available;
	}
}
