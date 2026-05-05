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
 * Class Images
 */
class Images
{
    /**
     * Process the update
     */
    public function process()
    {
        // Load the forge class
        ee()->load->dbforge();

        // Modify the upload_location_id column to accept strings
        ee()->dbforge->modify_column('ansel_images', array(
            'upload_location_id' => array(
                'name' => 'upload_location_id',
                'default' => '',
                'type' => 'VARCHAR',
                'constraint' => 255
            ),
        ));

        // Add the upload_location_type column
        if (! ee()->db->field_exists('upload_location_type', 'ansel_images')) {
            ee()->dbforge->add_column('ansel_images', array(
                'upload_location_type' => array(
                    'default' => 'ee',
                    'type' => 'VARCHAR',
                    'constraint' => 10
                ),
            ));
        }

        // Add the upload_location_type column
        if (! ee()->db->field_exists('original_location_type', 'ansel_images')) {
            ee()->dbforge->add_column('ansel_images', array(
                'original_location_type' => array(
                    'default' => 'ee',
                    'type' => 'VARCHAR',
                    'constraint' => 10
                ),
            ));
        }
    }
}
