<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

use BuzzingPixel\Ansel\Service\Install\RecordInstallerService;
use BuzzingPixel\Ansel\Service\Install\ModuleInstallerService;
use BuzzingPixel\Ansel\Service\Install\FieldTypeUpdaterService;
use BuzzingPixel\Ansel\Service\Install\UpdateTo2_0_0\Images as Images200UpdaterService;

// Legacy updaters
use BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_3_0\FieldSettings as Legacy130FieldSettingsUpdater;
use BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_3_0\Images as Legacy130ImagesUpdater;
use BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_3_0\Settings as Legacy130SettingsUpdater;
use BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_4_0\Images as Legacy140ImagesUpdater;

/**
 * Class Ansel_upd
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
// @codingStandardsIgnoreStart
class Ansel_upd // @codingStandardsIgnoreEnd
{
	/**
	 * Install
	 *
	 * @return bool
	 */
	public function install()
	{
		// Install records
		/** @var RecordInstallerService $recordInstallerService */
		$recordInstallerService = ee('ansel:RecordInstallerService');
		$recordInstallerService->installUpdate();

		// Install module
		/** @var ModuleInstallerService $moduleInstallerService */
		$moduleInstallerService = ee('ansel:ModuleInstallerService');
		$moduleInstallerService->installUpdate();

		// All done
		return true;
	}

	/**
	 * Uninstall
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		// Remove records
		/** @var RecordInstallerService $recordInstallerService */
		$recordInstallerService = ee('ansel:RecordInstallerService');
		$recordInstallerService->remove();

		// Remove module
		/** @var ModuleInstallerService $moduleInstallerService */
		$moduleInstallerService = ee('ansel:ModuleInstallerService');
		$moduleInstallerService->remove();

		// All done
		return true;
	}

	/**
	 * Update
	 *
	 * @param string $current The current version before update
	 * @return bool
	 */
	public function update($current = '')
	{
		// Get addon info
		/* @var \EllisLab\ExpressionEngine\Core\Provider $addonInfo */
		$addonInfo = ee('Addon')->get('ansel');

		// Check if updating is needed
		if ($current === $addonInfo->get('version')) {
			return false;
		}


		/**
		 * LEGACY UPDATES
		 */

		// Less than 1.3.0
		if (version_compare($current, '1.3.0', '<')) {
			// Run field settings updater
			$legacy130FieldSettingsUpdater = new Legacy130FieldSettingsUpdater();
			$legacy130FieldSettingsUpdater->process();

			// Run images table updater
			$legacy130ImagesUpdater = new Legacy130ImagesUpdater();
			$legacy130ImagesUpdater->process();

			// Run settings updater
			$legacy130SettingsUpdater = new Legacy130SettingsUpdater();
			$legacy130SettingsUpdater->process();
		}

		// Less than 1.4.0
		if (version_compare($current, '1.4.0', '<')) {
			// Run images table updater
			$legacy140ImagesUpdater = new Legacy140ImagesUpdater();
			$legacy140ImagesUpdater->process();
		}


		/**
		 * Version updates
		 */

		// Less than 2.0.0 (or 2.0.0-b.1)
		if (version_compare($current, '2.0.0', '<') ||
			$current === '2.0.0-b.1'
		) {
			// Run images table updater
			/** @var Images200UpdaterService $images200UpdaterService */
			$images200UpdaterService = ee('ansel:Images200UpdaterService');
			$images200UpdaterService->process();
		}


		/**
		 * Standard updates
		 */

		// Update field type
		/** @var FieldTypeUpdaterService $fieldTypeUpdaterService */
		$fieldTypeUpdaterService = ee('ansel:FieldTypeUpdaterService');
		$fieldTypeUpdaterService->update();

		// Update records
		/** @var RecordInstallerService $recordInstallerService */
		$recordInstallerService = ee('ansel:RecordInstallerService');
		$recordInstallerService->installUpdate();

		// Update module
		/** @var ModuleInstallerService $moduleInstallerService */
		$moduleInstallerService = ee('ansel:ModuleInstallerService');
		$moduleInstallerService->installUpdate();

		// All done
		return true;
	}
}
