<?php

namespace BoldMinded\Ansel\Extensions;

use ExpressionEngine\Model\Channel\ChannelField;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class AfterChannelFieldDelete extends AbstractRoute
{
    public function process(ChannelField $channelField, array $values = [])
    {
        $fieldId = $channelField->field_id;
        $fieldName = $channelField->field_name;

        // Should never be the case at this point, but just in-case.
        if (!$fieldId) {
            return;
        }

        try {
            // Cleanup after ourselves. If a field is deleted, delete any images attached to that field.
            $files = ee('db')
                ->where('field_id', $fieldId)
                ->get('ansel_images')
                ->result_array();

            if (count($files) > 0) {
                ee('db')->delete('ansel_images', [
                    'field_id' => $fieldId
                ]);

                $fileNames = implode(', ', array_column($files, 'filename'));

                ee('ansel:Logger')->developer(
                    sprintf(
                        '[Ansel] The following images were deleted from Ansel because the %s field was deleted: %s',
                        $fieldName,
                        $fileNames
                    ),
                    true
                );
            }
        } catch (\Exception $exception) {
            //ee('CP/Alert')->makeBanner()
            //    ->asAlert()
            //    ->cannotClose()
            //    ->withTitle('')
            //    ->addToBody('')
            //    ->defer();
        }

    }
}
