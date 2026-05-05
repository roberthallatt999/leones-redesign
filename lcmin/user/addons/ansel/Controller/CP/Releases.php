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

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Version;

/**
 * Class Updates
 */
class Releases extends BaseCP
{
    /**
     * Get method for displaying updates
     *
     * @return array
     */
    public function get()
    {
        $version = new Version();
        $allVersions = $version->setAddon('ansel')->fetchAll();

        $releases = [];

        foreach ($allVersions as $version) {
            $releases[] = [
                'date' => $version->dateFormatted,
                'version' => $version->version,
                'notes' => html_entity_decode($version->notes),
                'isNew' => version_compare($version->version, ANSEL_VERSION, '>'),
                'currentVersion' => ANSEL_VERSION,
            ];
        }

        $vars['releases'] = $releases;

        $vars['message'] = ee('CP/Alert')->makeInline('ansel-releases')
            ->asAttention()
            ->cannotClose()
            ->withTitle('Stay up-to-date!')
            ->addToBody('The latest version of Ansel can be downloaded from your <a href="https://boldminded.com/account/licenses">BoldMinded account</a> or <a href="https://expressionengine.com/store/licenses-add-ons">ExpressionEngine.com</a>')
            ->render();

        return [
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/ansel')->compile() => 'Ansel',
                ee('CP/URL', 'addons/settings/ansel/releases')->compile() => 'Release Notes',
            ],
            'body' => ee('View')->make('ansel:CP/releases')->render($vars)
        ];
    }
}
