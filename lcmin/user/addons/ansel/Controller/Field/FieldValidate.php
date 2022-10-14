<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\Field;

use BuzzingPixel\Ansel\Model\FieldSettings as FieldSettingsModel;

/**
 * Class FieldValidate
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class FieldValidate
{
	/**
	 * @var FieldSettingsModel $fieldSettings
	 */
	protected $fieldSettings;

	/**
	 * Constructor
	 *
	 * @param FieldSettingsModel $fieldSettings
	 * @param array $rawFieldSettings
	 */
	public function __construct(
		FieldSettingsModel $fieldSettings,
		$rawFieldSettings = array()
	) {
		// Populate the model
		$fieldSettings->set($rawFieldSettings);

		// Inject dependencies
		$this->fieldSettings = $fieldSettings;
	}

	/**
	 * Validate field data
	 *
	 * @param array $fieldData
	 * @return array|bool
	 */
	public function validate($fieldData)
	{
		// Unset the placeholder
		unset($fieldData['placeholder']);

		// Start by assuming $valid is true, we'll do things to find out
		$valid = true;

		// We might need to send back messages
		$message = array();

		// Go through each row in data and check if marked for deletion
		foreach ($fieldData as $key => $data) {
			if (isset($data['ansel_image_delete']) &&
				$data['ansel_image_delete'] === 'true'
			) {
				unset($fieldData[$key]);
			}
		}

		// Get the row count
		$rowCount = count($fieldData);

		// Make sure we're over the min quantity
		if ($rowCount < $this->fieldSettings->min_qty) {
			// Field is not valid
			$valid = false;

			// Set message
			if ($this->fieldSettings->min_qty === 1) {
				// We'll get the single image language
				$message[] = lang('field_requires_at_least_1_image');
			} else {
				// Get multiple image language and replace the amount
				$message[] = str_replace(
					'{{amount}}',
					$this->fieldSettings->min_qty,
					lang('field_requires_at_least_x_images')
				);
			}
		}

		// Check if the title is required
		if ($this->fieldSettings->require_title) {
			// Iterate through field data and make sure each row has a caption
			foreach ($fieldData as $data) {
				// Make sure title is set
				if (! isset($data['title']) || ! $data['title']) {
					// Field data is not valid
					$valid = false;

					// Set the message
					$message[] = str_replace(
						'{{field}}',
						$this->fieldSettings->title_label ?: lang('title'),
						lang('x_field_required_for_each_image')
					);

					// No need to go on
					break;
				}
			}
		}

		// Check if caption is required
		if ($this->fieldSettings->require_caption) {
			// Iterate through field data and make sure each row has a caption
			foreach ($fieldData as $data) {
				// Make sure caption is set
				if (! isset($data['caption']) || ! $data['caption']) {
					// Field data is not valid
					$valid = false;

					// Set the message
					$message[] = str_replace(
						'{{field}}',
						$this->fieldSettings->caption_label ?: lang('caption'),
						lang('x_field_required_for_each_image')
					);

					// No need to go on
					break;
				}
			}
		}

		// Check if cover is required
		if ($this->fieldSettings->require_cover && count($fieldData)) {
			// Start by assuming the cover is not set
			$coverIsSet = false;

			// Iterate through field data to find out if cover is set
			foreach ($fieldData as $data) {
				// Check if cover is set
				if (isset($data['cover']) && $data['cover'] == true) {
					// The cover is set
					$coverIsSet = true;

					// No need to continue the loop
					break;
				}
			}

			// Check if the cover got set
			if (! $coverIsSet) {
				// Uh oh, this field is not valid
				$valid = false;

				// Set the message
				$message[] = str_replace(
					'{{field}}',
					$this->fieldSettings->cover_label ?: lang('cover'),
					lang('field_requires_cover')
				);
			}
		}

		// If not valid, return the array
		if (! $valid) {
			return implode('<br>', $message);
		}

		// Otherwise, here we are, return true (validated)
		return true;
	}
}
