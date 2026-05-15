<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add a `page_color_scheme` top-level select field to the Pages channel
 * so editors can pick a body background colour per page. Used by
 * pages/index.html to wrap the hero + atomic content in a `color-scheme-N`
 * class; trailing sections (timeline / faqs / collage) keep their own
 * backgrounds.
 *
 * Only the body-content-friendly schemes are exposed (cream variants).
 * The dark schemes (5/6) are designed for full-bleed sections and don't
 * make sense as a body background. No selection = default white.
 */
class AddPageColorSchemeField extends Migration
{
    public function up()
    {
        $field = ee('Model')->make('ChannelField');
        $field->site_id              = 1;
        $field->field_name           = 'page_color_scheme';
        $field->field_label          = 'Page Color Scheme';
        $field->field_instructions   = 'Optional background colour for the page content area (hero + body). Leave blank for default white. Trailing sections (timeline, FAQs, collage) keep their own backgrounds.';
        $field->field_type           = 'select';
        $field->field_required       = 'n';
        $field->field_search         = 'n';
        $field->field_text_direction = 'ltr';
        $field->field_fmt            = 'none';
        $field->field_show_fmt       = 'n';
        $field->field_settings       = [
            'value_label_pairs' => [
                'color-scheme-2' => 'Cream',
                'color-scheme-4' => 'Cream Light',
            ],
        ];
        $field->save();
        $new_field_id = (int) $field->field_id;

        // Attach to the "Pages Channel Fields" field group so it shows up
        // on Pages entry edit screens (same pattern as page_hero_icon).
        $pages_group = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'Pages Channel Fields')->first();
        if ($pages_group) {
            $existing_ids = $pages_group->ChannelFields->getIds();
            $existing_ids[] = $new_field_id;
            $pages_group->ChannelFields = ee('Model')->get('ChannelField', $existing_ids)->all();
            $pages_group->save();
        }
    }

    public function down()
    {
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'page_color_scheme')
            ->first();
        if ($field) {
            $field->delete();
        }
    }
}
