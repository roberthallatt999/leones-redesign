<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add the `section_block` select field and enable it in the
 * `page_content_blocks` Fluid field (id=37).
 *
 * section_block is the single "what kind of trailing section?" chooser
 * used by pages/_single.html. Its selected value picks a self-contained
 * partial — the section's markup and content live entirely in the partial,
 * the field carries no content of its own:
 *
 *   timeline → partials/timeline_section
 *   faqs     → partials/faqs_section
 *   collage  → partials/collage_section
 *
 * {content} on a single select returns the raw option value
 * (Select_ft::replace_tag → OptionFieldtype::replace_tag returns $data),
 * so _single.html matches {if content == "timeline"} against these values.
 *
 * To add a section type later: add a value_label_pair here (or in the CP),
 * create partials/<value>_section.html, and add an {if} branch in
 * pages/_single.html.
 */
class AddSectionBlockField extends Migration
{
    private $fluid_id = 37;

    public function up()
    {
        $section = ee('Model')->make('ChannelField');
        $section->site_id              = 1;
        $section->field_name           = 'section_block';
        $section->field_label          = 'Section Block';
        $section->field_instructions   = 'Inserts a full-width page section. Pick a type — its markup and content live in the matching template partial (partials/<type>_section).';
        $section->field_type           = 'select';
        $section->field_required       = 'n';
        $section->field_search         = 'n';
        $section->field_text_direction = 'ltr';
        $section->field_fmt            = 'none';
        $section->field_show_fmt       = 'n';
        $section->field_settings       = [
            'value_label_pairs' => [
                'timeline' => 'Timeline',
                'faqs'     => 'FAQs Section',
                'collage'  => 'Collage Banner',
            ],
        ];
        $section->save();

        // Enable section_block in the page_content_blocks Fluid field.
        $fluid = ee('Model')->get('ChannelField', $this->fluid_id)->first();

        if (! $fluid) {
            throw new \Exception("Fluid field {$this->fluid_id} (page_content_blocks) not found; aborting.");
        }

        $settings = $fluid->field_settings;

        // Fail loud rather than clobber the existing allowed-fields list.
        if (! is_array($settings) || ! isset($settings['field_channel_fields']) || ! is_array($settings['field_channel_fields'])) {
            throw new \Exception('page_content_blocks field_settings not in the expected shape; aborting to avoid clobbering the allowed-fields list.');
        }

        if (! in_array((string) $section->field_id, $settings['field_channel_fields'], true)) {
            $settings['field_channel_fields'][] = (string) $section->field_id;
        }

        if (! isset($settings['field_channel_field_groups'])) {
            $settings['field_channel_field_groups'] = [];
        }

        $fluid->field_settings = $settings;
        $fluid->save();
    }

    public function down()
    {
        $section = ee('Model')->get('ChannelField')
            ->filter('field_name', 'section_block')->first();

        if (! $section) {
            return;
        }

        $section_id = (string) $section->field_id;

        // Remove section_block from the Fluid field's allowed block types.
        $fluid = ee('Model')->get('ChannelField', $this->fluid_id)->first();

        if ($fluid) {
            $settings = $fluid->field_settings;

            if (is_array($settings) && ! empty($settings['field_channel_fields']) && is_array($settings['field_channel_fields'])) {
                $settings['field_channel_fields'] = array_values(array_filter(
                    $settings['field_channel_fields'],
                    function ($id) use ($section_id) {
                        return (string) $id !== $section_id;
                    }
                ));
                $fluid->field_settings = $settings;
                $fluid->save();
            }
        }

        // Drop the field (also drops its channel_data_field_<id> table).
        $section->delete();
    }
}
