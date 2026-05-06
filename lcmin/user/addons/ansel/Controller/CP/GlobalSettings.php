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

namespace BoldMinded\Ansel\Controller\CP;

use ExpressionEngine\Service\Alert\Alert;

/**
 * Class GlobalSettings
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class GlobalSettings extends BaseCP
{
    /**
     * @var array $excludedItems
     */
    private $excludedItems = array(
        'license_key',
        'phone_home',
        'check_for_updates',
        'updates_available',
        'update_feed',
        'encoding',
        'encoding_data'
    );

    /**
     * Get method for displaying global settings page
     *
     * @return array
     */
    public function get()
    {
        // We need to find the last key
        $lastKey = '';
        foreach ($this->globalSettings as $key => $settings) {
            if (in_array($key, $this->excludedItems)) {
                continue;
            }

            $lastKey = $key;
        }

        return [
            'heading' => lang('global_settings'),
            'body' => $this->viewFactory->make('ansel:CP/GlobalSettings')
                ->render([
                    'globalSettings' => $this->globalSettings,
                    'cpUrl' => $this->cpUrl,
                    'excludedItems' => $this->excludedItems,
                    'lastKey' => $lastKey
                ])
        ];
    }

    /**
     * Post method for saving global settings
     */
    public function post()
    {
        // Iterate through settings and assign values from post
        foreach ($this->globalSettings as $key => $val) {
            // Check if this is an excluded item
            if (in_array($key, $this->excludedItems)) {
                continue;
            }

            // Set the value of the property
            $this->globalSettings->{$key} = $this->request->post($key);
        }

        // Save the model
        $this->globalSettings->save();

        // Show the success message
        /** @var Alert $alert */
        $alert = $this->cpAlertService->makeInline('ansel-settings-updated');
        $alert->asSuccess();
        $alert->canClose();
        $alert->withTitle(lang('settings_updated'));
        $alert->addToBody(lang('settings_updated_success'));
        $alert->defer();

        // Redirect away and back to this page/controller
        $this->eeFunctions->redirect($this->cpUrl->getCurrentUrl());
    }
}
