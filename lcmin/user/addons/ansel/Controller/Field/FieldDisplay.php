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

use BoldMinded\Ansel\Service\LivePreviewCleaner;
use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BoldMinded\Ansel\Service\GlobalSettings;
use BoldMinded\Ansel\Model\FieldSettings as FieldSettingsModel;
use ExpressionEngine\Service\View\ViewFactory;
use BoldMinded\Ansel\Service\UploadKeys;
use BoldMinded\Ansel\Service\Sources\SourceRouter;
use ExpressionEngine\Service\Model\Collection;

/**
 * Class FieldDisplay
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class FieldDisplay
{
    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

    /**
     * @var Collection $collection
     */
    private $collection;

    /**
     * @var GlobalSettings $globalSettings
     */
    private $globalSettings;

    /**
     * @var FieldSettingsModel $fieldSettings
     */
    protected $fieldSettings;

    /**
     * @var ViewFactory $viewFactory
     */
    protected $viewFactory;

    /**
     * @var UploadKeys $uploadKeys
     */
    protected $uploadKeys;

    /**
     * @var SourceRouter $sourceRouter
     */
    protected $sourceRouter;

    /**
     * @var int $siteId
     */
    protected $siteId;

    /**
     * @var bool $assetsSource
     */
    protected $isCP;

    /**
     * Constructor
     *
     * @param RecordBuilder $recordBuilder
     * @param Collection $collection
     * @param GlobalSettings $globalSettings
     * @param FieldSettingsModel $fieldSettings
     * @param ViewFactory $viewFactory
     * @param UploadKeys $uploadKeys
     * @param SourceRouter $sourceRouter
     * @param int $siteId
     * @param bool $isCP
     * @param array $rawFieldSettings
     */
    public function __construct(
        RecordBuilder $recordBuilder,
        Collection $collection,
        GlobalSettings $globalSettings,
        FieldSettingsModel $fieldSettings,
        ViewFactory $viewFactory,
        UploadKeys $uploadKeys,
        SourceRouter $sourceRouter,
        $siteId = 1,
        $isCP = true,
        $rawFieldSettings = array()
    ) {
        // Populate the model
        $fieldSettings->set($rawFieldSettings);

        // Inject dependencies
        $this->recordBuilder = $recordBuilder;
        $this->collection = $collection;
        $this->globalSettings = $globalSettings;
        $this->fieldSettings = $fieldSettings;
        $this->viewFactory = $viewFactory;
        $this->uploadKeys = $uploadKeys;
        $this->sourceRouter = $sourceRouter;
        $this->siteId = $siteId;
        $this->isCP = $isCP;
    }

    /**
     * Get method
     *
     * @param int $contentId
     * @param int $rowId
     * @param int $colId
     * @param array $postBackData
     * @return mixed
     */
    public function get(
        $contentId = null,
        $rowId = null,
        $colId = null,
        $postBackData = array()
    ) {
        // Create lang array
        $langArray = array(
            'drag_images_to_upload',
            'browser_does_not_support_drag_and_drop',
            'please_use_fallback_form',
            'file_too_big',
            'invalid_file_type',
            'cancel_upload',
            'cancel_upload_confirmation',
            'remove_file',
            'you_cannot_upload_any_more_files',
            'min_image_dimensions_not_met',
            'must_add_1_image',
            'must_add_qty_images',
            'must_add_1_more_image',
            'must_add_qty_more_images',
            'field_over_limit_1',
            'field_over_limit_qty',
            'file_is_not_an_image',
            'source_image_missing'
        );

        // Populate lang array
        $populatedLang = array();
        foreach ($langArray as $key) {
            $populatedLang[$key] = lang($key);
        }

        // Replace straight quotes with placeholders
        foreach ($populatedLang as $key => $val) {
            $populatedLang[$key] = str_replace(
                '\'',
                '{{quotePlaceholder}}',
                $val
            );
        }

        // Retinize the model
        $this->fieldSettings->retinizeReturnValues();

        // Get specific min requirements
        $translate = false;
        if ($this->fieldSettings->min_width && $this->fieldSettings->min_height) {
            $translate = 'min_image_dimensions_not_met_width_and_height';
        } elseif ($this->fieldSettings->min_width) {
            $translate = 'min_image_dimensions_not_met_width_only';
        } elseif ($this->fieldSettings->min_height) {
            $translate = 'min_image_dimensions_not_met_height_only';
        }

        // Check if we should translate
        if ($translate) {
            $populatedLang['min_image_dimensions_not_met'] = str_replace(
                array(
                    '{{minWidth}}',
                    '{{minHeight}}'
                ),
                array(
                    $this->fieldSettings->min_width,
                    $this->fieldSettings->min_height
                ),
                lang($translate)
            );
        }

        // Get the upload directory type
        $uploadDirectory = $this->fieldSettings->getUploadDirectory();
        $saveDirectory = $this->fieldSettings->getSaveDirectory();

        // Get the file chooser link
        $fileChooserLink = '';

        if (!$uploadDirectory) {
            return ee('CP/Alert')->makeInline('ansel-field-alerts')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('no_upload_directory'))
                ->addToBody(lang('no_upload_directory_desc'))
                ->render();
        }

        if (!$saveDirectory) {
            return ee('CP/Alert')->makeInline('ansel-field-alerts')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('no_save_directory'))
                ->addToBody(lang('no_save_directory_desc'))
                ->render();
        }

        // Alert those upgrading to v3 who might not be aware
        if ($uploadDirectory->type !== 'ee') {
            return ee('CP/Alert')->makeInline('ansel-field-alerts')
                ->asIssue()
                ->cannotClose()
                ->withTitle(lang('invalid_source'))
                ->addToBody(sprintf(lang('invalid_source_desc'), $uploadDirectory->type))
                ->render();
        }

        if ($this->isCP) {
            // Set the source type on the source router
            $this->sourceRouter->setSource($uploadDirectory->type);

            // Get the file chooser link
            $fileChooserLink = $this->sourceRouter->getFileChooserLink(
                $this->fieldSettings->getUploadDirectory()
            );
        }

        $this->checkSymLinks();

        // Check if we have postback data
        if ($postBackData) {
            // Unset the placeholder
            unset($postBackData['placeholder']);

            // Property map
            $propMap = array(
                'ansel_image_id' => 'id',
                'ansel_image_delete' => '_delete',
                'source_file_id' => 'original_file_id',
                'original_location_type' => 'original_location_type',
                'upload_location_id' => 'upload_location_id',
                'upload_location_type' => 'upload_location_type',
                'filename' => 'filename',
                'extension' => 'extension',
                'file_location' => '_file_location',
                'x' => 'x',
                'y' => 'y',
                'width' => 'width',
                'height' => 'height',
                'order' => 'position',
                'title' => 'title',
                'description' => 'description',
                'cover' => 'cover'
            );

            // Create an array for the records
            $recordArray = array();

            // Iterate over data
            foreach ($postBackData as $data) {
                // Make a record
                $anselRecord = $this->recordBuilder->make('ansel:Image');

                // Set properties
                foreach ($data as $key => $val) {
                    $anselRecord->{$propMap[$key]} = $val;
                }

                // Add the record to the array
                $recordArray[] = $anselRecord;
            }

            // Add items to the collection
            $this->collection->__construct($recordArray);

            // Set rows
            $rows = $this->collection;
        } else {
            // Get row query builder
            $query = $this->recordBuilder->get('ansel:Image');

            // Filter the query builder
            $query->filter('site_id', $this->siteId);
            $query->filter('content_id', $contentId);
            $query->filter('field_id', $this->fieldSettings->field_id);
            $query->filter('content_type', $this->fieldSettings->type);

            // Filter by row ID if applicable
            if ($rowId && $colId) {
                $query->filter('row_id', $rowId);
                $query->filter('col_id', $colId);
            } else {
                $query->filter('row_id', 'IN', array(
                    0,
                    ''
                ));
                $query->filter('col_id', 'IN', array(
                    0,
                    ''
                ));
            }

            // Order the query builder
            $query->order('position', 'asc');

            /** @var Collection $rows */
            if (ee()->extensions->active_hook('ansel_display_field_get_rows')) {
                $rows = ee()->extensions->call(
                    'ansel_display_field_get_rows',
                    $query,
                    $this->fieldSettings->getValues(),
                    $contentId,
                    $rowId,
                    $colId
                );
            } else {
                $rows = $query->all();
            }
        }

        // Ensure all images in Ansel still exist on the filesystem.
        // If someone intentionally or mistakenly removed something from the File Manager that was assigned
        // to this field errors will ensue, so remove it and notify the user.
        $anselFileIds = $rows->pluck('original_file_id');
        $eeFileIds = $this->recordBuilder->get('File')
            ->filter('file_id', 'IN', $anselFileIds)
            ->all()
            ->pluck('file_id');

        if (count($anselFileIds) !== count($eeFileIds)) {
            $removedNames = [];

            /** @var Collection $row */
            foreach ($rows as $row) {
                if (!in_array($row->original_file_id, $eeFileIds)) {
                    // if it's removed, it can't be deleted :(
                    // $rows->removeElement($row);
                    $removedNames[] = $row->filename;
                }
            }

            if (!empty($removedNames)) {
                ee('CP/Alert')->makeInline(sprintf(
                    'ansel-field-alerts-%s-%s',
                    $this->fieldSettings->type,
                    $this->fieldSettings->field_id
                ))
                    ->asWarning()
                    ->cannotClose()
                    ->withTitle(lang('missing_images_title'))
                    ->addToBody(lang('missing_images_desc'))
                    ->addToBody($removedNames)
                    ->now();
            }
        }

        $shouldShowTile = $this->fieldSettings->tile_view ?? false;
        $shouldShowTileMetaFields = false;
        $singleImageDisplay = false;
        $shouldSort = true;

        // Make sure at least one of the fields is enabled. If not don't show any.
        if (
            $shouldShowTile &&
            (
                $this->fieldSettings->show_title ||
                $this->fieldSettings->show_description ||
                $this->fieldSettings->show_cover
            )
        ) {
            $shouldShowTileMetaFields = true;
        }

        if (
            $shouldShowTileMetaFields &&
            $this->fieldSettings->max_qty === 1 &&
            $this->fieldSettings->prevent_upload_over_max
        ) {
            $singleImageDisplay = true;
        }

        if (
            $this->fieldSettings->max_qty === 1 &&
            $this->fieldSettings->prevent_upload_over_max
        ) {
            $shouldSort = false;
        }


        $fieldSettingsArray = $this->fieldSettings->toArray(
            true,
            false
        );

        $fieldSettingsArray['sync_meta_fields'] = bool_config_item('ansel_sync_meta_fields');

        return $this->viewFactory->make('ansel:Field/Field')
            ->render(array(
                'langArray' => $populatedLang,
                'fieldSettings' => $this->fieldSettings,
                'fieldSettingsArray' => $fieldSettingsArray,
                'uploadKey' => $this->uploadKeys->createNew(),
                'uploadUrl' => $this->uploadKeys->getUploadUrl(),
                'fileChooserLink' => $fileChooserLink,
                'rows' => $rows,
                'prefix' => '',
                'shouldShowTile' => $shouldShowTile,
                'shouldShowTileMetaFields' => $shouldShowTileMetaFields,
                'singleImageDisplay' => $singleImageDisplay,
                'shouldSort' => $shouldSort,
                'shouldShowMetaButton' => bool_config_item('ansel_sync_meta_fields'),
            ));
    }

    private function checkSymLinks(): void
    {
        if (bool_config_item('ansel_ignore_symlinks')) {
            return;
        }

        $uploadDirectory = $this->fieldSettings->getUploadDirectory();
        $saveDirectory = $this->fieldSettings->getSaveDirectory();
        $previewDirectory = $this->fieldSettings->getPreviewDirectory();

        if ($uploadDirectory && $this->sourceRouter->isSymLink($uploadDirectory)) {
            $this->showSymLinkAlert('Upload');
        }

        if ($saveDirectory && $this->sourceRouter->isSymLink($saveDirectory)) {
            $this->showSymLinkAlert('Save');
        }

        if ($previewDirectory && $this->sourceRouter->isSymLink($previewDirectory)) {
            $this->showSymLinkAlert('Preview');
        }
    }

    private function showSymLinkAlert(string $directoryName): void
    {
        ee('CP/Alert')->makeInline('ansel-field-alerts')
            ->asWarning()
            ->cannotClose()
            ->withTitle(lang('directory_is_symlink'))
            ->addToBody(sprintf(lang('directory_is_symlink_desc'), $directoryName))
            ->now();
    }
}
