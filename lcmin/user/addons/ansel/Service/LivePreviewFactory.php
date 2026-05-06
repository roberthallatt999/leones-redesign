<?php

namespace BoldMinded\Ansel\Service;

use BoldMinded\Ansel\Model\FieldSettings;
use BoldMinded\Ansel\Record\Image;
use BoldMinded\Ansel\Service\AnselImages\SaveRow;
use BoldMinded\Ansel\Service\Sources\UploadLocation;
use ExpressionEngine\Service\Model\Collection;

class LivePreviewFactory
{
    private \EE_Logger $eeLogger;

    public function __construct(
        \EE_Logger $eeLogger,
    )
    {
        $this->eeLogger = $eeLogger;
    }

    public function create(array $fieldData = [], array $fieldSettings = []): Collection
    {
        if (!isset($fieldSettings['preview_directory'])) {
            return new Collection();
        }

        $collection = [];

        $previewDirectory = UploadLocation::getUploadLocationByIdentifier($fieldSettings['preview_directory']);
        $previewDirectoryType = $previewDirectory->type;
        $previewDirectoryLocationId = $previewDirectory->uploadLocationId;
        $previewDirectorySubDirectoryId = $previewDirectory->directoryId;

        /** @var FieldSettings $fieldSettings */
        $fieldSettingsModel = ee('ansel:FieldSettingsModel');
        $fieldSettingsModel->fill($fieldSettings);

        foreach ($fieldData as $key => $row) {
            if ($key === 'placeholder' ||
                (isset($row['ansel_image_delete']) && $row['ansel_image_delete'] !== '')
            ) {
                continue;
            }

            /** @var SaveRow $saveRow */
            $saveRow = ee('ansel:AnselImagesSaveRow');

            // It's an existing file, but newly added to the entry
            if ($row['source_file_id'] !== '') {
                $row['upload_location_id'] = (int) $previewDirectoryLocationId;
                $row['directory_id'] = (int) $previewDirectorySubDirectoryId;

                $imageModel = $saveRow->save(
                    $row,
                    $fieldSettingsModel,
                    $row['source_file_id'],
                    'channel',
                    0,
                    null,
                    null,
                    true,
                );

                if ($imageModel instanceof Image) {
                    $collection[] = $imageModel;
                } else {
                    $this->eeLogger->developer(
                        sprintf(
                            '[Ansel] Unable to display existing image in LivePreview: %s',
                            json_encode($row)
                        )
                    );
                }
            }

            // It's a newly uploaded file while editing the entry
            if (
                $previewDirectorySubDirectoryId &&
                $previewDirectoryType &&
                $row['file_location'] !== '' &&
                $row['filename'] === '' &&
                $row['source_file_id'] === ''
            ) {
                $pathInfo = pathinfo($row['file_location']);
                $row['filename'] = $pathInfo['filename'];
                $row['extension'] = $pathInfo['extension'];
                $row['original_extension'] = $pathInfo['extension'];
                $row['original_location_type'] = $previewDirectoryType;
                $row['upload_location_type'] = $previewDirectoryType;
                $row['upload_location_id'] = (int) $previewDirectoryLocationId;
                $row['directory_id'] = (int) $previewDirectorySubDirectoryId;

                $imageModel = $saveRow->save(
                    $row,
                    $fieldSettingsModel,
                    0,
                    0,
                    null,
                    null,
                    true,
                );

                if ($imageModel instanceof Image) {
                    $collection[] = $imageModel;
                } else {
                    $this->eeLogger->developer(
                        sprintf(
                            '[Ansel] Unable to display new image in LivePreview: %s',
                            json_encode($row)
                        )
                    );
                }
            }
        }

        return new Collection($collection);
    }
}

