<?php // @codingStandardsIgnoreStart

// @codingStandardsIgnoreEnd

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

use BuzzingPixel\Ansel\Service\UploadKeys;
use EllisLab\ExpressionEngine\Core\Provider;
use BuzzingPixel\Ansel\Service\Install\RecordInstallerService;
use BuzzingPixel\Ansel\Service\Install\ModuleInstallerService;
use BuzzingPixel\Ansel\Service\Install\FieldTypeUpdaterService;
use BuzzingPixel\Ansel\Service\GlobalSettings;
use BuzzingPixel\Ansel\Model\UpdateFeedItem;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use BuzzingPixel\Ansel\Service\LicenseCheck;
use BuzzingPixel\Ansel\Service\LicensePing;
use BuzzingPixel\Ansel\Controller\Field\FieldSettings;
use BuzzingPixel\Ansel\Model\FieldSettings as FieldSettingsModel;
use BuzzingPixel\Ansel\Service\UploadDestinationsMenu;
use BuzzingPixel\Ansel\Controller\Field\FieldDisplay;
use BuzzingPixel\Ansel\Controller\Field\FieldValidate;
use BuzzingPixel\Ansel\Controller\Field\FieldSave;
use BuzzingPixel\Ansel\Service\UpdatesFeed;
use BuzzingPixel\Ansel\Service\Uploader;
use BuzzingPixel\Ansel\Controller\Field\FieldUploader;
use BuzzingPixel\Ansel\Model\PHPFileUpload;
use BuzzingPixel\Ansel\Service\Sources\SourceRouter;
use BuzzingPixel\Ansel\Service\Sources\Ee as EESource;
use BuzzingPixel\Ansel\Service\Sources\Treasury as TreasurySource;
use BuzzingPixel\Ansel\Service\Sources\Assets as AssetsSource;
use BuzzingPixel\Ansel\Model\File as FileModel;
use BuzzingPixel\Ansel\Service\ImageManipulation\ManipulateImage;
use BuzzingPixel\Ansel\Service\FileCacheService;
use BuzzingPixel\Ansel\Service\ImageManipulation\CropImage;
use BuzzingPixel\Ansel\Service\ImageManipulation\ResizeImage;
use BuzzingPixel\Ansel\Service\ImageManipulation\CopyImage;
use BuzzingPixel\Ansel\Service\AnselImages\SaveRow;
use BuzzingPixel\Ansel\Service\AnselImages\DeleteRow;
use BuzzingPixel\Ansel\Service\Noop;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use BuzzingPixel\Ansel\Controller\Field\ImagesTag;
use BuzzingPixel\Ansel\Model\ImagesTagParams;
use BuzzingPixel\Ansel\Service\AnselImages\ImagesTag as AnselImagesTag;
use BuzzingPixel\Ansel\Service\NoResults;
use BuzzingPixel\Ansel\Service\ParseInternalTags;
use BuzzingPixel\Ansel\Model\Source as SourceModel;
use BuzzingPixel\Ansel\Service\NamespaceVars;
use BuzzingPixel\Ansel\Model\InternalTagParams;
use BuzzingPixel\Ansel\Service\AnselImages\InternalTag;
use ImageOptimizer\OptimizerFactory;
use BuzzingPixel\Ansel\Service\Install\UpdateTo2_0_0\Images as Images200UpdaterService;

// Get addon json path
$addonPath = realpath(dirname(__FILE__));
$addonJsonPath = "{$addonPath}/addon.json";

// Get the addon json file
$addonJson = json_decode(file_get_contents($addonJsonPath));

// Set paths
$sysPath = rtrim(realpath(SYSPATH), '/') . '/';
$cachePath = "{$sysPath}user/cache/";

// Define constants
defined('ANSEL_LICENSE_PATH') || define('ANSEL_LICENSE_PATH', "{$addonPath}/license.md");
defined('ANSEL_NAME') || define('ANSEL_NAME', $addonJson->label);
defined('ANSEL_VER') || define('ANSEL_VER', $addonJson->version);
defined('ANSEL_CACHE') || define('ANSEL_CACHE', "{$cachePath}ansel/");
defined('ANSEL_CACHE_PERSISTENT') || define(
	'ANSEL_CACHE_PERSISTENT',
	"{$cachePath}ansel_persistent/"
);
defined('ANSEL_SUPPORTS_OPTIM') || define(
	'ANSEL_SUPPORTS_OPTIM',
	version_compare(phpversion(), '5.5.0', '>=')
);

// Get composer autoload file
if (ANSEL_SUPPORTS_OPTIM) {
	$composerAutoLoadFilePath = "{$addonPath}/vendor/autoload.php";
	if (is_file($composerAutoLoadFilePath)) {
		include_once $composerAutoLoadFilePath;
	}
}

