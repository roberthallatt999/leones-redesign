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

namespace BoldMinded\Ansel\Service\Install;

use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use ExpressionEngine\Service\Model\Query\Builder as QueryBuilder;
use ExpressionEngine\Model\Addon\Fieldtype as FieldTypeRecord;

/**
 * Class FieldTypeUpdaterService
 */
class FieldTypeUpdaterService
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
     * Update the field type
     */
    public function update()
    {
        // Get the FieldType record
        /** @var QueryBuilder $moduleRecord */
        $fieldTypeRecord = $this->recordBuilder->get('Fieldtype');
        $fieldTypeRecord->filter('name', 'ansel');
        $fieldTypeRecord = $fieldTypeRecord->first();

        /** @var FieldTypeRecord $fieldTypeRecord */

        // Make sure we got the field type record
        if (! $fieldTypeRecord) {
            return;
        }

        // Update the version on the field type record
        $fieldTypeRecord->setProperty('version', $this->addonVersion);

        // Save the record
        $fieldTypeRecord->save();
    }
}
