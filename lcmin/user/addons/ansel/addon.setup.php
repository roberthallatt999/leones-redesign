<?php
// Build: 3282ccc2

// @codingStandardsIgnoreStart
// @codingStandardsIgnoreEnd

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

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Logger;
use BoldMinded\Ansel\Service\LivePreviewFactory;
use BoldMinded\Ansel\Service\UploadKeys;
use BoldMinded\Ansel\Service\Install\RecordInstallerService;
use BoldMinded\Ansel\Service\Install\ModuleInstallerService;
use BoldMinded\Ansel\Service\Install\FieldTypeUpdaterService;
use BoldMinded\Ansel\Service\GlobalSettings;
use BoldMinded\Ansel\Model\UpdateFeedItem;
use BoldMinded\Ansel\Controller\Field\FieldSettings;
use BoldMinded\Ansel\Model\FieldSettings as FieldSettingsModel;
use BoldMinded\Ansel\Service\UploadDestinationsMenu;
use BoldMinded\Ansel\Controller\Field\FieldDisplay;
use BoldMinded\Ansel\Controller\Field\FieldValidate;
use BoldMinded\Ansel\Controller\Field\FieldSave;
use BoldMinded\Ansel\Service\UpdatesFeed;
use BoldMinded\Ansel\Service\Uploader;
use BoldMinded\Ansel\Controller\Field\FieldUploader;
use BoldMinded\Ansel\Model\PHPFileUpload;
use BoldMinded\Ansel\Service\Sources\SourceRouter;
use BoldMinded\Ansel\Service\Sources\Ee as EESource;
use BoldMinded\Ansel\Service\Sources\Assets as AssetsSource;
use BoldMinded\Ansel\Model\File as FileModel;
use BoldMinded\Ansel\Service\ImageManipulation\ManipulateImage;
use BoldMinded\Ansel\Service\FileCacheService;
use BoldMinded\Ansel\Service\ImageManipulation\CropImage;
use BoldMinded\Ansel\Service\ImageManipulation\ResizeImage;
use BoldMinded\Ansel\Service\ImageManipulation\CopyImage;
use BoldMinded\Ansel\Service\AnselImages\SaveRow;
use BoldMinded\Ansel\Service\AnselImages\DeleteRow;
use BoldMinded\Ansel\Service\Noop;
use BoldMinded\Ansel\Controller\Field\ImagesTag;
use BoldMinded\Ansel\Model\ImagesTagParams;
use BoldMinded\Ansel\Service\AnselImages\ImagesTag as AnselImagesTag;
use BoldMinded\Ansel\Service\NoResults;
use BoldMinded\Ansel\Service\ParseInternalTags;
use BoldMinded\Ansel\Model\Source as SourceModel;
use BoldMinded\Ansel\Service\NamespaceVars;
use BoldMinded\Ansel\Model\InternalTagParams;
use BoldMinded\Ansel\Service\AnselImages\InternalTag;
use BoldMinded\Ansel\Dependency\ImageOptimizer\OptimizerFactory;
use BoldMinded\Ansel\Service\Install\UpdateTo2_0_0\Images as Images200UpdaterService;
use ExpressionEngine\Core\Provider;
use ExpressionEngine\Library\Data\Collection;
use ExpressionEngine\Service\Model\Collection as ModelCollection;

require_once PATH_THIRD . 'ansel/vendor-build/autoload.php';

if (!defined('ANSEL_VERSION')) {
    define('ANSEL_VERSION', '3.1.0');
    define('ANSEL_NAME_SHORT', 'Ansel');
    define('ANSEL_DESC', 'Crop images with pre-defined constraints');
    define('ANSEL_TRIAL', file_exists(PATH_THIRD . 'ansel/Config/trial'));
    define('ANSEL_NAME', ANSEL_NAME_SHORT . (ANSEL_TRIAL ? ' (Free Trial)' : ''));
    define('ANSEL_EXT', 'Ansel_ext');
    define('ANSEL_PATH', PATH_THIRD.'ansel/');
    define('ANSEL_BUILD_VERSION', '3282ccc2');
    define('ANSEL_LICENSE_PATH', ANSEL_PATH.'license.md');
    define('ANSEL_CACHE', PATH_CACHE . 'ansel/');
    define('ANSEL_CACHE_PERSISTENT', PATH_CACHE . 'ansel_persistent/');
    define('ANSEL_SUPPORTS_OPTIM', version_compare(phpversion(), '5.5.0', '>='));
}

