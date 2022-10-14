<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\Field;

use BuzzingPixel\Ansel\Service\AnselImages\SaveRow;
use BuzzingPixel\Ansel\Service\AnselImages\DeleteRow;
use BuzzingPixel\Ansel\Model\FieldSettings as FieldSettingsModel;

/**
 * Class FieldSave
 */
class FieldSave
{
	/**
	 * @var SaveRow $saveRowService
	 */
	private $saveRowService;

	/**
	 * @var DeleteRow $deleteRowService
	 */
	private $deleteRowService;

	/**
	 * @var FieldSettingsModel $fieldSettings
	 */
	protected $fieldSettings;

	/**
	 * Constructor
	 *
	 * @param SaveRow $saveRowService
	 * @param DeleteRow $deleteRowService
	 * @param FieldSettingsModel $fieldSettings
	 * @param array $rawFieldSettings
	 */
	public function __construct(
		SaveRow $saveRowService,
		DeleteRow $deleteRowService,
		FieldSettingsModel $fieldSettings,
		$rawFieldSettings
	) {
		// Populate the model
		$fieldSettings->set($rawFieldSettings);

		// Inject dependencies
		$this->saveRowService = $saveRowService;
		$this->deleteRowService = $deleteRowService;
		$this->fieldSettings = $fieldSettings;
	}

	/**
	 * Save field data
	 *
	 * @param array $fieldData
	 * @param int $sourceId
	 * @param int $contentId
	 * @param int $rowId
	 * @param int $colId
	 */
	public function save(
		$fieldData,
		$sourceId,
		$contentId,
		$rowId = null,
		$colId = null
	) {
		// Unset the placeholder
		if (isset($fieldData['placeholder'])) {
			unset($fieldData['placeholder']);
		}

		// If there is no field data, we can end
		if (! $fieldData) {
			return;
		}

		// Iterate through field data
		foreach ($fieldData as $data) {
			// Check if we are deleting or saving
			if (isset($data['ansel_image_delete']) &&
				$data['ansel_image_delete'] === 'true'
			) {
				$this->deleteRowService->delete((int) $data['ansel_image_id']);
			} else {
				// Send the data to the save row service
				$this->saveRowService->save(
					$data,
					$this->fieldSettings,
					$sourceId,
					$contentId,
					$rowId,
					$colId
				);
			}
		}
	}
}
