<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateAboutTimelineChannel extends Migration
{
    /**
     * Execute the migration
     *
     * Creates:
     *   - Field group "About Timeline Fields"
     *   - Channel fields: milestone_year, milestone_title, milestone_description, milestone_image
     *   - Channel "About Timeline" using the field group
     *
     * Image and rich-text settings are copied from the existing `pages`
     * channel's `top_image` (ansel) and `text_area_1` (wygwam) so the
     * upload directory + Wygwam config are pre-wired correctly.
     */
    public function up()
    {
        // Source the field-settings arrays from existing fields with the same types.
        // EE expects field_settings as a PHP array on the model — it serializes on save.
        $top_image_blob   = ee()->db->select('field_settings')
            ->where('field_name', 'top_image')->get('channel_fields')->row('field_settings');
        $text_area_1_blob = ee()->db->select('field_settings')
            ->where('field_name', 'text_area_1')->get('channel_fields')->row('field_settings');
        // EE stores field_settings base64-encoded, then serialized.
        $top_image_settings   = $top_image_blob   ? unserialize(base64_decode($top_image_blob))   : [];
        $text_area_1_settings = $text_area_1_blob ? unserialize(base64_decode($text_area_1_blob)) : [];

        $site_id = 1;

        // -- Field Group ----------------------------------------------------
        $group = ee('Model')->make('ChannelFieldGroup');
        $group->site_id    = $site_id;
        $group->group_name = 'About Timeline Fields';
        $group->short_name = 'about_timeline_fields';
        $group->save();

        // -- Fields ---------------------------------------------------------
        $field_order = 0;

        $year = ee('Model')->make('ChannelField');
        $year->site_id            = $site_id;
        $year->field_name         = 'milestone_year';
        $year->field_label        = 'Milestone Year';
        $year->field_instructions = 'e.g. 2014. Used to order timeline entries on the About page.';
        $year->field_type         = 'text';
        $year->field_required     = 'y';
        $year->field_search       = 'n';
        $year->field_order        = ++$field_order;
        $year->field_text_direction = 'ltr';
        $year->field_fmt          = 'none';
        $year->field_show_fmt     = 'n';
        $year->field_settings     = [
            'field_maxl'              => 16,
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
        ];
        $year->save();

        $title = ee('Model')->make('ChannelField');
        $title->site_id            = $site_id;
        $title->field_name         = 'milestone_title';
        $title->field_label        = 'Milestone Title';
        $title->field_instructions = 'Short title for the milestone (e.g. "The beginning").';
        $title->field_type         = 'text';
        $title->field_required     = 'y';
        $title->field_search       = 'n';
        $title->field_order        = ++$field_order;
        $title->field_text_direction = 'ltr';
        $title->field_fmt          = 'none';
        $title->field_show_fmt     = 'n';
        $title->field_settings     = [
            'field_maxl'              => 200,
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
        ];
        $title->save();

        $desc = ee('Model')->make('ChannelField');
        $desc->site_id            = $site_id;
        $desc->field_name         = 'milestone_description';
        $desc->field_label        = 'Milestone Description';
        $desc->field_instructions = 'Body copy describing this milestone.';
        $desc->field_type         = 'wygwam';
        $desc->field_required     = 'n';
        $desc->field_search       = 'n';
        $desc->field_order        = ++$field_order;
        $desc->field_text_direction = 'ltr';
        $desc->field_fmt          = 'none';
        $desc->field_show_fmt     = 'n';
        $desc->field_settings     = $text_area_1_settings; // reuse existing wygwam config
        $desc->save();

        $image = ee('Model')->make('ChannelField');
        $image->site_id            = $site_id;
        $image->field_name         = 'milestone_image';
        $image->field_label        = 'Milestone Image';
        $image->field_instructions = 'Photo for this milestone (single image).';
        $image->field_type         = 'ansel';
        $image->field_required     = 'n';
        $image->field_search       = 'n';
        $image->field_order        = ++$field_order;
        $image->field_text_direction = 'ltr';
        $image->field_fmt          = 'none';
        $image->field_show_fmt     = 'n';
        $image->field_settings     = $top_image_settings; // reuse existing ansel config
        $image->save();

        // Attach fields to the group
        $group->ChannelFields = [$year, $title, $desc, $image];
        $group->save();

        // -- Channel --------------------------------------------------------
        $channel = ee('Model')->make('Channel');
        $channel->site_id          = $site_id;
        $channel->channel_name     = 'about_timeline';
        $channel->channel_title    = 'About Timeline';
        $channel->channel_lang     = 'en';
        $channel->channel_url      = ee()->config->item('site_url');
        $channel->deft_status      = 'open';
        $channel->save();

        // Link channel to field group
        $channel->FieldGroups = [$group];
        $channel->save();
    }

    /**
     * Rollback the migration
     */
    public function down()
    {
        $channel = ee('Model')->get('Channel')
            ->filter('channel_name', 'about_timeline')->first();
        if ($channel) {
            // Delete entries first (cascade should handle it but be explicit)
            ee('Model')->get('ChannelEntry')
                ->filter('channel_id', $channel->channel_id)->delete();
            $channel->delete();
        }

        $group = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'About Timeline Fields')->first();
        if ($group) {
            $group->delete();
        }

        foreach (['milestone_year', 'milestone_title', 'milestone_description', 'milestone_image'] as $name) {
            $field = ee('Model')->get('ChannelField')
                ->filter('field_name', $name)->first();
            if ($field) {
                $field->delete();
            }
        }
    }
}