return [
    'author' => 'BoldMinded, LLC',
    'author_url' => 'https://boldminded.com',
    'description' => ANSEL_DESC,
    'docs_url' => 'https://docs.boldminded.com/ansel',
    'name' => ANSEL_NAME,
    'namespace' => 'BoldMinded\Ansel',
    'settings_exist' => true,
    'version' => ANSEL_VERSION,

    'requires' => [
        'php'   => '8.2',
        'ee'    => '7.4'
    ],

    'fieldtypes' => [
        'ansel' => [
            'name' => 'Ansel',
            'templateGenerator' => 'Ansel'
        ]
    ],

    'models' => [
        'Image' => 'Record\Image',
        'Setting' => 'Record\Setting',
        'UploadKey' => 'Record\UploadKey'
    ],

    'coilpack' => [
        'fieldtypes' => [
            'ansel' => BoldMinded\Ansel\Tags\Replace::class
        ]
    ],

    'commands' => [
        'ansel:migrate-assets' => BoldMinded\Ansel\Commands\CommandMigrateAssets::class,
    ],

    'services.singletons' => [
        'Logger' => function () {
            ee()->load->library('logger');
            return new Logger(
                logger: ee()->logger,
                enabled: true,
            );
        },
    ],

    'services' => [
        /**
         * Services
         */
        'RecordInstallerService' => function ($addon) {
            /** @var Provider $addon */

            // Make sure the forge class is loaded
            ee()->load->dbforge();

            return new RecordInstallerService(
                $addon->get('namespace'),
                $addon->get('models'),
                ee('db'),
                ee()->dbforge,
                ee('Model')
            );
        },
        'ModuleInstallerService' => function ($addon) {
            /** @var Provider $addon */

            return new ModuleInstallerService(
                $addon->get('version'),
                ee('Model')
            );
        },
        'FieldTypeUpdaterService' => function ($addon) {
            /** @var Provider $addon */

            return new FieldTypeUpdaterService(
                $addon->get('version'),
                ee('Model')
            );
        },
        'GlobalSettings' => function ($addon) {
            /** @var Provider $addon */

            /**
             * We don't want to have more than one instance of this class
             */

            // Get the EE session class for cache access
            /** @var EE_Session $session */
            $session = ee()->session;

            // Get the class from the session cache
            $settings = $session->cache('ansel', 'GlobalSettings');

            // If the class does not exist, we need to create it
            if (! $settings) {
                // Create GlobalSettings class
                $settings = new GlobalSettings(
                    ee('Model'),
                    ee()->config
                );

                // Store it in cache
                ee()->session->set_cache('ansel', 'GlobalSettings', $settings);
            }

            // Return the class
            return $settings;
        },
        'UpdatesFeed' => function ($addon) {
            /** @var Provider $addon */

            return new UpdatesFeed(
                $addon->get('version'),
                ee('ansel:GlobalSettings'),
                ee('ansel:UpdateFeedItem'),
                new Collection()
            );
        },
        'UploadDestinationsMenu' => function ($addon) {
            /** @var Provider $addon */

            // Get an instance of Assets addon service if it exists
            /** @var \ExpressionEngine\Service\Addon\Addon $assets */
            $assets = ee('Addon')->get('assets');

            // Let's set a null value on the Assets lib
            $assetsLib = null;

            // If Assets is installed, get the lib
            if ($assets && $assets->isInstalled()) {
                // Add assets libraries and paths
                ee()->load->add_package_path(PATH_THIRD . 'assets/');
                ee()->load->library('assets_lib');

                $assetsLib = ee()->assets_lib;
            }

            // Return instance of UploadDestinationsMenu with dependencies
            return new UploadDestinationsMenu(
                ee()->config->item('site_id'),
                ee('Model'),
                $assetsLib
            );
        },
        'UploadKeys' => function ($addon) {
            /** @var Provider $addon */

            return new UploadKeys(
                ee('Model'),
                ee()->config->item('site_url'),
                ee()->config->item('site_index')
            );
        },
        'UploaderService' => function ($addon) {
            /** @var Provider $addon */

            return new Uploader(ANSEL_CACHE);
        },
        'SourceRouter' => function ($addon) {
            /** @var Provider $addon */

            return new SourceRouter(
                ee('ansel:EESource'),
            );
        },
        'EESource' => function ($addon) {
            /** @var Provider $addon */

            // Load EE Libraries
            ee()->load->model('file_model');
            ee()->load->library('filemanager');

            return new EESource(
                ee('CP/FilePicker')->make(),
                ee('Model'),
                ee('ansel:SourceModel'),
                ee('ansel:FileModel'),
                ee('ansel:FileCacheService'),
                ee()->file_model,
                ee()->filemanager,
                ee('ansel:Logger'),
                ee()->config->item('site_id'),
                ee()->session->userdata('member_id'),
            );
        },
        'AssetsSource' => function ($addon) {
            /** @var Provider $addon */

            // Make sure assets things are loaded
            ee()->load->add_package_path(PATH_THIRD . 'assets/');
            require_once PATH_THIRD . 'assets/helper.php';
            ee()->load->library('assets_lib');

            return new AssetsSource(
                new \Assets_helper(),
                ee()->assets_lib,
                ee('db'),
                ee('ansel:SourceModel'),
                ee('ansel:FileModel'),
                ee('ansel:FileCacheService')
            );
        },
        'FileCacheService' => function ($addon) {
            /** @var Provider $addon */
            /** @var EE_Config $eeConfig */
            $eeConfig = ee()->config;

            return new FileCacheService(
                ee('ansel:Logger'),
                $eeConfig->item('httpUser', 'ansel'),
                $eeConfig->item('httpPass', 'ansel'),
            );
        },
        'CropImage' => function ($addon) {
            /** @var Provider $addon */
            /** @var EE_Config $eeConfig */
            $eeConfig = ee()->config;

            return new CropImage($eeConfig->item('forceGD', 'ansel'));
        },
        'ResizeImage' => function ($addon) {
            /** @var Provider $addon */
            /** @var EE_Config $eeConfig */
            $eeConfig = ee()->config;

            return new ResizeImage($eeConfig->item('forceGD', 'ansel'));
        },
        'CopyImage' => function ($addon) {
            /** @var Provider $addon */
            /** @var EE_Config $eeConfig */
            $eeConfig = ee()->config;

            return new CopyImage($eeConfig->item('forceGD', 'ansel'));
        },
        'ManipulateImage' => function ($addon) {
            /** @var Provider $addon */
            /** @var EE_Config $eeConfig */
            $eeConfig = ee()->config;

            // Make sure we have support for Image Optimization
            $optimizerFactory = null;
            if (ANSEL_SUPPORTS_OPTIM) {
                // Show optimizer errors
                $showOptimizerErrors = $eeConfig->item(
                    'optimizerShowErrors',
                    'ansel'
                );

                // Set up config for OptimizerFactory
                $optimizerFactoryConfig = [
                    'ignore_errors' => ! $showOptimizerErrors
                ];

                $optimizerFactory = new OptimizerFactory($optimizerFactoryConfig);
            }

            return new ManipulateImage(
                ee('ansel:FileCacheService'),
                ee('ansel:CropImage'),
                ee('ansel:ResizeImage'),
                ee('ansel:CopyImage'),
                $optimizerFactory,
                $eeConfig
            );
        },
        'AnselImagesSaveRow' => function ($addon) {
            /** @var Provider $addon */

            return new SaveRow(
                ee('Model'),
                ee('ansel:SourceRouter'),
                ee('ansel:FileCacheService'),
                ee('ansel:ManipulateImage'),
                ee()->session->userdata('member_id'),
                ee()->config->item('site_id')
            );
        },
        'AnselImagesDeleteRow' => function ($addon) {
            /** @var Provider $addon */

            return new DeleteRow(
                ee('Model'),
                ee('ansel:SourceRouter')
            );
        },
        'AnselImagesTag' => function ($addon) {
            /** @var Provider $addon */

            // Make sure a file model is loaded
            ee()->load->model('file_model');
            ee()->load->library('logger');

            $isNativeTemplateEngine = !ee()->TMPL?->template_engine;

            return new AnselImagesTag(
                ee('Model'),
                ee('ansel:ImagesTagParams'),
                ee('ansel:SourceRouter'),
                ee('ansel:NamespaceVars'),
                ee('ansel:GlobalSettings'),
                ee('ansel:AnselInternalTag'),
                ee('ansel:LivePreviewFactory'),
                ee()->file_model,
                ee()->logger,
                $isNativeTemplateEngine
            );
        },
        'LivePreviewFactory' => function ($addon) {
            ee()->load->library('logger');

            return new LivePreviewFactory(
                ee()->logger,
            );
        },
        'AnselInternalTag' => function ($addon) {
            /** @var Provider $addon */

            return new InternalTag(
                ee('ansel:SourceRouter'),
                ee('ansel:ManipulateImage')
            );
        },
        'Noop' => function ($addon) {
            /** @var Provider $addon */

            return new Noop();
        },
        'NoResults' => function ($addon) {
            /** @var Provider $addon */

            return new NoResults();
        },
        'ParseInternalTags' => function ($addon) {
            /** @var Provider $addon */

            return new ParseInternalTags(
                ee('ansel:InternalTagParams')
            );
        },
        'NamespaceVars' => function ($addon) {
            /** @var Provider $addon */

            return new NamespaceVars();
        },

        'Images200UpdaterService' => function ($addon) {
            /** @var Provider $addon */

            // Load the forge class
            ee()->load->dbforge();

            return new Images200UpdaterService(
                ee()->dbforge
            );
        },

        /**
         * Controllers
         */
        'CPController' => function ($addon, $controller, $class) {
            /** @var Provider $addon */

            ee()->load->library('typography');
            ee()->typography->initialize();

            return new $class(
                $controller,
                ee('CP/Sidebar')->make(),
                ee('CP/URL'),
                ee('View'),
                ee('ansel:GlobalSettings'),
                ee('Request'),
                ee('CP/Alert'),
                ee()->functions,
                ee()->cp,
                ee('ansel:UpdatesFeed'),
                ee()->typography,
                URL_THIRD_THEMES,
                PATH_THIRD_THEMES
            );
        },
        'FieldSettingsController' => function ($addon, $data = []) {
            /** @var Provider $addon */

            return new FieldSettings(
                ee('ansel:GlobalSettings'),
                ee('ansel:UploadDestinationsMenu'),
                ee('Validation'),
                ee('ansel:FieldSettingsModel'),
                $data
            );
        },
        'FieldDisplayController' => function (
            $addon,
            $rawFieldSettings = []
        ) {
            /** @var Provider $addon */

            return new FieldDisplay(
                ee('Model'),
                new ModelCollection(),
                ee('ansel:GlobalSettings'),
                ee('ansel:FieldSettingsModel'),
                ee('View'),
                ee('ansel:UploadKeys'),
                ee('ansel:SourceRouter'),
                ee()->config->item('site_id'),
                REQ === 'CP',
                $rawFieldSettings
            );
        },
        'FieldUploaderController' => function ($addon) {
            /** @var Provider $addon */

            return new FieldUploader(
                ee('ansel:UploadKeys'),
                ee('Request'),
                ee('ansel:PHPFileUploadModel'),
                ee('ansel:UploaderService'),
                ee()->output
            );
        },
        'FieldValidateController' => function (
            $addon,
            $rawFieldSettings = []
        ) {
            /** @var Provider $addon */

            return new FieldValidate(
                ee('ansel:FieldSettingsModel'),
                $rawFieldSettings
            );
        },
        'FieldSaveController' => function (
            $addon,
            $rawFieldSettings = []
        ) {
            /** @var Provider $addon */

            return new FieldSave(
                ee('ansel:AnselImagesSaveRow'),
                ee('ansel:AnselImagesDeleteRow'),
                ee('ansel:FieldSettingsModel'),
                $rawFieldSettings
            );
        },
        'ImagesTagController' => function ($addon) {
            /** @var Provider $addon */

            $isNativeTemplateEngine = ee()->TMPL?->template_engine === null;

            return new ImagesTag(
                ee('ansel:ImagesTagParams'),
                ee('ansel:AnselImagesTag'),
                ee('ansel:NoResults'),
                ee('ansel:ParseInternalTags'),
                ee()->TMPL,
                $isNativeTemplateEngine,
            );
        },

        /**
         * Models
         */
        'UpdateFeedItem' => function ($addon) {
            /** @var Provider $addon */

            // Load EE Typography library
            ee()->load->library('typography');
            ee()->typography->initialize();

            return new UpdateFeedItem(
                ee()->typography
            );
        },
        'FieldSettingsModel' => function ($addon) {
            /** @var Provider $addon */

            return new FieldSettingsModel();
        },
        'PHPFileUploadModel' => function ($addon) {
            /** @var Provider $addon */

            return new PHPFileUpload();
        },
        'FileModel' => function ($addon) {
            /** @var Provider $addon */

            return new FileModel();
        },
        'ImagesTagParams' => function ($addon) {
            /** @var Provider $addon */

            return new ImagesTagParams();
        },
        'SourceModel' => function ($addon) {
            /** @var Provider $addon */

            return new SourceModel();
        },
        'InternalTagParams' => function ($addon) {
            /** @var Provider $addon */

            return new InternalTagParams();
        }
    ],

    'aliases' => [
        'ExpressionEngine\Addons\FilePicker\Service\FilePicker\FilePicker',
        'ExpressionEngine\Core\Provider',
        'ExpressionEngine\Core\Request',
        'ExpressionEngine\Service\Alert\AlertCollection',
        'ExpressionEngine\Service\Database\Query',
        'ExpressionEngine\Service\Model\Collection',
        'ExpressionEngine\Service\Model\Facade',
        'ExpressionEngine\Service\Sidebar\Sidebar',
        'ExpressionEngine\Service\URL\URLFactory',
        'ExpressionEngine\Service\Validation\Factory',
        'ExpressionEngine\Service\View\ViewFactory',
    ],
];
