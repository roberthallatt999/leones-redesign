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

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Updater;
use BoldMinded\Ansel\Service\Install\RecordInstallerService;
use BoldMinded\Ansel\Service\Install\ModuleInstallerService;
use BoldMinded\Ansel\Service\Install\FieldTypeUpdaterService;
use BoldMinded\Ansel\Service\Install\UpdateTo2_0_0\Images as Images200UpdaterService;

// Legacy updaters
use BoldMinded\Ansel\Service\Legacy\UpdateTo1_3_0\FieldSettings as Legacy130FieldSettingsUpdater;
use BoldMinded\Ansel\Service\Legacy\UpdateTo1_3_0\Images as Legacy130ImagesUpdater;
use BoldMinded\Ansel\Service\Legacy\UpdateTo1_3_0\Settings as Legacy130SettingsUpdater;
use BoldMinded\Ansel\Service\Legacy\UpdateTo1_4_0\Images as Legacy140ImagesUpdater;

/**
 * Class Ansel_upd
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
// @codingStandardsIgnoreStart
class Ansel_upd // @codingStandardsIgnoreEnd
{
    private $hookTemplate = [
        'class' => 'Ansel_ext',
        'settings' => '',
        'priority' => 5,
        'version' => ANSEL_VERSION,
        'enabled' => 'y',
    ];

    /**
     * @var string
     */
    public $version = ANSEL_VERSION;

    /**
     * Install
     *
     * @return bool
     */
    public function install()
    {
        // Install records
        /** @var RecordInstallerService $recordInstallerService */
        $recordInstallerService = ee('ansel:RecordInstallerService');
        $recordInstallerService->installUpdate();

        // Install module
        /** @var ModuleInstallerService $moduleInstallerService */
        $moduleInstallerService = ee('ansel:ModuleInstallerService');
        $moduleInstallerService->installUpdate();

        $updater = new Updater();
        $updater
            ->setFilePath(PATH_THIRD.'ansel/updates')
            ->setHookTemplate([
                'class' => ANSEL_EXT,
                'settings' => '',
                'priority' => 5,
                'version' => ANSEL_VERSION,
                'enabled' => 'y',
            ])
            ->fetchUpdates(0, true)
            ->runUpdates();

        return true;
    }

    /**
     * Uninstall
     *
     * @return bool
     */
    public function uninstall()
    {
        // Remove records
        /** @var RecordInstallerService $recordInstallerService */
        $recordInstallerService = ee('ansel:RecordInstallerService');
        $recordInstallerService->remove();

        // Remove module
        /** @var ModuleInstallerService $moduleInstallerService */
        $moduleInstallerService = ee('ansel:ModuleInstallerService');
        $moduleInstallerService->remove();

        ee()->db->select('module_id')
            ->get_where('modules', [
                'module_name' => ANSEL_NAME_SHORT
            ])->row('module_id');

        ee()->db->where('module_name', ANSEL_NAME_SHORT)
            ->delete('modules');

        ee()->db->where('class', ANSEL_NAME_SHORT)
            ->delete('actions');

        ee()->db->where('class', ANSEL_EXT)
            ->delete('extensions');

        return true;
    }

    /**
     * Update
     *
     * @param string $current The current version before update
     * @return bool
     */
    public function update($current = '')
    {
        // Get addon info
        /* @var \ExpressionEngine\Core\Provider $addonInfo */
        $addonInfo = ee('Addon')->get('ansel');

        // Check if updating is needed
        if ($current === $addonInfo->get('version')) {
            return false;
        }


        /**
         * LEGACY UPDATES
         */

        // Less than 1.3.0
        if (version_compare($current, '1.3.0', '<')) {
            // Run field settings updater
            $legacy130FieldSettingsUpdater = new Legacy130FieldSettingsUpdater();
            $legacy130FieldSettingsUpdater->process();

            // Run images table updater
            $legacy130ImagesUpdater = new Legacy130ImagesUpdater();
            $legacy130ImagesUpdater->process();

            // Run settings updater
            $legacy130SettingsUpdater = new Legacy130SettingsUpdater();
            $legacy130SettingsUpdater->process();
        }

        // Less than 1.4.0
        if (version_compare($current, '1.4.0', '<')) {
            // Run images table updater
            $legacy140ImagesUpdater = new Legacy140ImagesUpdater();
            $legacy140ImagesUpdater->process();
        }


        /**
         * Version updates
         */

        // Less than 2.0.0 (or 2.0.0-b.1)
        if (version_compare($current, '2.0.0', '<') ||
            $current === '2.0.0-b.1'
        ) {
            // Run images table updater
            /** @var Images200UpdaterService $images200UpdaterService */
            $images200UpdaterService = ee('ansel:Images200UpdaterService');
            $images200UpdaterService->process();
        }

        $updater = new Updater();

        try {
            $updater
                ->setFilePath(PATH_THIRD . 'ansel/updates')
                ->setHookTemplate($this->hookTemplate)
                ->fetchUpdates($current)
                ->runUpdates();

            $this->updateVersion();

        } catch (\Exception $exception) {
            show_error($exception->getMessage());
        }

        // Update field type
        /** @var FieldTypeUpdaterService $fieldTypeUpdaterService */
        $fieldTypeUpdaterService = ee('ansel:FieldTypeUpdaterService');
        $fieldTypeUpdaterService->update();

        // Update records
        /** @var RecordInstallerService $recordInstallerService */
        $recordInstallerService = ee('ansel:RecordInstallerService');
        $recordInstallerService->installUpdate();

        // Update module
        /** @var ModuleInstallerService $moduleInstallerService */
        $moduleInstallerService = ee('ansel:ModuleInstallerService');
        $moduleInstallerService->installUpdate();

        return true;
    }

    private function updateVersion()
    {
        ee()->db->update('modules', ['module_version' => $this->version], ['module_name' => 'Ansel']);
    }
}
