<?php

namespace BoldMinded\Ansel\Tags;

use BoldMinded\Ansel\Service\AnselImages\ImagesTag;
use Expressionengine\Coilpack\Api\Graph\Support\GeneratedType;
use Expressionengine\Coilpack\Contracts\GeneratesGraphType;
use Expressionengine\Coilpack\Contracts\ListsGraphType;
use Expressionengine\Coilpack\Fieldtypes\Fieldtype;
use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Models\Channel\ChannelField;
use Expressionengine\Coilpack\Models\FieldContent;
use GraphQL\Type\Definition\Type;

class Replace extends Fieldtype implements GeneratesGraphType, ListsGraphType
{
    private FieldContent $content;
    private int $entryId;
    private int $fieldId;

    public function apply(FieldContent $content, array $parameters = [])
    {
        $this->content = $content;
        $attributes = $content->getAttributes();
        $fieldSettings = $content->field->field_settings;
        $data = $attributes['data'] ?? [];

        $this->entryId = $attributes['entry_id'];
        $this->fieldId = $attributes['field_type_id'];

        /** @var ImagesTag $imagesTag */
        $imagesTag = ee('ansel:AnselImagesTag');

        $data = json_decode($data, true);

        $parameters['content_id'] = $this->entryId;
        $parameters['field_id'] = $this->fieldId;

        // When Ansel is embedded as an atom inside another fieldtype
        // (Bloqs / Grid / Fluid), records are saved with row_id / col_id
        // and a non-default content_type. Propagate those through so
        // ImagesTag can find the right rows. (See AnselDexter::process
        // and SaveRow for the authoritative mapping.)
        if (isset($attributes['grid_row_id'])) {
            $parameters['row_id'] = $attributes['grid_row_id'];
        }
        if (isset($attributes['grid_col_id'])) {
            $parameters['col_id'] = $attributes['grid_col_id'];
        }
        if (! empty($attributes['blocks_block_id']) || ! empty($attributes['blocks_atom_id'])) {
            // Stored by Bloqs as content_type 'blocks' — see dex.ansel.php.
            $parameters['content_type'] = 'blocks';
        }

        $imagesTag->populateTagParams($parameters);
        $imageVariables = $imagesTag->getVariables($data, $fieldSettings, $parameters);

        if (empty($data)) {
            return FieldtypeOutput::for($this)->value([]);
        }

        // When there's no EE template engine (e.g. we're being rendered from
        // Coilpack / GraphQL), ImagesTag::getVariables() treats the call as
        // "native" and namespaces every key with the configured prefix (by
        // default "img:"). Strip that prefix back off so downstream code —
        // including the GraphQL type defined by generateGraphType() — can
        // access plain keys like id / url / width.
        $imageVariables = array_map(
            fn (array $image) => $this->stripNamespacePrefix($image),
            $imageVariables ?: []
        );

        $output = [];

        foreach ($imageVariables as $image) {
            // Skip malformed rows (missing 'id' / 'url') rather than fatal.
            if (! is_array($image) || ! isset($image['id'], $image['url'])) {
                continue;
            }

            $urlType = (new Url(
                'ansel',
                $image['id'],
                $imagesTag,
                $image,
            ));

            $urlOutput = FieldtypeOutput::for(
                $urlType
            )->string($image['url']);

            $image['url'] = $urlOutput;

            $output[] = FieldtypeOutput::make()->array($image);
        }

        return FieldtypeOutput::make()->array($output);
    }

