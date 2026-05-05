<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Add-ons
 * @category    Ansel
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/ansel
 * @license
 *
 *  Copyright (c) 2019. BoldMinded, LLC
 *  All rights reserved.
 *
 *  This source is commercial software. Use of this software requires a
 *  site license for each domain it is used on. Use of this software or any
 *  of its source code without express written permission in the form of
 *  a purchased commercial or other license is prohibited.
 *
 *  THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 *  KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 *  IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 *  PARTICULAR PURPOSE.
 *
 *  As part of the license agreement for this software, all modifications
 *  to this source must be submitted to the original author for review and
 *  possible inclusion in future releases. No compensation will be provided
 *  for patches, although where possible we will attribute each contribution
 *  in file revision notes. Submitting such modifications constitutes
 *  assignment of copyright to the original author (Brian Litzinger and
 *  BoldMinded, LLC) for such modifications. If you do not wish to assign
 *  copyright to the original author, your license to  use and modify this
 *  source is null and void. Use of this software constitutes your agreement
 *  to this clause.
 */

namespace BoldMinded\Ansel\Controller\Field;

use BoldMinded\Ansel\Model\FieldSettings as FieldSettingsModel;

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
        if (!is_array($fieldData)) {
            return true;
        }

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
            // Iterate through field data and make sure each row has a description
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

        // Check if description is required
        if ($this->fieldSettings->require_description) {
            // Iterate through field data and make sure each row has a description
            foreach ($fieldData as $data) {
                // Make sure description is set
                if (! isset($data['description']) || ! $data['description']) {
                    // Field data is not valid
                    $valid = false;

                    // Set the message
                    $message[] = str_replace(
                        '{{field}}',
                        $this->fieldSettings->description_label ?: lang('description'),
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
