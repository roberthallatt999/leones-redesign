<?php

namespace BoldMinded\Ansel\Service;

use BoldMinded\Ansel\Service\Sources\UploadLocation;
use ExpressionEngine\Addons\Grid\Model\GridColumn;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Model\Content\FieldFacade;

class LivePreviewCleaner
{
    private static $cleaned = [];

    private array $previewDirectories = [];

    public function cleanByChannelEntry(ChannelEntry $channelEntry): LivePreviewCleaner
    {
        /** @var FieldFacade[] $fields */
        $fields = array_filter($channelEntry->getCustomFields(), function (FieldFacade $field) {
            return in_array($field->getType(), ['ansel', 'grid', 'bloqs']);
        });

        foreach ($fields as $field) {
            if ($field->getType() === 'ansel') {
                $this->findPreviewDirectoryIdFromSettings($field->getSettings());
            }

            if ($field->getType() === 'bloqs') {
                /** @var \BoldMinded\Bloqs\Entity\BlockDefinition $blocks */
                $blocks = ee('bloqs:Adapter')->getBlockDefinitionsForField($field->getId());

                foreach ($blocks as $block) {
                    foreach ($block->getAtomDefinitions() as $atomDefinition) {
                        if ($atomDefinition->getType() === 'ansel') {
                            $this->findPreviewDirectoryIdFromSettings($atomDefinition->getSettings());
                        }
                    }
                }
            }

            if ($field->getType() === 'grid') {
                $gridField = ee('Model')->get('ChannelField', $field->getId())
                    ->with('GridColumns')
                    ->first();

                /** @var GridColumn[] $columns */
                $columns = $gridField->GridColumns;

                foreach ($columns as $column) {
                    if ($column->col_type === 'ansel') {
                        $this->findPreviewDirectoryIdFromSettings($column->col_settings);
                    }
                }
            }
        }

        $this->cleanByDirectoryIds();

        return $this;
    }

    public function cleanByDirectoryIds(): LivePreviewCleaner
    {
        if (bool_config_item('ansel_disable_preview_directory_cleaning')) {
            return $this;
        }

        if (empty($this->previewDirectories)) {
            return $this;
        }

        $previewDirectories = array_unique($this->previewDirectories);

        // Multiple fields might use the same directory. Each field will call this
        // so don't call unnecessary deletes if there are multiple fields in the entry.
        if (empty(array_diff($previewDirectories, self::$cleaned))) {
            return $this;
        }

        // We could have subdirectories that are different than the main directory, so iterate the list.
        // In most cases, hopefully, the same directory was defined as the Live Preview directory for all fields.
        foreach (array_unique($previewDirectories) as $previewDirectory) {
            $location = UploadLocation::getUploadLocationByIdentifier($previewDirectory);

            ee('Model')->get('File')
                ->with('UploadDestination')
                ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
                ->filter('upload_location_id', $location->uploadLocationId)
                ->filter('directory_id', $location->directoryId)
                ->delete();
        }

        self::$cleaned = array_unique(array_merge(self::$cleaned, $previewDirectories));

        return $this;
    }

    public function findPreviewDirectoryIdFromSettings(array $settings = []): LivePreviewCleaner
    {
        $previewDirectory = $settings['preview_directory'] ?? null;

        if ($previewDirectory) {
            $this->previewDirectories[] = $previewDirectory;
        }

        return $this;
    }
}
