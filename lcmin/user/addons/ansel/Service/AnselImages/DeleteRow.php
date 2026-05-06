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

use BoldMinded\Ansel\Record\Image as ImageRecord;
use BoldMinded\Ansel\Service\Sources\UploadLocation;
use ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BoldMinded\Ansel\Service\Sources\SourceRouter;

/**
 * Class DeleteRow
 */
class DeleteRow
{
    /**
     * @var RecordBuilder $recordBuilder
     */
    private $recordBuilder;

    /**
     * @var SourceRouter $sourceRouter
     */
    private $sourceRouter;

    /**
     * Constructor
     *
     * @param RecordBuilder $recordBuilder
     * @param SourceRouter $sourceRouter
     */
    public function __construct(
        RecordBuilder $recordBuilder,
        SourceRouter $sourceRouter
    ) {
        // Inject dependencies
        $this->recordBuilder = $recordBuilder;
        $this->sourceRouter = $sourceRouter;
    }

    /**
     * Delete
     *
     * @param int $anselId
     * @param string $contentType
     */
    public function delete($anselId, $contentType)
    {
        // Get record query builder
        $anselRecord = $this->recordBuilder->get('ansel:Image');

        // Filter the record
        $anselRecord->filter('id', $anselId);

        // Get the record
        $anselRecord = $anselRecord->first();

        /** @var ImageRecord $anselRecord */

        // Make sure record exists
        if (! $anselRecord) {
            return;
        }

        // Set up the source router location type
        $this->sourceRouter->setSource(
            $anselRecord->getProperty('upload_location_type')
        );

        // Delete the high quality file
        $this->sourceRouter->deleteFile(
            new UploadLocation(
                uploadLocationId: (int) $anselRecord->getProperty('upload_location_id'),
                directoryId: (int) $anselRecord->getProperty('directory_id'),
            ),
            $anselRecord->getBasename(),
            "{$anselRecord->getHighQualityDirectoryName()}/{$anselRecord->id}"
        );

        // Delete the thumbnail file
        $this->sourceRouter->deleteFile(
            new UploadLocation(
                uploadLocationId: (int) $anselRecord->getProperty('upload_location_id'),
                directoryId: (int) $anselRecord->getProperty('directory_id'),
            ),
            $anselRecord->getBasename(),
            "{$anselRecord->getThumbDirectoryName()}/{$anselRecord->id}"
        );

        // https://boldminded.com/support/ticket/3124
        if (ee()->extensions->active_hook('ansel_should_delete_row')) {
            $response = ee()->extensions->call(
                'ansel_should_delete_row',
                $anselRecord,
                $this->sourceRouter,
                $contentType
            );

            if ($response) {
                // Delete the source file
                $this->sourceRouter->removeFile(
                    $anselRecord->getProperty('file_id')
                );
            }
        } else {
            // Delete the source file
            $this->sourceRouter->removeFile(
                $anselRecord->getProperty('file_id')
            );
        }

        // Delete the ansel record
        $anselRecord->delete();
    }
}
