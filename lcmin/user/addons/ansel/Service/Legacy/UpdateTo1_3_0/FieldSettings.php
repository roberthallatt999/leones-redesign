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

namespace BoldMinded\Ansel\Service\Legacy\UpdateTo1_3_0;

/**
 * Class FieldSettings
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class FieldSettings
{
    /**
     * Process update
     */
    public function process()
    {
        /**
         * Update field types
         */

        // Get ansel field types
        $fields = ee()->db->select('field_id, field_settings')
            ->from('channel_fields')
            ->where('field_type', 'ansel')
            ->get()
            ->result();

        // Loop through fields
        foreach ($fields as $key => $field) {
            // Decode the settings
            $settings = unserialize(base64_decode($field->field_settings));

            // Update upload directory preference
            if (is_numeric($settings['upload_directory'])) {
                $settings['upload_directory'] = "ee:{$settings['upload_directory']}";
            }

            // Update save directory preference
            if (is_numeric($settings['save_directory'])) {
                $settings['save_directory'] = "ee:{$settings['save_directory']}";
            }

            // Resave the setttings
            $fields[$key]->field_settings = base64_encode(serialize($settings));
        }

        // Save the fields
        if ($fields) {
            ee()->db->update_batch('channel_fields', $fields, 'field_id');
        }


        /**
         * Update grid fields
         */

        // Get ansel grid columns
        $gridFields = ee()->db->select('col_id, col_settings')
            ->from('grid_columns')
            ->where('col_type', 'ansel')
            ->get()
            ->result();

        // Loop through grid columns
        foreach ($gridFields as $key => $field) {
            // Decode the settings
            $settings = json_decode($field->col_settings);

            // Update upload directory preference
            if (is_numeric($settings->upload_directory)) {
                $settings->upload_directory = "ee:{$settings->upload_directory}";
            }

            // Update save directory preference
            if (is_numeric($settings->save_directory)) {
                $settings->save_directory = "ee:{$settings->save_directory}";
            }

            // Resave the setttings
            $gridFields[$key]->col_settings = json_encode($settings);
        }

        // Save the columns
        if ($gridFields) {
            ee()->db->update_batch('grid_columns', $gridFields, 'col_id');
        }


        /**
         * Update blocks fields
         */

        // Get ansel block types
        if (ee()->db->table_exists('blocks_atomdefinition')) {
            $blocks = ee()->db->select('id, settings')
                ->from('blocks_atomdefinition')
                ->where('type', 'ansel')
                ->get()
                ->result();
        } else {
            $blocks = array();
        }

        foreach ($blocks as $key => $field) {
            // Decode the settings
            $settings = json_decode($field->settings);

            // Update upload directory preference
            if (is_numeric($settings->upload_directory)) {
                $settings->upload_directory = "ee:{$settings->upload_directory}";
            }

            // Update save directory preference
            if (is_numeric($settings->save_directory)) {
                $settings->save_directory = "ee:{$settings->save_directory}";
            }

            // Re-save the settings
            $blocks[$key]->settings = json_encode($settings);
        }

        // Save the blocks
        if ($blocks) {
            ee()->db->update_batch('blocks_atomdefinition', $blocks, 'id');
        }


        /**
         * Update Low Variables
         */

        if (ee()->db->table_exists('low_variables')) {
            // Get ansel low variable types
            $lowVarsFields = ee()->db->select('variable_id, variable_settings')
                ->from('low_variables')
                ->where('variable_type', 'ansel')
                ->get()
                ->result();
        } else {
            $lowVarsFields = array();
        }

        // Loop through low variables
        foreach ($lowVarsFields as $key => $field) {
            // Decode the settings
            $settings = json_decode($field->variable_settings);

            // Update upload directory preference
            if (is_numeric($settings->upload_directory)) {
                $settings->upload_directory = "ee:{$settings->upload_directory}";
            }

            // Update save directory preference
            if (is_numeric($settings->save_directory)) {
                $settings->save_directory = "ee:{$settings->save_directory}";
            }

            // Resave the setttings
            $lowVarsFields[$key]->variable_settings = json_encode($settings);
        }

        // Save the fields
        if ($lowVarsFields) {
            ee()->db->update_batch('low_variables', $lowVarsFields, 'variable_id');
        }
    }
}
