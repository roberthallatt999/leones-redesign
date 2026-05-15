<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add a `location_short_title` text field to the locations channel.
 *
 * The entry's main `title` is the long, branded form (e.g. "Spearfish
 * Creamery", "Rapid City Scoop Shop") used in the footer link.
 * `location_short_title` is the bare label (e.g. "Spearfish", "Rapid
 * City") used on the contact-page location cards where the branded
 * suffix would be redundant. Templates fall back to {title} when
 * short_title is blank.
 */
class AddLocationShortTitleField extends Migration
{
    public function up()
    {
        // Idempotent guard
        $existing = ee('Model')->get('ChannelField')
            ->filter('field_name', 'location_short_title')->first();
        if ($existing) {
            return;
        }

        $field = ee('Model')->make('ChannelField');
        $field->site_id            = 1;
        $field->field_name         = 'location_short_title';
        $field->field_label        = 'Short Title';
        $field->field_instructions = 'Optional short form of the location name (e.g. "Spearfish" when the full title is "Spearfish Creamery"). Used on the contact-page cards. Falls back to the entry title when blank.';
        $field->field_type         = 'text';
        $field->field_required     = 'n';
        $field->field_search       = 'n';
        $field->field_text_direction = 'ltr';
        $field->field_fmt          = 'none';
        $field->field_show_fmt     = 'n';
        $field->field_settings     = [
            'field_maxl'              => 80,
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
        ];
        $field->save();

        // Attach to the Locations Channel Fields group
        $group = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'Locations Channel Fields')->first();
        if ($group) {
            $existing_ids = $group->ChannelFields->getIds();
            $existing_ids[] = (int) $field->field_id;
            $group->ChannelFields = ee('Model')->get('ChannelField', $existing_ids)->all();
            $group->save();
        }
    }

    public function down()
    {
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'location_short_title')->first();
        if ($field) {
            $field->delete();
        }
    }
}
