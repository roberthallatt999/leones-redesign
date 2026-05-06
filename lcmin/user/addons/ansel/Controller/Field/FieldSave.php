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
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace BoldMinded\Ansel\Controller\Field;

use BoldMinded\Ansel\Service\AnselImages\SaveRow;
use BoldMinded\Ansel\Service\AnselImages\DeleteRow;
use BoldMinded\Ansel\Model\FieldSettings as FieldSettingsModel;

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
     * @param string $contentType
     * @param int $rowId
     * @param int $colId
     */
    public function save(
        $fieldData,
        $sourceId,
        $contentId,
        $contentType = 'channel', // If is numeric, it's likely a Grid rowId
        $rowId = null,
        $colId = null
    ): array {
        // Unset the placeholder
        if (isset($fieldData['placeholder'])) {
            unset($fieldData['placeholder']);
        }

        // If there is no field data, we can end
        if (! $fieldData) {
            return [];
        }

        $results = [];

        // Iterate through field data
        foreach ($fieldData as $data) {
            // Check if we are deleting or saving
            if (isset($data['ansel_image_delete']) &&
                $data['ansel_image_delete'] === 'true'
            ) {
                $this->deleteRowService->delete((int) $data['ansel_image_id'], $contentType);
            } else {
                if (!$sourceId && isset($data['source_id'])) {
                    $sourceId = $data['source_id'];
                }

                // Send the data to the save row service
                $results[] = $this->saveRowService->save(
                    $data,
                    $this->fieldSettings,
                    $sourceId,
                    $contentId,
                    $contentType,
                    $rowId,
                    $colId
                );
            }
        }

        return $results;
    }
}