    public function generateGraphType(ChannelField $field)
    {
        // Ansel is a flat, repeating list of image records with a fixed
        // schema — no per-field variation. Each row coming out of apply()
        // is a FieldtypeOutput wrapping the array returned by
        // ImagesTag::setVariablesFromRecord(). Because this class
        // implements ListsGraphType, Coilpack wraps the type in listOf()
        // automatically; we just define the row shape here.
        //
        // Fields with colons in their names (manipulation outputs like
        // "thumb:url") can't be valid GraphQL field names, so they are
        // intentionally not exposed here. Callers who need manipulations
        // can use the manipulation short-name fields added below.
        return new GeneratedType([
            'fields' => function () {
                $stringField = fn (string $key, string $description = ''): array => [
                    'type' => Type::string(),
                    'description' => $description,
                    'resolve' => fn ($root) => $this->anselValue($root, $key),
                ];

                $intField = fn (string $key, string $description = ''): array => [
                    'type' => Type::int(),
                    'description' => $description,
                    'resolve' => function ($root) use ($key) {
                        $value = $this->anselValue($root, $key);
                        return is_numeric($value) ? (int) $value : null;
                    },
                ];

                return [
                    // Record identity
                    'id' => $intField('id', 'Ansel record id'),
                    'site_id' => $intField('site_id'),
                    'content_id' => $intField('content_id', 'Entry / content id this image is attached to'),
                    'field_id' => $intField('field_id'),
                    'content_type' => $stringField('content_type'),
                    'row_id' => $intField('row_id'),
                    'col_id' => $intField('col_id'),
                    'file_id' => $intField('file_id'),
                    'original_file_id' => $intField('original_file_id'),
                    'upload_location_id' => $intField('upload_location_id'),
                    'member_id' => $intField('member_id'),
                    'position' => $intField('position'),
                    'cover' => $intField('cover', 'Non-zero if this image is the cover image'),

                    // File meta
                    'filename' => $stringField('filename'),
                    'basename' => $stringField('basename'),
                    'extension' => $stringField('extension'),
                    'file_size' => $intField('file_size'),
                    'filesize' => $intField('filesize'),
                    'width' => $intField('width'),
                    'height' => $intField('height'),

                    // Descriptive fields
                    'title' => $stringField('title'),
                    'description' => $stringField('description'),
                    'caption' => $stringField('caption', 'Alias of description for pre-3.0 templates'),
                    'description_field' => $stringField('description_field'),
                    'credit_field' => $stringField('credit_field'),
                    'location_field' => $stringField('location_field'),

                    // Dates
                    'upload_date' => $stringField('upload_date'),
                    'modify_date' => $stringField('modify_date'),
                    'modified_date' => $stringField('modified_date'),

                    // Paths & URLs (derived/stringable values — apply() wraps the
                    // url in a FieldtypeOutput, but __toString resolves it.)
                    'path' => $stringField('path'),
                    'url' => $stringField('url'),
                    'thumbnail_path' => $stringField('thumbnail_path'),
                    'thumbnail_url' => $stringField('thumbnail_url'),

                    // Original (pre-crop) variants
                    'original_filename' => $stringField('original_filename'),
                    'original_basename' => $stringField('original_basename'),
                    'original_extension' => $stringField('original_extension'),
                    'original_filesize' => $intField('original_filesize'),
                    'original_path' => $stringField('original_path'),
                    'original_url' => $stringField('original_url'),
                    'original_title' => $stringField('original_title'),
                    'original_title_field' => $stringField('original_title_field'),
                    'original_description' => $stringField('original_description'),
                    'original_description_field' => $stringField('original_description_field'),
                    'original_credit' => $stringField('original_credit'),
                    'original_credit_field' => $stringField('original_credit_field'),
                    'original_location' => $stringField('original_location'),
                    'original_location_field' => $stringField('original_location_field'),

                    'host' => $stringField('host'),
                ];
            },
        ]);
    }

    /**
     * Pull a scalar value out of an Ansel image row.
     *
     * apply() wraps each image in FieldtypeOutput::make()->array([...]); the
     * default TemplateOutput::__get already falls back to the inner array, so
     * property access works on both the wrapper and raw arrays. The value may
     * itself be a FieldtypeOutput (e.g. the url, which apply() re-wraps) — we
     * cast to string/int at the field level, relying on __toString for
     * FieldtypeOutput.
     */
    private function anselValue($root, string $key)
    {
        if (is_array($root)) {
            return $root[$key] ?? null;
        }

        // FieldtypeOutput / TemplateOutput
        if (is_object($root)) {
            return $root->{$key} ?? null;
        }

        return null;
    }

    /**
     * Remove the namespace prefix (e.g. "img:") that
     * NamespaceVars::run() prepends to every key. ImagesTag applies that
     * when no non-native template engine is active — which is always the
     * case for Coilpack / GraphQL, so every returned key looks like
     * "img:id" instead of "id".
     *
     * Manipulation outputs ("thumb:url") become "img:thumb:url" after
     * namespacing, so we strip only the first segment; the inner colon
     * is preserved (those keys aren't usable as GraphQL field names
     * anyway and are ignored in generateGraphType).
     */
    private function stripNamespacePrefix(array $image): array
    {
        $stripped = [];
        foreach ($image as $key => $value) {
            $colon = strpos($key, ':');
            $newKey = ($colon === false) ? $key : substr($key, $colon + 1);
            $stripped[$newKey] = $value;
        }
        return $stripped;
    }
}
