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

namespace BoldMinded\Ansel\Service\Install\UpdateTo2_0_0;

use CI_DB_mysqli_forge as DBForge;

/**
 * Class Images
 */
class Images
{
    /**
     * @var DBForge $dbForge
     */
    private $dbForge;

    /**
     * Constructor
     *
     * @param DBForge $dbForge
     */
    public function __construct(DBForge $dbForge)
    {
        $this->dbForge = $dbForge;
    }

    /**
     * Process Images changes
     */
    public function process()
    {
        // Modify the row_id column to have a default of 0
        $this->dbForge->modify_column('ansel_images', array(
            'row_id' => array(
                'name' => 'row_id',
                'null' => false,
                'default' => 0,
                'type' => 'INT',
                'unsigned' => true
            ),
        ));

        // Modify the col_id column to have a default of 0
        $this->dbForge->modify_column('ansel_images', array(
            'col_id' => array(
                'name' => 'col_id',
                'null' => false,
                'default' => 0,
                'type' => 'INT',
                'unsigned' => true
            ),
        ));
    }
}
