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

namespace BoldMinded\Ansel\Controller\Field;

use BoldMinded\Ansel\Service\GlobalSettings;
use BoldMinded\Ansel\Service\UploadDestinationsMenu;
use BoldMinded\Ansel\Traits\FileUploadDestinations;
use ExpressionEngine\Service\Validation\Factory as ValidationFactory;
use BoldMinded\Ansel\Model\FieldSettings as FieldSettingsModel;

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
    use FileUploadDestinations;

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
        // Check if we should display upload/save note
        $uploadSaveExplain = '';

        if (! $this->globalSettings->hide_source_save_instructions) {
            $message = ee('CP/Alert')->makeInline()
                ->asTip()
                ->cannotClose()
                ->addToBody(lang('upload_save_dir_explain_upload'))
                ->addToBody('&nbsp;')
                ->addToBody(lang('upload_save_dir_explain_save'))
                ->addToBody('&nbsp;')
                ->addToBody(lang('upload_save_dir_explain_different_sources'))
                ->addToBody('&nbsp;')
                ->addToBody(lang('upload_preview_dir_explain'))
                ->render();

            $uploadSaveExplain = array(
                'title' => 'upload_save_dir_explanation',
                'desc' => 'upload_save_dir_hide',
                'fields' => array(
                    'ansel_upload_save_explain' => array(
                        'type' => 'html',
                        'content' => $message,
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
        $forceWebp = $this->globalSettings->default_webp ? 'y' : 'n';
        if ($this->fieldSettings->field_id) {
            $forceWebp = $this->fieldSettings->force_webp ? 'y' : 'n';
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
        $showDescription = $this->globalSettings->default_show_description ? 'y' : 'n';
        if ($this->fieldSettings->field_id) {
            $showDescription = $this->fieldSettings->show_description ? 'y' : 'n';
        }

        // Check for display title field or use default
        $requireDescription = $this->globalSettings->default_require_description ? 'y' : 'n';
        if ($this->fieldSettings->field_id) {
            $requireDescription = $this->fieldSettings->require_description ? 'y' : 'n';
        }

        // Check for customize title label or use default
        $customizeDescriptionLabel = $this->globalSettings->default_description_label;
        if ($this->fieldSettings->field_id) {
            $customizeDescriptionLabel = $this->fieldSettings->description_label;
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

        // Add new images to the beginning of list instead of end
        $prependToTable = $this->globalSettings->default_prepend_to_table ? 'y' : 'n';
        if ($this->fieldSettings->field_id) {
            $prependToTable = $this->fieldSettings->prepend_to_table ? 'y' : 'n';
        }

        // Use the new condensed grid layout
        $gridDisplay = $this->globalSettings->tile_view ?? 'y';
        if ($this->fieldSettings->field_id) {
            $gridDisplay = $this->fieldSettings->tile_view ? 'y' : 'n';
        }

        // Set field name wrapper
        $wrapper1 = '';
        $wrapper2 = '';
        if (in_array($this->fieldSettings->type, ['lowVar', 'proVar'])) {
            $wrapper1 = 'variable_settings[ansel][';
            $wrapper2 = ']';
        }

        return [
            /**
             * Upload/Save Explanation
             */
            'ansel_upload_save_explain' => $uploadSaveExplain,

            /**
             * Upload Directory
             */
            'ansel_upload_directory' => [
                'title' => 'upload_directory',
                'desc' => 'upload_directory_explain',
                'fields' => [
                    "{$wrapper1}ansel_upload_directory{$wrapper2}" => [
                        'type' => 'html',
                        'required' => true,
                        'content' => $this->buildFileUploadDropdown(
                            "{$wrapper1}ansel_upload_directory{$wrapper2}",
                            $this->fieldSettings->upload_directory
                        ),
                    ]
                ]
            ],

            /**
             * Save Directory
             */
            'ansel_save_directory' => [
                'title' => 'save_directory',
                'desc' => 'save_directory_explain',
                'fields' => [
                    "{$wrapper1}ansel_save_directory{$wrapper2}" => [
                        'type' => 'html',
                        'required' => true,
                        'content' => $this->buildFileUploadDropdown(
                            "{$wrapper1}ansel_save_directory{$wrapper2}",
                            $this->fieldSettings->save_directory
                        ),
                    ]
                ]
            ],

            /**
             * Live Preview Directory
             */
            'ansel_preview_directory' => [
                'title' => 'preview_directory',
                'desc' => 'preview_directory_explain',
                'fields' => [
                    "{$wrapper1}ansel_preview_directory{$wrapper2}" => [
                        'type' => 'html',
                        'required' => false,
                        'content' => $this->buildFileUploadDropdown(
                            "{$wrapper1}ansel_preview_directory{$wrapper2}",
                            $this->fieldSettings->preview_directory
                        ),
                    ]
                ]
            ],

            /**
             * Tile View
             */
            'ansel_tile_view' => [
                'title' => 'tile_view',
                'desc' => 'tile_view_explain',
                'fields' => [
                    "{$wrapper1}ansel_tile_view{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $gridDisplay
                    ]
                ]
            ],

            /**
             * Minimum Quantity
             */
            'ansel_min_qty' => [
                'title' => 'min_quantity',
                'desc' => 'optional',
                'fields' => [
                    "{$wrapper1}ansel_min_qty{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_min_qty{$wrapper2}",
                            'type' => 'number',
                            'min' => 0,
                            'placeholder' => '&infin;',
                            'id' => 'ansel_min_qty',
                            'value' => $this->fieldSettings->min_qty ?: ''
                        ])
                    ]
                ]
            ],

            /**
             * Maximum Quantity
             */
            'ansel_max_qty' => [
                'title' => 'max_quantity',
                'desc' => 'optional',
                'fields' => [
                    "{$wrapper1}ansel_max_qty{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_max_qty{$wrapper2}",
                            'type' => 'number',
                            'min' => 0,
                            'placeholder' => '&infin;',
                            'id' => 'ansel_max_qty',
                            'value' => $maxQuantity
                        ])
                    ]
                ]
            ],

            /**
             * Force JPEG
             */
            'ansel_prevent_upload_over_max' => [
                'title' => 'prevent_upload_over_max',
                'desc' => 'prevent_upload_over_max_explain',
                'fields' => [
                    "{$wrapper1}ansel_prevent_upload_over_max{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $this->fieldSettings->prevent_upload_over_max ? 'y': 'n'
                    ]
                ]
            ],

            /**
             * Image Quality
             */
            'ansel_quality' => [
                'title' => 'image_quality',
                'desc' => 'specify_jpeg_image_quality',
                'fields' => [
                    "{$wrapper1}ansel_quality{$wrapper2}" => [
                        'type' => 'html',
                        'required' => true,
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_quality{$wrapper2}",
                            'type' => 'number',
                            'min' => 0,
                            'max' => 100,
                            'maxlength' => 3,
                            'id' => 'ansel_quality',
                            'value' => $imgQuality
                        ])
                    ]
                ]
            ],

            /**
             * Force JPEG
             */
            'ansel_force_jpg' => [
                'title' => 'force_jpeg',
                'desc' => 'force_jpeg_explain',
                'fields' => [
                    "{$wrapper1}ansel_force_jpg{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $forceJpg,
                    ]
                ]
            ],

            /**
             * Force WebP
             */
            'ansel_force_webp' => [
                'title' => 'force_webp',
                'desc' => 'force_webp_explain',
                'fields' => [
                    "{$wrapper1}ansel_force_webp{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $forceWebp,
                    ]
                ]
            ],

            /**
             * Retina mode
             */
            'ansel_retina_mode' => [
                'title' => 'retina_mode',
                'desc' => 'retina_mode_explain',
                'fields' => [
                    "{$wrapper1}ansel_retina_mode{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $retinaMode
                    ]
                ]
            ],

            /**
             * Min Width
             */
            'ansel_min_width' => [
                'title' => 'min_width',
                'desc' => 'optional',
                'fields' => [
                    "{$wrapper1}ansel_min_width{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_min_width{$wrapper2}",
                            'type' => 'number',
                            'min' => 1,
                            'placeholder' => '&infin;',
                            'id' => 'ansel_min_width',
                            'value' => $this->fieldSettings->min_width ?: ''
                        ])
                    ]
                ]
            ],

            /**
             * Min Height
             */
            'ansel_min_height' => [
                'title' => 'min_height',
                'desc' => 'optional',
                'fields' => [
                    "{$wrapper1}ansel_min_height{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_min_height{$wrapper2}",
                            'type' => 'number',
                            'min' => 1,
                            'placeholder' => '&infin;',
                            'id' => 'ansel_min_height',
                            'value' => $this->fieldSettings->min_height ?: ''
                        ])
                    ]
                ]
            ],

            /**
             * Max Width
             */
            'ansel_max_width' => [
                'title' => 'max_width',
                'desc' => 'optional',
                'fields' => [
                    "{$wrapper1}ansel_max_width{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_max_width{$wrapper2}",
                            'type' => 'number',
                            'min' => 1,
                            'placeholder' => '&infin;',
                            'id' => 'ansel_max_width',
                            'value' => $this->fieldSettings->max_width ?: ''
                        ])
                    ]
                ]
            ],

            /**
             * Max Height
             */
            'ansel_max_height' => [
                'title' => 'max_height',
                'desc' => 'optional',
                'fields' => [
                    "{$wrapper1}ansel_max_height{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_max_height{$wrapper2}",
                            'type' => 'number',
                            'min' => 1,
                            'placeholder' => '&infin;',
                            'id' => 'ansel_max_height',
                            'value' => $this->fieldSettings->max_height ?: ''
                        ])
                    ]
                ]
            ],

            /**
             * Crop Ratio
             */
            'ansel_ratio' => [
                'title' => 'crop_ratio',
                'desc' => 'crop_ratio_explain',
                'fields' => [
                    "{$wrapper1}ansel_ratio{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_ratio{$wrapper2}",
                            'type' => 'text',
                            'placeholder' => lang('eg_16_9'),
                            'id' => 'ansel_ratio',
                            'value' => $this->fieldSettings->ratio
                        ])
                    ]
                ]
            ],

            /**
             * Display title field
             */
            'ansel_prepend_to_table' => [
                'title' => 'prepend_to_table',
                'desc' => 'prepend_to_table_explain',
                'fields' => [
                    "{$wrapper1}ansel_prepend_to_table{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $prependToTable
                    ]
                ]
            ],

            /**
             * Display title field
             */
            'ansel_show_title' => [
                'title' => 'display_title_field',
                'fields' => [
                    "{$wrapper1}ansel_show_title{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $showTitle
                    ]
                ]
            ],

            /**
             * Require title field
             */
            'ansel_require_title' => [
                'title' => 'require_title_field',
                'fields' => [
                    "{$wrapper1}ansel_require_title{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $requireTitle
                    ]
                ]
            ],

            /**
             * Customize title field label
             */
            'ansel_title_label' => [
                'title' => 'customize_title_label',
                'fields' => [
                    "{$wrapper1}ansel_title_label{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_title_label{$wrapper2}",
                            'type' => 'text',
                            'placeholder' => lang('eg_alt_text'),
                            'id' => 'ansel_title_label',
                            'value' => $customizeTitleLabel
                        ])
                    ]
                ]
            ],

            /**
             * Display description field
             */
            'ansel_show_description' => [
                'title' => 'display_description_field',
                'fields' => [
                    "{$wrapper1}ansel_show_description{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $showDescription
                    ]
                ]
            ],

            /**
             * Require description field
             */
            'ansel_require_description' => [
                'title' => 'require_description_field',
                'fields' => [
                    "{$wrapper1}ansel_require_description{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $requireDescription
                    ]
                ]
            ],

            /**
             * Customize description field label
             */
            'ansel_description_label' => [
                'title' => 'customize_description_label',
                'fields' => [
                    "{$wrapper1}ansel_description_label{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_description_label{$wrapper2}",
                            'type' => 'text',
                            'placeholder' => lang('eg_image_description'),
                            'id' => 'ansel_description_label',
                            'value' => $customizeDescriptionLabel
                        ])
                    ]
                ]
            ],

            /**
             * Display cover field
             */
            'ansel_show_cover' => [
                'title' => 'display_cover_field',
                'fields' => [
                    "{$wrapper1}ansel_show_cover{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $showCover
                    ]
                ]
            ],

            /**
             * Require cover field
             */
            'ansel_require_cover' => [
                'title' => 'require_cover_field',
                'fields' => [
                    "{$wrapper1}ansel_require_cover{$wrapper2}" => [
                        'type' => 'yes_no',
                        'value' => $requireCover
                    ]
                ]
            ],

            /**
             * Customize cover field label
             */
            'ansel_cover_label' => [
                'title' => 'customize_cover_label',
                'fields' => [
                    "{$wrapper1}ansel_cover_label{$wrapper2}" => [
                        'type' => 'html',
                        'content' => form_input([
                            'name' => "{$wrapper1}ansel_cover_label{$wrapper2}",
                            'type' => 'text',
                            'placeholder' => lang('eg_favorite'),
                            'id' => 'ansel_cover_label',
                            'value' => $customizeCoverLabel
                        ])
                    ]
                ]
            ]
        ];
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
        $validator = $this->validationFactory->make([
            'ansel_upload_directory' => 'required|validateDirectory|validateUniqueDirectory',
            'ansel_save_directory' => 'required|validateDirectory|validateUniqueDirectory',
            'ansel_preview_directory' => 'validateDirectory|validateUniqueDirectory',
            'ansel_min_qty' => 'validateMinMaxQty',
            'ansel_max_qty' => 'validateMinMaxQty',
            'ansel_quality' => 'required|isNaturalNoZero|lessThan[101]',
            'ansel_force_jpg' => 'enum[y, n]',
            'ansel_force_webp' => 'enum[y, n]',
            'ansel_retina_mode' => 'enum[y, n]',
            'ansel_min_width' => 'isNatural|validateMinMaxWidth',
            'ansel_min_height' => 'isNatural|validateMinMaxHeight',
            'ansel_max_width' => 'isNatural|validateMinMaxWidth',
            'ansel_max_height' => 'isNatural|validateMinMaxHeight',
            'ansel_ratio' => 'validateCropRatio',
            'ansel_show_title' => 'enum[y, n]',
            'ansel_require_title' => 'enum[y, n]',
            'ansel_show_description' => 'enum[y, n]',
            'ansel_require_description' => 'enum[y, n]',
            'ansel_show_cover' => 'enum[y, n]',
            'ansel_require_cover' => 'enum[y, n]'
        ]);

        // Define validate directory rule
        $validator->defineRule('validateDirectory', function ($key, $val) {
            // Get upload destinations
            $uploadDirectoryIds = $this->getAllUploadDestinationIds();

            // Set validated variable
            $validated = false;

            // Iterate through destinations and find a match
            foreach ($uploadDirectoryIds as $id) {
                // Check for a match
                if ($val == $id) {
                    // If there is a match, this is a valid directory
                    $validated = true;

                    // Break the loop
                    break;
                }

                if (
                    $key === 'ansel_preview_directory'
                    && $val == 0
                ) {
                    $validated = true;

                    break;
                }
            }

            if (!$validated) {
                return 'Invalid directory.';
            }

            return true;
        });

        $validator->defineRule('validateUniqueDirectory', function ($key, $val) use ($data) {
            foreach ([
                'ansel_upload_directory',
                'ansel_save_directory',
                'ansel_preview_directory'
            ] as $directoryName) {
                if ($key !== $directoryName && $val === $data[$directoryName]) {
                    return lang('unique_directory');
                }
            }

            return true;
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
