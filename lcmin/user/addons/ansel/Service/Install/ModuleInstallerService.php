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
use ExpressionEngine\Model\Addon\Module as ModuleRecord;
use ExpressionEngine\Model\Addon\Action as ActionRecord;

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
    }
}
