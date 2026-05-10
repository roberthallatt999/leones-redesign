<?php

use ExpressionEngine\Service\Migration\Migration;

class ConvertMilestoneImageToFileField extends Migration
{
    /**
     * Switch milestone_image (about_timeline channel) from `ansel` to native
     * `file` so we can handle AVIF without depending on Ansel's image-decode
     * whitelist.
     *
     * Drop and recreate the field — Ansel's settings shape is incompatible
     * with the native File field. Existing entry data on this field (if any)
     * is dropped along with the field's data table.
     */
    public function up()
    {
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'milestone_image')->first();

        if (! $field) {
            return; // nothing to convert
        }

        $field_label        = $field->field_label;
        $field_instructions = $field->field_instructions;
        $field_order        = $field->field_order;
        $site_id            = $field->site_id;
        $group_ids          = $field->ChannelFieldGroups->pluck('group_id');

        // Drop the existing ansel field (this also drops exp_channel_data_field_N)
        $field->delete();

        // Recreate as a native File field
        $new = ee('Model')->make('ChannelField');
        $new->site_id              = $site_id;
        $new->field_name           = 'milestone_image';
        $new->field_label          = $field_label ?: 'Milestone Image';
        $new->field_instructions   = $field_instructions ?: 'Photo for this milestone (single image).';
        $new->field_type           = 'file';
        $new->field_required       = 'n';
        $new->field_search         = 'n';
        $new->field_order          = $field_order ?: 4;
        $new->field_text_direction = 'ltr';
        $new->field_fmt            = 'none';
        $new->field_show_fmt       = 'n';
        $new->field_settings       = [
            'field_content_type' => 'image',
            'allowed_directories'=> 'all', // any upload directory; CP can narrow later
            'show_existing'      => 'y',
            'num_existing'       => 50,
            'field_fmt'          => 'none',
            'field_show_fmt'     => 'n',
        ];
        $new->save();

        // Re-link to the field group(s) the original belonged to
        if (!empty($group_ids)) {
            $new->ChannelFieldGroups = ee('Model')->get('ChannelFieldGroup', $group_ids)->all();
            $new->save();
        }
    }

    /**
     * Rollback: re-create as ansel using settings copied from top_image.
     */
    public function down()
    {
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'milestone_image')->first();

        if (! $field) {
            return;
        }

        $field_label        = $field->field_label;
        $field_instructions = $field->field_instructions;
        $field_order        = $field->field_order;
        $site_id            = $field->site_id;
        $group_ids          = $field->ChannelFieldGroups->pluck('group_id');

        $field->delete();

        $top_blob = ee()->db->select('field_settings')
            ->where('field_name', 'top_image')
            ->get('channel_fields')->row('field_settings');
        $settings = $top_blob ? unserialize(base64_decode($top_blob)) : [];
        if (!is_array($settings)) {
            $settings = [];
        }

        $new = ee('Model')->make('ChannelField');
        $new->site_id              = $site_id;
        $new->field_name           = 'milestone_image';
        $new->field_label          = $field_label ?: 'Milestone Image';
        $new->field_instructions   = $field_instructions;
        $new->field_type           = 'ansel';
        $new->field_required       = 'n';
        $new->field_search         = 'n';
        $new->field_order          = $field_order ?: 4;
        $new->field_text_direction = 'ltr';
        $new->field_fmt            = 'none';
        $new->field_show_fmt       = 'n';
        $new->field_settings       = $settings;
        $new->save();

        if (!empty($group_ids)) {
            $new->ChannelFieldGroups = ee('Model')->get('ChannelFieldGroup', $group_ids)->all();
            $new->save();
        }
    }
}
