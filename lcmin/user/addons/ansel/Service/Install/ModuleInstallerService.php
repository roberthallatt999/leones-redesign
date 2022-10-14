<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Install;

use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as QueryBuilder;
use EllisLab\ExpressionEngine\Model\Addon\Module as ModuleRecord;
use EllisLab\ExpressionEngine\Model\Addon\Action as ActionRecord;

/**
 * Class ModuleInstallerService
 */
class ModuleInstallerService
{
	/**
	 * @var string $addonVersion
	 */
	private $addonVersion;

	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * Constructor
	 *
	 * @param string $addonVersion
	 * @param RecordBuilder $recordBuilder
	 */
	public function __construct($addonVersion, RecordBuilder $recordBuilder)
	{
		$this->addonVersion = $addonVersion;
		$this->recordBuilder = $recordBuilder;
	}

	/**
	 * Add module record
	 */
	public function installUpdate()
	{
		/**
		 * Base module record
		 */

		// Get the module record
		/** @var QueryBuilder $moduleRecord */
		$moduleRecord = $this->recordBuilder->get('Module');
		$moduleRecord->filter('module_name', 'Ansel');
		$moduleRecord = $moduleRecord->first();

		// If no module record, make one
		if (! $moduleRecord) {
			$moduleRecord = $this->recordBuilder->make('Module');
		}

		/** @var ModuleRecord $moduleRecord */

		// Set module properties
		$moduleRecord->setProperty('module_name', 'Ansel');
		$moduleRecord->setProperty('module_version', $this->addonVersion);
		$moduleRecord->setProperty('has_cp_backend', 'y');
		$moduleRecord->setProperty('has_publish_fields', 'n');

		// Now save the module record
		$moduleRecord->save();


		/**
		 * License ping action record
		 */

		// Get the action record
		$pingActionRecord = $this->recordBuilder->get('Action');
		$pingActionRecord->filter('class', 'Ansel');
		$pingActionRecord->filter('method', 'licensePing');
		$pingActionRecord = $pingActionRecord->first();

		// If no action record, make one
		if (! $pingActionRecord) {
			$pingActionRecord = $this->recordBuilder->make('Action');
		}

		// Set action properties
		$pingActionRecord->setProperty('class', 'Ansel');
		$pingActionRecord->setProperty('method', 'licensePing');
		$pingActionRecord->setProperty('csrf_exempt', true);

		// Save the record
		$pingActionRecord->save();


		/**
		 * Image uploader action record
		 */

		// Get the action record
		$uploaderActionRecord = $this->recordBuilder->get('Action');
		$uploaderActionRecord->filter('class', 'Ansel');
		$uploaderActionRecord->filter('method', 'imageUploader');
		$uploaderActionRecord = $uploaderActionRecord->first();

		// If no action record, make one
		if (! $uploaderActionRecord) {
			$uploaderActionRecord = $this->recordBuilder->make('Action');
		}

		// Set action properties
		$uploaderActionRecord->setProperty('class', 'Ansel');
		$uploaderActionRecord->setProperty('method', 'imageUploader');
		$uploaderActionRecord->setProperty('csrf_exempt', true);

		// Save the record
		$uploaderActionRecord->save();
	}

	/**
	 * Remove module record
	 */
	public function remove()
	{
		/**
		 * Base module record
		 */

		/** @var QueryBuilder $moduleRecord */
		$moduleRecord = $this->recordBuilder->get('Module');
		$moduleRecord->filter('module_name', 'Ansel');
		$moduleRecord = $moduleRecord->first();

		/** @var ModuleRecord $moduleRecord */

		// If module record, delete it
		if ($moduleRecord) {
			$moduleRecord->delete();
		}


		/**
		 * Action record
		 */
		$actionRecord = $this->recordBuilder->get('Action');
		$actionRecord->filter('class', 'Ansel');
		$actionRecord->filter('method', 'licensePing');
		$actionRecord = $actionRecord->first();

		/** @var ActionRecord $actionRecord */

		// If action record, delete it
		if ($actionRecord) {
			$actionRecord->delete();
		}
	}
}
