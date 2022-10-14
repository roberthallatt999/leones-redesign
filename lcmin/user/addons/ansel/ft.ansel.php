<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use BuzzingPixel\Ansel\Service\LicenseCheck;

/**
 * Class Ansel_ft
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
// @codingStandardsIgnoreStart
class Ansel_ft extends EE_Fieldtype
// @codingStandardsIgnoreEnd
{
	/**
	 * Var ID for Low Variables (so PHPStorm doesn’t show errors)
	 */
	public $var_id;

	/**
	 * Error Message for Low Variables (so PHPStorm doesn’t show errors)
	 */
	public $error_msg;

	/**
	 * @var bool $has_array_data Set field type as tag pair
	 */
	public $has_array_data = true;

	/**
	 * Required info for EE fieldtype
	 *
	 * @var array $info
	 */
	public $info = array(
		'name' => ANSEL_NAME,
		'version' => ANSEL_VER
	);

	/**
	 * License status
	 */
	private $licenseStatus;

	/**
	 * Table exists
	 */
	private $tableExists = false;

	/**
	 * Field type constructor
	 */
	public function __construct()
	{
		// Make sure the package path is available
		$anselPath = PATH_THIRD . 'ansel/';
		$pathLoaded = in_array($anselPath, ee()->load->get_package_paths());
		if (! $pathLoaded) {
			ee()->load->add_package_path(PATH_THIRD . 'ansel/');
		}

		// Make sure the lang file is available
		ee()->lang->loadfile('ansel');

		// Check if Ansel table exists
		$this->tableExists = ee('db')->table_exists('ansel_settings');

		// Run license check
		// Don't know of a better way to do this right now
		// If this check is not here, the license check runs into errors on
		// uninstall routine
		if (defined('REQ') && REQ === 'CP' && $this->tableExists) {
			// Get license check service
			$licenseCheckService = ee('ansel:LicenseCheck');

			// Run license check
			$this->licenseStatus = $licenseCheckService->run();

			// Place license ping action ID JS
			$licenseCheckService->placeActionIdForJS();
		}

		//Add CSS and JS
		$this->setCpCssAndJs();

		// Run parent constructor
		parent::__construct();
	}

	/**
	 * Set cp CSS and JS
	 */
	private function setCpCssAndJs()
	{
		// Pre-flight checks
		if (! $this->tableExists ||
			! isset(ee()->cp) ||
			ee()->session->cache('ansel', 'cpAssetsSet')
		) {
			return;
		}

		$cssPath = PATH_THIRD_THEMES . 'ansel/css/style.min.css';
		if (is_file($cssPath)) {
			$cssFileTime = filemtime($cssPath);
		} else {
			$cssFileTime = uniqid();
		}
		$css = URL_THIRD_THEMES;
		$css .= "ansel/css/style.min.css?v={$cssFileTime}";
		$cssTag = "<link rel=\"stylesheet\" href=\"{$css}\">";
		ee()->cp->add_to_head($cssTag);

		$jsPath = PATH_THIRD_THEMES . 'ansel/js/script.min.js';
		if (is_file($jsPath)) {
			$jsFileTime = filemtime($jsPath);
		} else {
			$jsFileTime = uniqid();
		}
		$js = URL_THIRD_THEMES;
		$js .= "ansel/js/script.min.js?v={$jsFileTime}";
		$jsTag = "<script type=\"text/javascript\" src=\"{$js}\"></script>";
		ee()->cp->add_to_foot($jsTag);

		ee()->session->set_cache('ansel', 'cpAssetsSet', true);
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Enable Blocks, Channel and Grid and Low Variables compatibility
	 *
	 * @param string $name
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	public function accepts_content_type($name) // @codingStandardsIgnoreEnd
	{
		$compatibility = array(
			'blocks/1',
			'channel',
			'grid',
			'low_variables',
			'fluid_field',
		);

		return in_array($name, $compatibility, false);
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Field settings
	 *
	 * @param array $data Existing field setting data
	 * @return array
	 */
	// @codingStandardsIgnoreStart
	public function display_settings($data) // @codingStandardsIgnoreEnd
	{
		// Set field ID
		$data['field_id'] = $this->field_id;

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired' ||
			$this->licenseStatus === 'invalid'
		) {
			return array();
		}

		// Show field settings
		return array('field_options_ansel' => array(
			'label' => 'field_options',
			'group' => 'ansel',
			'settings' => ee('ansel:FieldSettingsController', $data)->get()
		));
	}

	/**
	 * Grid field settings
	 *
	 * @param array $data Existing field setting data
	 * @return array
	 */
	// @codingStandardsIgnoreStart
	public function grid_display_settings($data) // @codingStandardsIgnoreEnd
	{
		// Set field ID
		$data['field_id'] = $this->field_id;

		// Set type
		$data['type'] = 'grid';

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired' ||
			$this->licenseStatus === 'invalid'
		) {
			return array();
		}

		// Show field settings
		return array(
			'field_options' => ee('ansel:FieldSettingsController', $data)->get()
		);
	}

	/**
	 * Low Variables field settings
	 *
	 * @param array $data Existing field setting data
	 * @return array
	 */
	// @codingStandardsIgnoreStart
	public function var_display_settings($data) // @codingStandardsIgnoreEnd
	{
		// Set field ID
		$data['field_id'] = $this->field_id;

		// Set type
		$data['type'] = 'lowVar';

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired' ||
			$this->licenseStatus === 'invalid'
		) {
			return array();
		}

		// Show field settings
		return array('field_options_ansel' => array(
			'label' => 'field_options',
			'group' => 'ansel',
			'settings' => ee('ansel:FieldSettingsController', $data)->get()
		));
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Validate field settings
	 *
	 * @param array $data
	 * @return mixed
	 */
	// @codingStandardsIgnoreStart
	public function validate_settings($data) // @codingStandardsIgnoreEnd
	{
		return ee('ansel:FieldSettingsController', $data)->validate();
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Save field settings
	 *
	 * @param array $data
	 * @return array
	 */
	// @codingStandardsIgnoreStart
	public function save_settings($data) // @codingStandardsIgnoreEnd
	{
		// Check if the field is required and set min quantity if so
		if (isset($data['field_required']) && $data['field_required'] === 'y') {
			$qty = isset($data['ansel_min_qty']) ? $data['ansel_min_qty'] : 0;
			$qty = (int) $qty;
			$data['ansel_min_qty'] = $qty ?: 1;
		}

		return ee('ansel:FieldSettingsController', $data)->save();
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Save Low Variables field settings
	 *
	 * @param array $data
	 * @return false|array
	 * @throws \Exception
	 */
	// @codingStandardsIgnoreStart
	public function var_save_settings($data) // @codingStandardsIgnoreEnd
	{
		// Get controller
		$controller = ee('ansel:FieldSettingsController', $data);

		// Validate data
		/** @var ValidationResult $validation */
		$validation = $controller->validate();

		// Make sure data validates
		if (! $validation->isValid()) {
			throw new \Exception(lang('some_data_did_not_validate'));
		}

		// Return the save data
		return $controller->save();
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Display field
	 *
	 * @param mixed $data Existing field data
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	public function display_field($data) // @codingStandardsIgnoreEnd
	{
		// Channel form may not get the assets from the constructor set
		// if the fieldtype is called before the channel form
		$this->setCpCssAndJs();

		// Check for postback data
		if (gettype($data) !== 'array') {
			$data = null;
		}

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired') {
			$controllerReturn = ee(
				'ansel:CPController',
				'TrialExpired',
				'BuzzingPixel\Ansel\Controller\CP\TrialExpired'
			)->get();

			return $controllerReturn['body'];
		} elseif ($this->licenseStatus === 'invalid') {
			$controllerReturn = ee(
				'ansel:CPController',
				'InvalidLicense',
				'BuzzingPixel\Ansel\Controller\CP\InvalidLicense'
			)->get();

			return $controllerReturn['body'];
		}

		// Set type
		$this->settings['type'] = 'channel';

		$fluidFieldId = isset($this->settings['fluid_field_data_id']) ?
			$this->settings['fluid_field_data_id'] :
			null;

		if ($fluidFieldId) {
			$this->settings['type'] = 'fluid';
		}

		/** @var \BuzzingPixel\Ansel\Controller\Field\FieldDisplay $controller */
		$controller = ee('ansel:FieldDisplayController', $this->settings);

		// Run the controller
		return $controller->get(
			$this->content_id(),
			$fluidFieldId,
			$fluidFieldId,
			$data
		);
	}

	/**
	 * Grid display field
	 *
	 * @param mixed $data Existing field data
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	public function grid_display_field($data) // @codingStandardsIgnoreEnd
	{
		// Check for postback data
		if (gettype($data) !== 'array') {
			$data = null;
		}

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired') {
			$controllerReturn = ee(
				'ansel:CPController',
				'TrialExpired',
				'BuzzingPixel\Ansel\Controller\CP\TrialExpired'
			)->get();

			return $controllerReturn['body'];
		} elseif ($this->licenseStatus === 'invalid') {
			$controllerReturn = ee(
				'ansel:CPController',
				'InvalidLicense',
				'BuzzingPixel\Ansel\Controller\CP\InvalidLicense'
			)->get();

			return $controllerReturn['body'];
		}

		// Set name
		$this->settings['field_name'] = $this->field_name;

		// Set the field ID
		$this->settings['field_id'] = $this->settings['grid_field_id'];

		// Set type
		$this->settings['type'] = $this->content_type();

		// Run the controller
		return ee('ansel:FieldDisplayController', $this->settings)->get(
			$this->content_id(),
			isset($this->settings['grid_row_id']) ?
				$this->settings['grid_row_id'] :
				null,
			isset($this->settings['col_id']) ?
				$this->settings['col_id'] :
				null,
			$data
		);
	}

	/**
	 * Display Low Variables field
	 *
	 * @param mixed $data Existing field data
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	public function var_display_field($data) // @codingStandardsIgnoreEnd
	{
		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired') {
			$controllerReturn = ee(
				'ansel:CPController',
				'TrialExpired',
				'BuzzingPixel\Ansel\Controller\CP\TrialExpired'
			)->get();

			return $controllerReturn['body'];
		} elseif ($this->licenseStatus === 'invalid') {
			$controllerReturn = ee(
				'ansel:CPController',
				'InvalidLicense',
				'BuzzingPixel\Ansel\Controller\CP\InvalidLicense'
			)->get();

			return $controllerReturn['body'];
		}

		// Set type
		$this->settings['type'] = 'lowVar';

		// Set field ID
		$this->settings['field_id'] = $this->var_id;

		// Set the field name
		$this->settings['field_name'] = $this->field_name;

		// Run the controller
		return ee('ansel:FieldDisplayController', $this->settings)->get(
			$this->content_id()
		);
	}

	/**
	 * Display Low Variables field
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	public function var_wide() // @codingStandardsIgnoreEnd
	{
		return true;
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Validate Ansel field post data
	 *
	 * @param array $data
	 * @return array|bool
	 */
	public function validate($data)
	{
		// Run the controller
		return ee('ansel:FieldValidateController', $this->settings)->validate(
			$data
		);
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Save Ansel field data
	 *
	 * @param array $data
	 * @return string
	 */
	public function save($data)
	{
		// Save the json encoded data for use in the post_save method
		return json_encode($data);
	}

	/**
	 * Validate and save Low Variables Ansel field
	 *
	 * @param array $data Field Data
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	public function var_save($data) // @codingStandardsIgnoreEnd
	{
		// Set type
		$this->settings['type'] = 'lowVar';

		// Set field ID
		$this->settings['field_id'] = $this->var_id;

		// Set the field name
		$this->settings['field_name'] = $this->field_name;

		// Run the controller
		$validation = ee('ansel:FieldValidateController', $this->settings)->validate(
			$data
		);

		// Check if validation passed, return message if not
		if ($validation !== true) {
			$this->error_msg = $validation;

			return false;
		}

		// Run the controller
		ee('ansel:FieldSaveController', $this->settings)->save(
			$data,
			$this->var_id,
			$this->content_id()
		);

		// Return saved
		return true;
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Run post field save operations (Ansel does the heavy lifting here)
	 *
	 * @param string $data Json encoded field data
	 */
	// @codingStandardsIgnoreStart
	public function post_save($data) // @codingStandardsIgnoreEnd
	{
		// Decode the data
		$data = json_decode($data, true);

		$fluidFieldId = isset($this->settings['fluid_field_data_id']) ?
			$this->settings['fluid_field_data_id'] :
			null;

		if ($fluidFieldId) {
			$this->settings['type'] = 'fluid';
		}

		/** @var \BuzzingPixel\Ansel\Controller\Field\FieldSave $fieldSaveController */
		$fieldSaveController = ee('ansel:FieldSaveController', $this->settings);

		// Run the controller
		$fieldSaveController->save(
			$data,
			ee()->input->get_post('channel_id'),
			$this->content_id(),
			$fluidFieldId,
			$fluidFieldId
		);
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Run post field save operations (Ansel does the heavy lifting here)
	 *
	 * @param string $data Json encoded field data
	 */
	// @codingStandardsIgnoreStart
	public function grid_post_save($data) // @codingStandardsIgnoreEnd
	{
		// Decode the data
		$data = json_decode($data, true);

		// Set name
		$this->settings['field_name'] = $this->field_name;

		// Set the field ID
		$this->settings['field_id'] = $this->settings['grid_field_id'];

		// Set type
		$this->settings['type'] = $this->content_type();

		// Run the controller
		ee('ansel:FieldSaveController', $this->settings)->save(
			$data,
			$this->var_id ? $this->var_id : ee()->input->get_post('channel_id'),
			$this->content_id(),
			isset($this->settings['grid_row_id']) ?
				$this->settings['grid_row_id'] :
				null,
			isset($this->settings['col_id']) ?
				$this->settings['col_id'] :
				null
		);
	}

	/**
	 * Prevent Low Vars from doing anything on post save
	 */
	// @codingStandardsIgnoreStart
	public function var_post_save() // @codingStandardsIgnoreEnd
	{
		return;
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Entry delete
	 *
	 * @param array $entryIds Array of entry IDs being deleted
	 */
	// @codingStandardsIgnoreStart
	public function delete($entryIds) // @codingStandardsIgnoreEnd
	{
		// Iterate over entry IDs
		foreach ($entryIds as $entryId) {
			// Set the field ID if needed
			if (isset($this->settings['grid_field_id']) &&
				$this->settings['grid_field_id']
			) {
				$this->settings['field_id'] = $this->settings['grid_field_id'];
			}

			// Get associated image records
			$anselImageRecords = ee('Model')->get('ansel:Image')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('content_id', $entryId)
				->filter('field_id', $this->settings['field_id'])
				->filter('content_type', 'IN', array(
					'channel',
					'grid',
					'blocks'
				))
				->all();

			// Iterate through records
			foreach ($anselImageRecords as $anselImageRecord) {
				// Set data
				$data = array(
					array(
						'ansel_image_delete' => 'true',
						'ansel_image_id' => $anselImageRecord->id
					)
				);

				// Run the controller
				ee('ansel:FieldSaveController', $this->settings)->save(
					$data,
					ee()->input->get_post('channel_id'),
					(int) end(ee()->uri->segments)
				);
			}
		}
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Run post field save operations (Ansel does the heavy lifting here)
	 *
	 * @param array $gridRowIds Array of grid row IDs
	 */
	// @codingStandardsIgnoreStart
	public function grid_delete($gridRowIds) // @codingStandardsIgnoreEnd
	{
		// Iterate over IDs
		foreach ($gridRowIds as $gridRowId) {
			// Set name
			$this->settings['field_name'] = $this->field_name;

			// Set the field ID
			$this->settings['field_id'] = $this->settings['grid_field_id'];

			// Set type
			$this->settings['type'] = $this->content_type();

			// Get associated image records
			$anselImageRecords = ee('Model')->get('ansel:Image')
				->filter('site_id', ee()->config->item('site_id'))
				// ->filter('source_id', ee()->input->get_post('channel_id'))
				// ->filter('content_id', (int) end(ee()->uri->segments))
				->filter('field_id', $this->settings['field_id'])
				->filter('content_type', $this->settings['type'])
				->filter('row_id', $gridRowId)
				->filter('col_id', $this->settings['col_id'])
				->all();

			// Iterate through records
			foreach ($anselImageRecords as $anselImageRecord) {
				// Set data
				$data = array(
					array(
						'ansel_image_delete' => 'true',
						'ansel_image_id' => $anselImageRecord->id
					)
				);

				// Run the controller
				ee('ansel:FieldSaveController', $this->settings)->save(
					$data,
					ee()->input->get_post('channel_id'),
					(int) end(ee()->uri->segments),
					$gridRowId,
					$this->settings['col_id']
				);
			}
		}
	}

	/**
	 * Low Variables delete var with ansel field in it
	 *
	 * @param int $id Variable ID
	 */
	// @codingStandardsIgnoreStart
	public function var_delete($id) // @codingStandardsIgnoreEnd
	{
		// Get associated image records
		$anselImageRecords = ee('Model')->get('ansel:Image')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('source_id', $id)
			->filter('content_id', $id)
			->filter('content_type', 'lowVar')
			->filter('field_id', $id)
			->all();

		// Iterate through records
		foreach ($anselImageRecords as $anselImageRecord) {
			// Set data
			$data = array(
				array(
					'ansel_image_delete' => 'true',
					'ansel_image_id' => $anselImageRecord->id
				)
			);

			// Run the controller
			ee('ansel:FieldSaveController', $this->settings)->save(
				$data,
				$id,
				$id
			);
		}
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Replace tag
	 *
	 * @param string|bool $fieldData
	 * @param array $tagParams
	 * @param array|bool $tagData
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	public function replace_tag(
		$fieldData = false,
		$tagParams = array(),
		$tagData = false
	) { // @codingStandardsIgnoreEnd
		// Get license check service
		$licenseCheckService = ee('ansel:LicenseCheck');

		// Run license check
		$this->licenseStatus = $licenseCheckService->run();

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired') {
			return lang('ansel_trial_expired');
		} elseif ($this->licenseStatus === 'invalid') {
			return lang('ansel_license_invalid');
		}

		// Make sure tagParams is an array
		$tagParams = is_array($tagParams) ? $tagParams : [];

		$fluidFieldId = isset($this->settings['fluid_field_data_id']) ?
			$this->settings['fluid_field_data_id'] :
			null;

		// Set required params
		$tagParams = array_merge(
			$tagParams,
			array(
				'content_id' => $this->content_id(),
				'content_type' => $fluidFieldId ? 'fluid' : 'channel',
				'field_id' => $this->field_id,
				'row_id' => $fluidFieldId,
				'col_id' => $fluidFieldId,
			)
		);

		// Run the controller
		return ee('ansel:ImagesTagController')->parse(
			$tagParams,
			$tagData
		);
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Grid Replace tag
	 *
	 * @param string|bool $fieldData
	 * @param array $tagParams
	 * @param array|bool $tagData
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	public function grid_replace_tag(
		$fieldData = false,
		$tagParams = array(),
		$tagData = false
	) { // @codingStandardsIgnoreEnd
		// Get license check service
		$licenseCheckService = ee('ansel:LicenseCheck');

		// Run license check
		$this->licenseStatus = $licenseCheckService->run();

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired') {
			return lang('ansel_trial_expired');
		} elseif ($this->licenseStatus === 'invalid') {
			return lang('ansel_license_invalid');
		}

		// Make sure tagParams is an array
		$tagParams = gettype($tagParams) === 'array' ? $tagParams : $tagParams;

		// Set required params
		$tagParams = array_merge(
			$tagParams,
			array(
				'content_id' => $this->content_id(),
				'content_type' => $this->content_type(),
				'field_id' => $this->settings['grid_field_id'],
				'row_id' => isset($this->settings['grid_row_id']) ?
					$this->settings['grid_row_id'] :
					null,
				'col_id' => isset($this->settings['col_id']) ?
					$this->settings['col_id'] :
					null
			)
		);

		// Run the controller
		return ee('ansel:ImagesTagController')->parse(
			$tagParams,
			$tagData
		);
	}

	/** @noinspection PhpMissingParentCallCommonInspection */
	/**
	 * Low Variables Replace tag
	 *
	 * @param string|bool $fieldData
	 * @param array $tagParams
	 * @param array|bool $tagData
	 * @return string
	 */
	// @codingStandardsIgnoreStart
	public function var_replace_tag(
		$fieldData = false,
		$tagParams = array(),
		$tagData = false
	) { // @codingStandardsIgnoreEnd
		// Get license check service
		$licenseCheckService = ee('ansel:LicenseCheck');

		// Run license check
		$this->licenseStatus = $licenseCheckService->run();

		// Check if trial expired or license invalid
		if ($this->licenseStatus === 'expired') {
			return lang('ansel_trial_expired');
		} elseif ($this->licenseStatus === 'invalid') {
			return lang('ansel_license_invalid');
		}

		// Make sure tagParams is an array
		$tagParams = gettype($tagParams) === 'array' ? $tagParams : $tagParams;

		// Set required params
		$tagParams = array_merge(
			$tagParams,
			array(
				'content_id' => $this->var_id,
				'content_type' => 'lowVar',
				'field_id' => $this->var_id
			)
		);

		// Run the controller
		return ee('ansel:ImagesTagController')->parse(
			$tagParams,
			$tagData
		);
	}
}
