<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BuzzingPixel\Treasury\API\Locations as TreasuryLocationsAPI;

/**
 * Class UploadDestinationsMenu
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UploadDestinationsMenu
{
	/*
	 * @var int $siteId
	 */
	private $siteId;

	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * @var null|TreasuryLocationsAPI $treasuryLocationsAPI
	 */
	private $treasuryLocationsAPI;

	/**
	 * @var null|\Assets_lib $assetsLib
	 */
	private $assetsLib;

	/**
	 * Constructor
	 *
	 * @param int $siteId
	 * @param RecordBuilder $recordBuilder
	 * @param null|TreasuryLocationsAPI $treasuryLocationsAPI
	 * @param null|\Assets_lib $assetsLib
	 * @throws \Exception
	 */
	public function __construct(
		$siteId,
		RecordBuilder $recordBuilder,
		$treasuryLocationsAPI,
		$assetsLib
	) {
		// Make sure $treasury is null or instance of Addon
		if ($treasuryLocationsAPI !== null &&
			! $treasuryLocationsAPI instanceof TreasuryLocationsAPI
		) {
			$treasuryThrowMsg = '$treasury must be null or an instance of ';
			$treasuryThrowMsg .= 'BuzzingPixel\Treasury\API\Locations';
			throw new \Exception($treasuryThrowMsg);
		}

		// Make sure $assetsLib is null or instance of Assets_lib
		if ($assetsLib !== null && ! $assetsLib instanceof \Assets_lib) {
			$assetsThrowMsg = '$assetsLib must be null or an instance of ';
			$assetsThrowMsg .= 'Assets_lib';
			throw new \Exception($assetsThrowMsg);
		}

		// Inject dependencies
		$this->siteId = $siteId;
		$this->recordBuilder = $recordBuilder;
		$this->treasuryLocationsAPI = $treasuryLocationsAPI;
		$this->assetsLib = $assetsLib;
	}

	/**
	 * Get upload destinations menu
	 */
	public function getMenu()
	{
		// Create an array for upload destinations menu
		$uploadDestinationsMenu = array(
			'' => lang('choose_a_directory')
		);


		/**
		 * EE Upload Directories
		 */

		// Get EE upload destinations record builder
		$uploadDestinations = $this->recordBuilder->get('UploadDestination');

		// Only get upload directories for the current site
		$uploadDestinations->filter('site_id', $this->siteId);

		// Filter system directories out of records
		$uploadDestinations->filter('module_id', 0);

		// Order alphabetically
		$uploadDestinations->order('name', 'asc');

		// Get all records
		$uploadDestinations = $uploadDestinations->all();

		// Set EE dir lang
		$eeDirLang = lang('ee_directories');

		// Iterate over upload destinations and add to array
		foreach ($uploadDestinations as $destination) {
			$id = "ee:{$destination->id}";
			$uploadDestinationsMenu[$eeDirLang][$id] = $destination->name;
		}


		/**
		 * Treasury upload directories
		 */

		if ($this->treasuryLocationsAPI) {
			// Set Treasury dir lang
			$tDirLang = lang('treasury_directories');

			// Get all treasury locations
			$treasuryLocations = $this->treasuryLocationsAPI->getAllLocations();

			// Iterate over locations and add to array
			foreach ($treasuryLocations as $location) {
				$id = "treasury:{$location->handle}";
				$uploadDestinationsMenu[$tDirLang][$id] = $location->name;
			}
		}


		/**
		 * Assets upload directories
		 */

		if ($this->assetsLib) {
			// Set Assets dir lang
			$aDirLang = lang('assets_directories');

			// Get all Assets sources
			$assetsSources = $this->assetsLib->get_all_sources();

			// Iterate over sources and add to array
			foreach ($assetsSources as $assetsSource) {
				$id = "assets:{$assetsSource->type}-{$assetsSource->id}";
				$uploadDestinationsMenu[$aDirLang][$id] = $assetsSource->name;
			}
		}

		// Return the menu
		return $uploadDestinationsMenu;
	}
}