// Return info about the addon for ExpressionEngine
return array(
	'author' => $addonJson->author,
	'author_url' => $addonJson->authorUrl,
	'description' => $addonJson->description,
	'docs_url' => $addonJson->docsUrl,
	'name' => $addonJson->label,
	'namespace' => $addonJson->namespace,
	'settings_exist' => $addonJson->settingsExist,
	'version' => $addonJson->version,
	'models' => array(
		'Image' => 'Record\Image',
		'Setting' => 'Record\Setting',
		'UploadKey' => 'Record\UploadKey'
	),
	'services' => array(
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
		'LicenseCheck' => function ($addon) {
			/** @var Provider $addon */

			/**
			 * We don't want to have more than one instance of this class
			 */

			// Get the EE session class for cache access
			/** @var EE_Session $session */
			$session = ee()->session;

			// Get the class from the session cache
			$class = $session->cache('Ansel', 'LicenseCheck');

			// If the class does not exist, we need to create it
			if (! $class) {
				// Set defaults
				$cpUrl = null;
				$cpAlert = null;
				$javaScript = null;

				// Check for CP
				if (REQ === 'CP') {
					$cpUrl = ee('CP/URL');
					$cpAlert = ee('CP/Alert');
					$javaScript = ee()->javascript;
				}

				// Create the license check class
				$class = new LicenseCheck(
					ee('ansel:GlobalSettings'),
					$cpUrl,
					$cpAlert,
					$javaScript,
					ee('Model'),
					ee()->config->item('site_url'),
					ee()->config->item('site_index')
				);

				// Store it in cache
				$session->set_cache('Ansel', 'LicenseCheck', $class);
			}

			// Return the class
			return $class;
		},
		'LicensePing' => function ($addon) {
			/** @var Provider $addon */

			return new LicensePing(ee('ansel:GlobalSettings'), $addon);
		},
		'UploadDestinationsMenu' => function ($addon) {
			/** @var Provider $addon */

			// Get an instance of Treasury addon service if it exists
			/** @var \EllisLab\ExpressionEngine\Service\Addon\Addon $treasury */
			$treasury = ee('Addon')->get('treasury');

			// Let's set a null value on the Treasury locations API
			$treasuryLocationsApi = null;

			// If Treasury is installed, get the API
			if ($treasury && $treasury->isInstalled()) {
				$treasuryLocationsApi = ee('treasury:LocationsAPI');
			}

			// Get an instance of Assets addon service if it exists
			/** @var \EllisLab\ExpressionEngine\Service\Addon\Addon $assets */
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
				$treasuryLocationsApi,
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

			// Get an instance of Treasury addon service if it exists
			/** @var \EllisLab\ExpressionEngine\Service\Addon\Addon $treasury */
			$treasury = ee('Addon')->get('treasury');

			// Let's set a null value on the Treasury locations API
			$treasurySource = null;

			// If Treasury is installed, get the API
			if ($treasury && $treasury->isInstalled()) {
				$treasurySource = ee('ansel:TreasurySource');
			}

			// Get an instance of Assets addon service if it exists
			/** @var \EllisLab\ExpressionEngine\Service\Addon\Addon $assets */
			$assets = ee('Addon')->get('assets');

			// Let's set a null value on the Assets lib
			$assetsSource = null;

			// If Assets is installed, get the lib
			if ($assets && $assets->isInstalled()) {
				$assetsSource = ee('ansel:AssetsSource');
			}

			return new SourceRouter(
				ee('ansel:EESource'),
				$treasurySource,
				$assetsSource
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
				ee()->config->item('site_id'),
				ee()->session->userdata('member_id')
			);
		},
		'TreasurySource' => function ($addon) {
			/** @var Provider $addon */

			// Set null default for Treasury file picker
			$treasuryFilePicker = null;

			if (defined('REQ') && REQ === 'CP') {
				$treasuryFilePicker = ee('treasury:FilePicker')->make();
			}

			return new TreasurySource(
				$treasuryFilePicker,
				ee('treasury:UploadAPI'),
				ee('treasury:FilesAPI'),
				ee('treasury:LocationsAPI'),
				ee('ansel:SourceModel'),
				ee('ansel:FileModel'),
				ee('ansel:FileCacheService')
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

			return new FileCacheService();
		},
		'CropImage' => function ($addon) {
			/** @var Provider $addon */

			// Get EE Config class
			/** @var EE_Config $eeConfig */
			$eeConfig = ee()->config;

			return new CropImage($eeConfig->item('forceGD', 'ansel'));
		},
		'ResizeImage' => function ($addon) {
			/** @var Provider $addon */

			// Get EE Config class
			/** @var EE_Config $eeConfig */
			$eeConfig = ee()->config;

			return new ResizeImage($eeConfig->item('forceGD', 'ansel'));
		},
		'CopyImage' => function ($addon) {
			/** @var Provider $addon */

			// Get EE Config class
			/** @var EE_Config $eeConfig */
			$eeConfig = ee()->config;

			return new CopyImage($eeConfig->item('forceGD', 'ansel'));
		},
		'ManipulateImage' => function ($addon) {
			/** @var Provider $addon */

			// Get EE Config class
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
				$optimizerFactoryConfig = array(
					'ignore_errors' => ! $showOptimizerErrors
				);

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

			// Make sure file model is loaded
			ee()->load->model('file_model');

			return new AnselImagesTag(
				ee('Model'),
				ee('ansel:ImagesTagParams'),
				ee('ansel:SourceRouter'),
				ee('ansel:NamespaceVars'),
				ee('ansel:GlobalSettings'),
				ee('ansel:AnselInternalTag'),
				ee()->file_model
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
				ee('ansel:LicensePing'),
				ee()->typography,
				URL_THIRD_THEMES,
				PATH_THIRD_THEMES
			);
		},
		'FieldSettingsController' => function ($addon, $data = array()) {
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
			$rawFieldSettings = array()
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
			$rawFieldSettings = array()
		) {
			/** @var Provider $addon */

			return new FieldValidate(
				ee('ansel:FieldSettingsModel'),
				$rawFieldSettings
			);
		},
		'FieldSaveController' => function (
			$addon,
			$rawFieldSettings = array()
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

			return new ImagesTag(
				ee('ansel:ImagesTagParams'),
				ee('ansel:AnselImagesTag'),
				ee('ansel:NoResults'),
				ee('ansel:ParseInternalTags'),
				ee()->TMPL
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
	)
);
