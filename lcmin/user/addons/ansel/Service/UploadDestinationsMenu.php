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

namespace BoldMinded\Ansel\Service;

use ExpressionEngine\Service\Model\Facade as RecordBuilder;

/**
 * Class UploadDestinationsMenu
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UploadDestinationsMenu
{
    /*
     * @var int $siteId
     */
    private $siteId;

    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

    /**
     * @var null|\Assets_lib $assetsLib
     */
    private $assetsLib;

    /**
     * Constructor
     *
     * @param int $siteId
     * @param RecordBuilder $recordBuilder
     * @param null|\Assets_lib $assetsLib
     * @throws \Exception
     */
    public function __construct(
        $siteId,
        RecordBuilder $recordBuilder,
        $assetsLib
    ) {
        // Make sure $assetsLib is null or instance of Assets_lib
        if ($assetsLib !== null && ! $assetsLib instanceof \Assets_lib) {
            $assetsThrowMsg = '$assetsLib must be null or an instance of ';
            $assetsThrowMsg .= 'Assets_lib';
            throw new \Exception($assetsThrowMsg);
        }

        // Inject dependencies
        $this->siteId = $siteId;
        $this->recordBuilder = $recordBuilder;
        $this->assetsLib = $assetsLib;
    }

    /**
     * Get upload destinations menu
     */
    public function getMenu()
    {
        // Create an array for upload destinations menu
        $uploadDestinationsMenu = array(
            '' => lang('choose_a_directory')
        );


        /**
         * EE Upload Directories
         */

        // Get EE upload destinations record builder
        $uploadDestinations = $this->recordBuilder->get('UploadDestination');

        // Only get upload directories for the current site
        $uploadDestinations->filter('site_id', $this->siteId);

        // Filter system directories out of records
        $uploadDestinations->filter('module_id', 0);

        // Order alphabetically
        $uploadDestinations->order('name', 'asc');

        // Get all records
        $uploadDestinations = $uploadDestinations->all();

        // Set EE dir lang
        $eeDirLang = lang('ee_directories');

        // Iterate over upload destinations and add to array
        foreach ($uploadDestinations as $destination) {
            $id = "ee:{$destination->id}";
            $uploadDestinationsMenu[$eeDirLang][$id] = $destination->name;
        }

        /**
         * Assets upload directories
         */

        if ($this->assetsLib) {
            // Set Assets dir lang
            $aDirLang = lang('assets_directories');

            // Get all Assets sources
            $assetsSources = $this->assetsLib->get_all_sources();

            // Iterate over sources and add to array
            foreach ($assetsSources as $assetsSource) {
                $id = "assets:{$assetsSource->type}-{$assetsSource->id}";
                $uploadDestinationsMenu[$aDirLang][$id] = $assetsSource->name;
            }
        }

        // Return the menu
        return $uploadDestinationsMenu;
    }
}
