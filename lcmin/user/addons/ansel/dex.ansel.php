<?php

use BoldMinded\Dexter\Dependency\BoldMinded\DexterCore\Contracts\ConfigInterface;
use BoldMinded\Dexter\Dependency\BoldMinded\DexterCore\Contracts\IndexableInterface;
use BoldMinded\Dexter\Dependency\BoldMinded\DexterCore\Service\Field\AbstractField;

class AnselDexter extends AbstractField
{
    public function process(
        IndexableInterface $indexable,
        ConfigInterface $config,
        int|string $fieldId,
        array $fieldSettings,
        $fieldValue,
        $fieldFacade = null
    ): array {
        if ($fieldValue === null) {
            return [];
        }

        if (!is_array($fieldValue)) {
            $data = json_decode($fieldValue, true);
        } else {
            $data = $fieldValue;
        }

        if (!is_array($data) || empty($data)) {
            return [];
        }

        $fluidFieldId = $fieldFacade->getItem('fluid_field_data_id') ?? null;
        $fieldType = $fieldSettings['field_type'] ?? '';

        if ($fieldType === 'bloqs') {
            $fieldType = 'blocks';
        } else {
            $fieldType = $fieldSettings['content_type'] ?? $fieldFacade->getContentType();
        }

        $params = [
            'content_id' => $fieldFacade->getContentId(),
            'content_type' => $fieldType,
            'field_id' => $fieldSettings['field_id'] ?? $fieldId,
            'row_id' => $fieldSettings['grid_row_id'] ?? $fluidFieldId,
            'col_id' => $fieldSettings['grid_col_id'] ?? $fluidFieldId,
        ];

        $r = ee('ansel:ImagesTagController')->parse(
            $params,
            '{img:url},',
            $data ?: '',
            $fieldSettings
        );

        $imagePaths = array_filter(explode(',', $r));

        unset($data['placeholder']);

        $return = [];
        $iterator = 0;

        foreach ($data as $row) {
            unset($row['ansel_image_delete']);
            unset($row['original_location_type']);
            unset($row['upload_location_type']);

            if (isset($imagePaths[$iterator])) {
                $row['url'] = $imagePaths[$iterator];
                $return[] = $row;
            }

            $iterator++;
        }

        return $return;
    }
}
