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

namespace BoldMinded\Ansel\Service\AnselImages;

use BoldMinded\Ansel\Model\File;
use BoldMinded\Ansel\Model\ImagesTagParams;
use BoldMinded\Ansel\Model\Source;
use BoldMinded\Ansel\Service\LivePreviewFactory;
use BoldMinded\Ansel\Service\Noop;
use EE_Logger;
use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use ExpressionEngine\Service\Model\Query\Builder as RecordQueryBuilder;
use BoldMinded\Ansel\Record\Image as ImageRecord;
use ExpressionEngine\Service\Model\Collection;
use BoldMinded\Ansel\Service\Sources\SourceRouter;
use BoldMinded\Ansel\Service\NamespaceVars;
use BoldMinded\Ansel\Service\GlobalSettings;
use BoldMinded\Ansel\Utility\RegEx;
use BoldMinded\Ansel\Model\File as FileModel;
use BoldMinded\Ansel\Model\Source as SourceModel;

/**
 * Class ImagesTag
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImagesTag
{
    private bool $queryBuilt = false;
    private ImagesTagParams $imagesTagParams;
    private RecordBuilder $recordBuilder;
    private RecordQueryBuilder $recordQuery;
    private array $internalTags = [];
    private SourceRouter $sourceRouter;
    private NamespaceVars $namespaceVars;
    private GlobalSettings $globalSettings;
    private InternalTag $internalTag;
    private LivePreviewFactory $livePreviewFactory;
    private EE_logger $logger;
    private \File_model $eeFileModel;
    private bool $isNativeTemplateEngine;
    private array $sources = [];
    private array $files = [];
    private string $host = '';

    public function __construct(
        RecordBuilder $recordBuilder,
        ImagesTagParams $imagesTagParams,
        SourceRouter $sourceRouter,
        NamespaceVars $namespaceVars,
        GlobalSettings $globalSettings,
        InternalTag $internalTag,
        LivePreviewFactory $livePreviewFactory,
        \File_model $eeFileModel,
        EE_Logger $logger,
        bool $isNativeTemplateEngine,
    ) {
        // Inject dependencies
        $this->recordBuilder = $recordBuilder;
        $this->imagesTagParams = $imagesTagParams;
        $this->sourceRouter = $sourceRouter;
        $this->namespaceVars = $namespaceVars;
        $this->globalSettings = $globalSettings;
        $this->internalTag = $internalTag;
        $this->livePreviewFactory =$livePreviewFactory;
        $this->eeFileModel = $eeFileModel;
        $this->logger = $logger;
        $this->isNativeTemplateEngine = $isNativeTemplateEngine;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(int $id): File | null
    {
        return $this->files['ee'][$id] ?? null;
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    public function getSource(int $id): SourceModel | null
    {
        return $this->sources['ee'][$id] ?? null;
    }

    public function getInternalTags(): array
    {
        return $this->internalTags;
    }

    /**
     * Populate tag params
     *
     * @param array $tagParams
     */
    public function populateTagParams($tagParams)
    {
        // Populate the tag params
        $this->imagesTagParams->populate($tagParams);

        // Build the query (we know we can now)
        $this->buildQuery();

        // Set the host (we know we can now)
        $this->setUpHost();
    }

    /**
     * Populate internal tags
     *
     * @param array $internalTags
     */
    public function populateInternalTags($internalTags)
    {
        $this->internalTags = $internalTags;
    }

    /**
     * Count
     *
     * @return int
     */
    public function count()
    {
        if (! $this->queryBuilt) {
            return null;
        }

        // Return count
        return $this->recordQuery->count();
    }

    /**
     * Get variables
     *
     * @return array
     */
    public function getVariables(
        array $fieldData = [],
        array $settings = [],
        array $tagParams = [],
    ) {
        if (! $this->queryBuilt) {
            return null;
        }

        if (!empty($fieldData) && ee('LivePreview')->hasEntryData()) {
            $records = $this->livePreviewFactory->create($fieldData, $settings);
        } else {
            if (ee()->extensions->active_hook('ansel_replace_tag_get_rows')) {
                $records = ee()->extensions->call(
                    'ansel_replace_tag_get_rows',
                    $this->recordQuery,
                    $settings,
                    $fieldData,
                    $tagParams
                );
            } else {
                // Get all records
                $records = $this->recordQuery->all();
            }
        }

        // Check if we have results
        if (! $records->count()) {
            return [];
        }

        // We have to do a bunch of special stuff if random is requested because
        // EE models don't support random
        if ($this->imagesTagParams->random) {
            // Get an array of the records
            $array = $records->asArray();

            // Shuffle the array
            shuffle($array);

            // If there is a limit, get a slice of the array
            if ($this->imagesTagParams->limit) {
                $array = array_slice($array, 0, $this->imagesTagParams->limit);
            }

            // Re-set the collection
            $records->__construct($array);
        }

        // Start a variable array
        $vars = [];

        // Run bulk queries of other items we need for efficiency
        $this->runBulkQueries($records);

        $anselFileIds = $records->pluck('file_id');
        $eeFileIds = $this->recordBuilder->get('File')
            ->filter('file_id', 'IN', $anselFileIds)
            ->all()
            ->pluck('file_id');

        // Iterate through records
        foreach ($records as $record) {
            if (!in_array($record->file_id, $eeFileIds)) {
                $this->logger->developer(
                    sprintf(
                        lang('missing_images_log'),
                        $record->content_id,
                        $record->file_id,
                        $record->filename,
                    ),
                );
                continue;
            }

            $vars[] = $this->setVariablesFromRecord($record);
        }

        // Set index/count/total_results
        $index = 0;
        $totalResults = count($vars);
        foreach ($vars as $key => $val) {
            $val["index"] = $index;
            $val["count"] = $index + 1;
            $val["total_results"] = $totalResults;
            $vars[$key] = $val;
            $index++;
        }

        // Only add namespace prefix if it's the native parser
        if ($this->isNativeTemplateEngine) {
            // Namespace vars
            $vars = $this->namespaceVars->namespaceSet(
                $vars,
                $this->imagesTagParams->namespace
            );
        }

        return $vars;
    }

    /**
     * Run build queries for efficiency
     *
     * @param Collection $records
     */
    private function runBulkQueries(Collection $records)
    {
        $locations = [];
        $files = [];

        // Iterate through records and get files IDs we need for queries
        foreach ($records as $record) {
            $uploadLocType = $record->upload_location_type;
            $uploadFileId = $record->file_id;
            $origLocType = $record->original_location_type;
            $origFileId = $record->original_file_id;

            // Add the upload file to the files query
            $files[$uploadLocType][$record->file_id] = $uploadFileId;
            $files[$origLocType][$record->original_file_id] = $origFileId;
        }

        // Files bulk query
        foreach ($files as $type => $ids) {
            // Set the source type
            $this->sourceRouter->setSource($type);

            // Get the file models
            $this->files[$type] = $this->sourceRouter->getFileModels(
                array_values($ids)
            );
        }

        // Iterate through files and get locations to bulk query
        foreach ($this->files as $locType => $fileModels) {
            foreach ($fileModels as $fileModel) {
                $locId = $fileModel->location_identifier;
                $locations[$locType][$locId] = $locId;
            }
        }

        // Locations bulk query
        foreach ($locations as $type => $ids) {
            // Set the source type
            $this->sourceRouter->setSource($type);

            // Get the source models
            $this->sources[$type] = $this->sourceRouter->getSourceModels(
                array_values($ids)
            );
        }
    }

    /**
     * Set variables from record
     *
     * @param ImageRecord $record
     * @return array
     */
    private function setVariablesFromRecord(ImageRecord $record)
    {
        $fileLocationType = $record->upload_location_type;
        $fileLocationId = $record->upload_location_id;
        $fileDirectoryId = $record->directory_id ?? 0;
        $fileId = $record->file_id;

        /** @var FileModel $file */
        $file = isset($this->files[$fileLocationType][$fileId]) ?
            $this->files[$fileLocationType][$fileId] :
            new Noop();

        /** @var SourceModel $fileSource */
        $fileSource = isset($this->sources[$fileLocationType][$fileLocationId]) ?
            $this->sources[$fileLocationType][$fileLocationId] :
            new Noop();

        $uploadLocationType = $record->original_location_type;
        $uploadFileId = $record->original_file_id;

        /** @var FileModel $originalFile */
        $originalFile = isset($this->files[$uploadLocationType][$uploadFileId]) ?
            $this->files[$uploadLocationType][$uploadFileId] :
            new Noop();
        $origLocId = $originalFile->location_identifier;

        /** @var SourceModel $originalSource */
        $originalSource = isset($this->sources[$uploadLocationType][$origLocId]) ?
            $this->sources[$uploadLocationType][$origLocId] :
             new Noop();

        // Process host if set
        if ($this->host) {
            $fileSource->url = preg_replace(
                RegEx::host(),
                '',
                $fileSource->url
            );
            $fileSource->url = $this->host . ltrim($fileSource->url, '/');

            $originalSource->url = preg_replace(
                RegEx::host(),
                '',
                $fileSource->url
            );
            $originalSource->url = $this->host . ltrim($originalSource->url, '/');
        } else {
            $fileSource->url = $record->getSourceUrl();
            $fileSource->path = $record->getSourcePath();
        }

        // @todo could we optimize this and move it into the bulkQueries?
        $fileSystemEntity = ee('Model')->get('FileSystemEntity', $record->file_id)->first();

        $vars = [
            'id' => $record->id,
            'site_id' => $record->site_id,
            'content_id' => $record->content_id,
            'field_id' => $record->field_id,
            'content_type' => $record->content_type,
            'row_id' => $record->row_id,
            'col_id' => $record->col_id,
            'file_id' => $record->file_id,
            'original_file_id' => $record->original_file_id,
            'upload_location_id' => $record->upload_location_id,
            'filename' => $record->filename,
            'basename' => $record->getBasename(),
            'extension' => $record->extension,
            'file_size' => $record->filesize,
            'filesize' => $record->filesize,
            'width' => $record->width,
            'height' => $record->height,
            'title' => $record->title,
            'description' => $record->description,
            'caption' => $record->description, // Duplicate for pre-3.0 installs so template tags don't break.
            'member_id' => $record->member_id,
            'position' => $record->position,
            'cover' => $record->cover,
            'upload_date' => $record->upload_date,
            'modify_date' => $record->modify_date,
            'modified_date' => $record->modify_date,
            'original_filename' => $originalFile->filename,
            'original_basename' => $originalFile->basename,
            'original_extension' => $originalFile->extension,
            'original_filesize' => $originalFile->filesize,
            'path' => $fileSystemEntity->getAbsolutePath(),
            'original_path' => "{$originalSource->path}{$originalFile->basename}",
            'url' => $fileSystemEntity->getAbsoluteURL(),
            'original_url' => "{$originalSource->url}{$originalFile->getUrlSafeParam('basename')}",
            'thumbnail_path' => $fileSystemEntity->getAbsoluteThumbnailPath(),
            'thumbnail_url' => $fileSystemEntity->getAbsoluteThumbnailURL(),
            'description_field' => $file->file_description,
            'credit_field' => $file->file_credit,
            'location_field' => $file->file_location,
            'original_title' => $originalFile->title,
            'original_title_field' => $originalFile->title,
            'original_description' => $originalFile->file_description,
            'original_description_field' => $originalFile->file_description,
            'original_credit' => $originalFile->file_credit,
            'original_credit_field' => $originalFile->file_credit,
            'original_location' => $originalFile->file_location,
            'original_location_field' => $originalFile->file_location,
            'host' => $this->host,
        ];

        // Process internal tag variables
        $internalTagVars = $this->internalTag->processTags(
            $file,
            $fileSource,
            $this->internalTags,
            90
        );

        // Merge the variables
        $vars = array_merge($vars, $internalTagVars);

        // Go ahead and return vars if this is not EE
        if ($fileLocationType !== 'ee' ||
            ! $this->imagesTagParams->manipulations
        ) {
            return $vars;
        }

        // Get image manipulations
        $manipulations = $this->getManipulation($fileLocationId);

        // Iterate through manipulations and set
        foreach ($manipulations as $manipulation) {
            // Set manipulation file path
            $path = "{$fileSource->path}_{$manipulation->short_name}/";
            $path .= $file->basename;
            $vars["{$manipulation->short_name}:path"] = $path;

            // Set manipulation url
            $url = "{$fileSource->url}_{$manipulation->short_name}/";
            $url .= $file->basename;
            $vars["{$manipulation->short_name}:url"] = $url;

            // Set manipulations dimensions
            $vars["{$manipulation->short_name}:width"] = $manipulation->width;
            $vars["{$manipulation->short_name}:height"] = $manipulation->height;

            // Set other manipulation properties
            $vars["{$manipulation->short_name}:id"] = $manipulation->id;
            $vars["{$manipulation->short_name}:site_id"] = $manipulation->site_id;
            $vars["{$manipulation->short_name}:upload_location_id"] = $manipulation->upload_location_id;
            $vars["{$manipulation->short_name}:title"] = $manipulation->title;
            $vars["{$manipulation->short_name}:resize_type"] = $manipulation->resize_type;
            $vars["{$manipulation->short_name}:watermark_id"] = $manipulation->watermark_id;
        }

        return $vars;
    }

    /**
     * Build query
     */
    private function buildQuery()
    {
        // Set up record query
        $this->recordQuery = $this->recordBuilder->get('ansel:Image');

        // Set up filters
        foreach ($this->imagesTagParams->getFilterProperties() as $prop) {
            // Get filterable value
            $filterableValue = $this->imagesTagParams->getFilterableValue($prop);

            // If there is a value, filter on it
            if ($filterableValue) {
                $this->recordQuery->filter(
                    $this->imagesTagParams->getFilterableProperty($prop),
                    $this->imagesTagParams->getFilterComparison($prop),
                    $this->imagesTagParams->getFilterableValue($prop)
                );
            }
        }

        // Check if we're showing cover only
        if ($this->imagesTagParams->cover_only) {
            $this->recordQuery->filter('cover', 1);
        } elseif ($this->imagesTagParams->skip_cover) {
            $this->recordQuery->filter('cover', 0);
        }

        // Check if we're not showing disabled
        if (! $this->imagesTagParams->show_disabled) {
            $this->recordQuery->filter('disabled', 0);
        }

        // Check if we're limiting
        if ($this->imagesTagParams->limit && ! $this->imagesTagParams->random) {
            $this->recordQuery->limit($this->imagesTagParams->limit);
        }

        // Check if we're offsetting
        if ($this->imagesTagParams->offset && ! $this->imagesTagParams->random) {
            $this->recordQuery->offset($this->imagesTagParams->offset);
        }

        // Get available columns
        $columns = ImageRecord::getColumnNames();

        // Order by cover first if applicable
        if ($this->imagesTagParams->cover_first) {
            $this->recordQuery->order('cover', 'desc');
        }

        // Set ordering
        foreach ($this->imagesTagParams->order_by as $prop => $dir) {
            // Make sure this is a property we can order on
            if (! in_array($prop, $columns)) {
                continue;
            }

            // Order record
            $this->recordQuery->order($prop, $dir);
        }

        // Query has been built
        $this->queryBuilt = true;
    }

    /**
     * Manipulations by location id
     */
    private $manipulationsByLocationId = array();

    /**
     * Get manipulation
     *
     * @param int $locationId
     * @return array
     */
    private function getManipulation($locationId)
    {
        // Check if location is already set
        if (isset($this->manipulationsByLocationId[$locationId])) {
            return $this->manipulationsByLocationId[$locationId];
        }

        // Get image manipulations
        /** @var \CI_DB_mysqli_result $manipulations */
        $manipulations = $this->eeFileModel->get_dimensions_by_dir_id(
            $locationId
        );


        $manipulations = $manipulations->result();

        /** @var array $manipulations */

        // Add manipulations to storage
        $this->manipulationsByLocationId[$locationId] = $manipulations;

        return $manipulations;
    }

    /**
     * Set up host
     */
    private function setUpHost()
    {
        // Check if the host param has been set
        if ($this->imagesTagParams->host) {
            $this->host = $this->imagesTagParams->host;
            return;
        }

        // Check if the global host param exists
        if ($this->globalSettings->default_host) {
            $this->host = $this->globalSettings->default_host;
        }
    }
}
