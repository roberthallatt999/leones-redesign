<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\Field;

use BuzzingPixel\Ansel\Service\GlobalSettings;
use BuzzingPixel\Ansel\Service\UploadDestinationsMenu;
use EllisLab\ExpressionEngine\Service\Validation\Factory as ValidationFactory;
use BuzzingPixel\Ansel\Model\FieldSettings as FieldSettingsModel;

/**
 * Class FieldSettings
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class FieldSettings
{
	/**
	 * @var GlobalSettings $globalSettings
	 */
	protected $globalSettings;

	/**
	 * @var UploadDestinationsMenu $uploadDestinationsMenu
	 */
	protected $uploadDestinationsMenu;

	/**
	 * @var ValidationFactory $validationFactory
	 */
	protected $validationFactory;

	/**
	 * @var FieldSettingsModel $fieldSettings
	 */
	protected $fieldSettings;

	/**
	 * Constructor
	 *
	 * @param GlobalSettings $globalSettings
	 * @param UploadDestinationsMenu $uploadDestinationsMenu
	 * @param ValidationFactory $validationFactory
	 * @param FieldSettingsModel $fieldSettings
	 * @param array $data
	 */
	public function __construct(
		GlobalSettings $globalSettings,
		UploadDestinationsMenu $uploadDestinationsMenu,
		ValidationFactory $validationFactory,
		FieldSettingsModel $fieldSettings,
		$data = array()
	) {
		// Remove ansel prefix from items if necessary
		foreach ($data as $key => $val) {
			$key = str_replace('ansel_', '', $key);
			$data[$key] = $val;
		}

		// Populate the model
		$fieldSettings->set($data);

		// Inject dependencies
		$this->globalSettings = $globalSettings;
		$this->uploadDestinationsMenu = $uploadDestinationsMenu;
		$this->validationFactory = $validationFactory;
		$this->fieldSettings = $fieldSettings;
	}

	/**
	 * Get method
	 *
	 * @return array
	 */
	public function get()
	{
		// Create an array for upload destinations menu
		$uploadDestinationsMenu = $this->uploadDestinationsMenu->getMenu();

		// Check if we should display upload/save note
		$uploadSaveExplain = '';
		if (! $this->globalSettings->hide_source_save_instructions) {
			$uploadSaveExplain = array(
				'title' => 'upload_save_dir_explanation',
				'desc' => 'upload_save_dir_hide',
				'fields' => array(
					'ansel_upload_save_explain' => array(
						'type' => 'html',
						'content' => '<p>' .
								lang('upload_save_dir_explain_upload') .
							'</p>' .
							'<p>' .
								lang('upload_save_dir_explain_save') .
							'</p>' .
							'<p>' .
								lang('upload_save_dir_explain_different_sources') .
							'</p>'
					)
				)
			);
		}

		// Check for max quantity or use default
		$maxQuantity = $this->globalSettings->default_max_qty ?: '';
		if ($this->fieldSettings->field_id) {
			$maxQuantity = $this->fieldSettings->max_qty ?: '';
		}

		// Check for image quality or use default
		$imgQuality = $this->globalSettings->default_image_quality ?: 90;
		if ($this->fieldSettings->field_id) {
			$imgQuality = $this->fieldSettings->quality;
		}

		// Check for force jpg or use default
		$forceJpg = $this->globalSettings->default_jpg ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$forceJpg = $this->fieldSettings->force_jpg ? 'y' : 'n';
		}

		// Check for force jpg or use default
		$retinaMode = $this->globalSettings->default_retina ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$retinaMode = $this->fieldSettings->retina_mode ? 'y' : 'n';
		}

		// Check for display title field or use default
		$showTitle = $this->globalSettings->default_show_title ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$showTitle = $this->fieldSettings->show_title ? 'y' : 'n';
		}

		// Check for require title field or use default
		$requireTitle = $this->globalSettings->default_require_title ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$requireTitle = $this->fieldSettings->require_title ? 'y' : 'n';
		}

		// Check for customize title label or use default
		$customizeTitleLabel = $this->globalSettings->default_title_label;
		if ($this->fieldSettings->field_id) {
			$customizeTitleLabel = $this->fieldSettings->title_label;
		}

		// Check for display title field or use default
		$showCaption = $this->globalSettings->default_show_caption ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$showCaption = $this->fieldSettings->show_caption ? 'y' : 'n';
		}

		// Check for display title field or use default
		$requireCaption = $this->globalSettings->default_require_caption ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$requireCaption = $this->fieldSettings->require_caption ? 'y' : 'n';
		}

		// Check for customize title label or use default
		$customizeCaptionLabel = $this->globalSettings->default_caption_label;
		if ($this->fieldSettings->field_id) {
			$customizeCaptionLabel = $this->fieldSettings->caption_label;
		}

		// Check for display title field or use default
		$showCover = $this->globalSettings->default_show_cover ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$showCover = $this->fieldSettings->show_cover ? 'y' : 'n';
		}

		// Check for display title field or use default
		$requireCover = $this->globalSettings->default_require_cover ? 'y' : 'n';
		if ($this->fieldSettings->field_id) {
			$requireCover = $this->fieldSettings->require_cover ? 'y' : 'n';
		}

		// Check for customize title label or use default
		$customizeCoverLabel = $this->globalSettings->default_cover_label;
		if ($this->fieldSettings->field_id) {
			$customizeCoverLabel = $this->fieldSettings->cover_label;
		}

		// Set select2 class
		$select2Class = $this->fieldSettings->type === 'grid' ?
			'js-ansel-grid-select' :
			'js-ansel-select';

		// Set field name wrapper
		$wrapper1 = '';
		$wrapper2 = '';
		if ($this->fieldSettings->type === 'lowVar') {
			$wrapper1 = 'variable_settings[ansel][';
			$wrapper2 = ']';
		}

		return array(
			/**
			 * Upload/Save Explanation
			 */
			'ansel_upload_save_explain' => $uploadSaveExplain,

			/**
			 * Upload Directory
			 */
			'ansel_upload_directory' => array(
				'title' => 'upload_directory',
				'desc' => 'upload_directory_explain',
				'fields' => array(
					"{$wrapper1}ansel_upload_directory{$wrapper2}" => array(
						'type' => 'html',
						'required' => true,
						'content' => form_dropdown(
							"{$wrapper1}ansel_upload_directory{$wrapper2}",
							$uploadDestinationsMenu,
							$this->fieldSettings->upload_directory,
							"class=\"{$select2Class}\""
						)
					)
				)
			),

			/**
			 * Save Directory
			 */
			'ansel_save_directory' => array(
				'title' => 'save_directory',
				'desc' => 'save_directory_explain',
				'fields' => array(
					"{$wrapper1}ansel_save_directory{$wrapper2}" => array(
						'type' => 'html',
						'required' => true,
						'content' => form_dropdown(
							"{$wrapper1}ansel_save_directory{$wrapper2}",
							$uploadDestinationsMenu,
							$this->fieldSettings->save_directory,
							"class=\"{$select2Class}\""
						)
					)
				)
			),

			/**
			 * Minimum Quantity
			 */
			'ansel_min_qty' => array(
				'title' => 'min_quantity',
				'desc' => 'optional',
				'fields' => array(
					"{$wrapper1}ansel_min_qty{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_min_qty{$wrapper2}",
							'type' => 'number',
							'min' => 0,
							'placeholder' => '&infin;',
							'id' => 'ansel_min_qty',
							'value' => $this->fieldSettings->min_qty ?: ''
						))
					)
				)
			),

			/**
			 * Maximum Quantity
			 */
			'ansel_max_qty' => array(
				'title' => 'max_quantity',
				'desc' => 'optional',
				'fields' => array(
					"{$wrapper1}ansel_max_qty{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_max_qty{$wrapper2}",
							'type' => 'number',
							'min' => 0,
							'placeholder' => '&infin;',
							'id' => 'ansel_max_qty',
							'value' => $maxQuantity
						))
					)
				)
			),

			/**
			 * Force JPEG
			 */
			'ansel_prevent_upload_over_max' => array(
				'title' => 'prevent_upload_over_max',
				'desc' => 'prevent_upload_over_max_explain',
				'fields' => array(
					"{$wrapper1}ansel_prevent_upload_over_max{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $this->fieldSettings->prevent_upload_over_max
							?
							'y':
							'n'
					)
				)
			),

			/**
			 * Image Quality
			 */
			'ansel_quality' => array(
				'title' => 'image_quality',
				'desc' => 'specify_jpeg_image_quality',
				'fields' => array(
					"{$wrapper1}ansel_quality{$wrapper2}" => array(
						'type' => 'html',
						'required' => true,
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_quality{$wrapper2}",
							'type' => 'number',
							'min' => 0,
							'max' => 100,
							'maxlength' => 3,
							'id' => 'ansel_quality',
							'value' => $imgQuality
						))
					)
				)
			),

			/**
			 * Force JPEG
			 */
			'ansel_force_jpg' => array(
				'title' => 'force_jpeg',
				'desc' => 'force_jpeg_explain',
				'fields' => array(
					"{$wrapper1}ansel_force_jpg{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $forceJpg
					)
				)
			),

			/**
			 * Retina mode
			 */
			'ansel_retina_mode' => array(
				'title' => 'retina_mode',
				'desc' => 'retina_mode_explain',
				'fields' => array(
					"{$wrapper1}ansel_retina_mode{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $retinaMode
					)
				)
			),

			/**
			 * Min Width
			 */
			'ansel_min_width' => array(
				'title' => 'min_width',
				'desc' => 'optional',
				'fields' => array(
					"{$wrapper1}ansel_min_width{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_min_width{$wrapper2}",
							'type' => 'number',
							'min' => 1,
							'placeholder' => '&infin;',
							'id' => 'ansel_min_width',
							'value' => $this->fieldSettings->min_width ?: ''
						))
					)
				)
			),

			/**
			 * Min Height
			 */
			'ansel_min_height' => array(
				'title' => 'min_height',
				'desc' => 'optional',
				'fields' => array(
					"{$wrapper1}ansel_min_height{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_min_height{$wrapper2}",
							'type' => 'number',
							'min' => 1,
							'placeholder' => '&infin;',
							'id' => 'ansel_min_height',
							'value' => $this->fieldSettings->min_height ?: ''
						))
					)
				)
			),

			/**
			 * Max Width
			 */
			'ansel_max_width' => array(
				'title' => 'max_width',
				'desc' => 'optional',
				'fields' => array(
					"{$wrapper1}ansel_max_width{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_max_width{$wrapper2}",
							'type' => 'number',
							'min' => 1,
							'placeholder' => '&infin;',
							'id' => 'ansel_max_width',
							'value' => $this->fieldSettings->max_width ?: ''
						))
					)
				)
			),

			/**
			 * Max Height
			 */
			'ansel_max_height' => array(
				'title' => 'max_height',
				'desc' => 'optional',
				'fields' => array(
					"{$wrapper1}ansel_max_height{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_max_height{$wrapper2}",
							'type' => 'number',
							'min' => 1,
							'placeholder' => '&infin;',
							'id' => 'ansel_max_height',
							'value' => $this->fieldSettings->max_height ?: ''
						))
					)
				)
			),

			/**
			 * Crop Ratio
			 */
			'ansel_ratio' => array(
				'title' => 'crop_ratio',
				'desc' => 'crop_ratio_explain',
				'fields' => array(
					"{$wrapper1}ansel_ratio{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_ratio{$wrapper2}",
							'type' => 'text',
							'placeholder' => lang('eg_16_9'),
							'id' => 'ansel_ratio',
							'value' => $this->fieldSettings->ratio
						))
					)
				)
			),

			/**
			 * Display title field
			 */
			'ansel_show_title' => array(
				'title' => 'display_title_field',
				'fields' => array(
					"{$wrapper1}ansel_show_title{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $showTitle
					)
				)
			),

			/**
			 * Require title field
			 */
			'ansel_require_title' => array(
				'title' => 'require_title_field',
				'fields' => array(
					"{$wrapper1}ansel_require_title{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $requireTitle
					)
				)
			),

			/**
			 * Customize title field label
			 */
			'ansel_title_label' => array(
				'title' => 'customize_title_label',
				'fields' => array(
					"{$wrapper1}ansel_title_label{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_title_label{$wrapper2}",
							'type' => 'text',
							'placeholder' => lang('eg_alt_text'),
							'id' => 'ansel_title_label',
							'value' => $customizeTitleLabel
						))
					)
				)
			),

			/**
			 * Display caption field
			 */
			'ansel_show_caption' => array(
				'title' => 'display_caption_field',
				'fields' => array(
					"{$wrapper1}ansel_show_caption{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $showCaption
					)
				)
			),

			/**
			 * Require caption field
			 */
			'ansel_require_caption' => array(
				'title' => 'require_caption_field',
				'fields' => array(
					"{$wrapper1}ansel_require_caption{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $requireCaption
					)
				)
			),

			/**
			 * Customize caption field label
			 */
			'ansel_caption_label' => array(
				'title' => 'customize_caption_label',
				'fields' => array(
					"{$wrapper1}ansel_caption_label{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_caption_label{$wrapper2}",
							'type' => 'text',
							'placeholder' => lang('eg_image_description'),
							'id' => 'ansel_caption_label',
							'value' => $customizeCaptionLabel
						))
					)
				)
			),

			/**
			 * Display cover field
			 */
			'ansel_show_cover' => array(
				'title' => 'display_cover_field',
				'fields' => array(
					"{$wrapper1}ansel_show_cover{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $showCover
					)
				)
			),

			/**
			 * Require cover field
			 */
			'ansel_require_cover' => array(
				'title' => 'require_cover_field',
				'fields' => array(
					"{$wrapper1}ansel_require_cover{$wrapper2}" => array(
						'type' => 'yes_no',
						'value' => $requireCover
					)
				)
			),

			/**
			 * Customize cover field label
			 */
			'ansel_cover_label' => array(
				'title' => 'customize_cover_label',
				'fields' => array(
					"{$wrapper1}ansel_cover_label{$wrapper2}" => array(
						'type' => 'html',
						'content' => form_input(array(
							'name' => "{$wrapper1}ansel_cover_label{$wrapper2}",
							'type' => 'text',
							'placeholder' => lang('eg_favorite'),
							'id' => 'ansel_cover_label',
							'value' => $customizeCoverLabel
						))
					)
				)
			)
		);
	}

	/**
	 * Validate field settings
	 *
	 * @return mixed
	 */
	public function validate()
	{
		// Define data as an array
		$data = array();

		// Begin empty array for data
		$inputData = $this->fieldSettings->toArray();

		// Add ansel prefix because EE removes it, but then doesn't know which
		// field the error is associated with
		foreach ($inputData as $key => $val) {
			// Make sure `ansel_` is not already prepended to the key
			if (strpos($key, 'ansel_') !== 0) {
				// Prepend the key
				$key = "ansel_{$key}";
			}

			// Set the key and data to the array
			$data[$key] = $val;
		}

		// Make an EE validator
		$validator = $this->validationFactory->make(array(
			'ansel_upload_directory' => 'required|validateDirectory',
			'ansel_save_directory' => 'required|validateDirectory',
			'ansel_min_qty' => 'validateMinMaxQty',
			'ansel_max_qty' => 'validateMinMaxQty',
			'ansel_quality' => 'required|isNaturalNoZero|lessThan[101]',
			'ansel_force_jpg' => 'enum[y, n]',
			'ansel_retina_mode' => 'enum[y, n]',
			'ansel_min_width' => 'isNatural|validateMinMaxWidth',
			'ansel_min_height' => 'isNatural|validateMinMaxHeight',
			'ansel_max_width' => 'isNatural|validateMinMaxWidth',
			'ansel_max_height' => 'isNatural|validateMinMaxHeight',
			'ansel_ratio' => 'validateCropRatio',
			'ansel_show_title' => 'enum[y, n]',
			'ansel_require_title' => 'enum[y, n]',
			'ansel_show_caption' => 'enum[y, n]',
			'ansel_require_caption' => 'enum[y, n]',
			'ansel_show_cover' => 'enum[y, n]',
			'ansel_require_cover' => 'enum[y, n]'
		));

		$uploadDestinationsMenu = $this->uploadDestinationsMenu;

		// Define validate directory rule
		$validator->defineRule('validateDirectory', function ($key, $val) use (
			$uploadDestinationsMenu
		) {
			// Get upload destinations
			$destinations = $uploadDestinationsMenu->getMenu();

			// Set validated variable
			$validated = false;

			// Iterate through destinations and find a match
			foreach ($destinations as $key => $intermediate) {
				// Make sure key is set
				if (! $key) {
					continue;
				}

				// Iterate through intermediate
				foreach ($intermediate as $id => $name) {
					// Check for a match
					if ($val === $id) {
						// If there is a match, this is a valid directory
						$validated = true;

						// Break the loop
						break;
					}
				}

				// If we're validated, we can break the loop
				if ($validated) {
					break;
				}
			}

			// Return the validated boolean
			return $validated;
		});

		// Define validateMinMaxQty
		$validator->defineRule('validateMinMaxQty', function ($key, $val) use ($data) {
			// Make sure number is positive integer
			if ($val < 0) {
				return lang('not_negative_number');
			}

			// Get min qty if set
			$minQty = isset($data['ansel_min_qty']) ?
				$data['ansel_min_qty'] : null;

			// Get max qty if set
			$maxQty = isset($data['ansel_max_qty']) ?
				$data['ansel_max_qty'] : null;

			// Make sure max is not less than min
			if (($minQty && $maxQty) && ($minQty > $maxQty)) {
				return lang('max_not_less_than_min');
			}

			return true;
		});

		// Define validate min/max width rule
		$validator->defineRule('validateMinMaxWidth', function () use ($data) {
			// Get min width if set
			$minWidth = (int) isset($data['ansel_min_width']) ?
				$data['ansel_min_width'] : null;

			// Get max width if set
			$maxWidth = (int) isset($data['ansel_max_width']) ?
				$data['ansel_max_width'] : null;

			// If min and max width are both defined and min width is greater
			// than max width, we have a problem
			if (($minWidth && $maxWidth) && ($minWidth > $maxWidth)) {
				return lang('min_width_cannot_be_greater_than_max_width');
			}

			// We can return validated at this point
			return true;
		});

		// Define validate min/max height rule
		$validator->defineRule('validateMinMaxHeight', function () use ($data) {
			// Get min height if set
			$minHeight = (int) isset($data['ansel_min_height']) ?
				$data['ansel_min_height'] : null;

			// Get max height if set
			$maxHeight = (int) isset($data['ansel_max_height']) ?
				$data['ansel_max_height'] : null;

			// If min and max height are both defined and min height is greater
			// than max height, we have a problem
			if (($minHeight && $maxHeight) && ($minHeight > $maxHeight)) {
				return lang('min_height_cannot_be_greater_than_max_height');
			}

			// We can return validated at this point
			return true;
		});

		// Define crop rule validation
		$validator->defineRule('validateCropRatio', function ($key, $val) {
			// The ratio should be (int):(int), explode to check
			$parts = explode(':', $val);

			// Make sure there are two parts
			if (count($parts) !== 2) {
				return lang('specify_crop_width_height');
			}

			// Make sure each part is a natural number
			foreach ($parts as $part) {
				if (! is_numeric($part)) {
					return lang('specify_crop_width_height');
				}
			}

			// We can return validated at this point
			return true;
		});

		// Return validation result
		return $validator->validate($data);
	}

	/**
	 * Save field settings
	 *
	 * @return array
	 */
	public function save()
	{
		// Get array data
		$data = $this->fieldSettings->toArray();

		// Make sure field is wide
		$data['field_wide'] = true;

		// Return array data
		return $data;
	}
}
