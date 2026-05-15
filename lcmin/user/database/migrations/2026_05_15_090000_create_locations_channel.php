<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Create the `locations` channel and add a "Locations" option to the
 * page_content_blocks section_block select field.
 *
 * Each location entry holds opening/closing times (native EE date
 * fields, time-of-day used for the open/closed status logic), the
 * weekdays the location is closed (multi-select of day names matching
 * the format "%A" output of {current_time}), phone, street address
 * split into city/state/zip (so the same data can compose into card,
 * footer, and structured contexts), and an optional Google Maps link
 * for one storefront. Entries are rendered as paired cards by
 * partials/locations_section.html (embedded by the section_block
 * handler in pages/index.html when the block's value is "locations").
 * The same channel feeds the address/hours blocks above the flavor
 * boards on the per-location templates and the footer.
 *
 * Title is the built-in channel-title field (e.g. "Spearfish",
 * "Rapid City"). url_title becomes the lookup key for per-location
 * templates: {exp:channel:entries channel="locations" url_title="spearfish"}.
 *
 * Address is split into 4 fields so the same data can be composed into
 * different formats per context (one-line for cards, multi-line for
 * structured data / schema.org, parts only where appropriate). Refine
 * later if locations need richer data (e.g. multiple phone numbers,
 * social links, hero image).
 */
class CreateLocationsChannel extends Migration
{
    public function up()
    {
        $site_id = 1;

        // ----- Field Group -------------------------------------------------
        $group = ee('Model')->make('ChannelFieldGroup');
        $group->site_id    = $site_id;
        $group->group_name = 'Locations Channel Fields';
        $group->short_name = 'locations_channel_fields';
        $group->save();

        // ----- Fields ------------------------------------------------------
        $order = 0;

        $open_time = $this->makeField($site_id, 'location_open_time', 'Opens At',
            'Daily opening time. The date portion is ignored — only the time of day is used. Powers the "Open / Closed now" status logic and the displayed hours.',
            'date', [], ++$order);

        $close_time = $this->makeField($site_id, 'location_close_time', 'Closes At',
            'Daily closing time. Like Opens At, only the time of day matters.',
            'date', [], ++$order);

        $closed_days = $this->makeField($site_id, 'location_closed_days', 'Closed Days',
            'Weekdays the location is closed all day. Tick zero or more. Drives the "Closed today" status — the day-name values match what {current_time format="%A"} returns.',
            'multi_select', [
                'value_label_pairs' => [
                    'Sunday'    => 'Sunday',
                    'Monday'    => 'Monday',
                    'Tuesday'   => 'Tuesday',
                    'Wednesday' => 'Wednesday',
                    'Thursday'  => 'Thursday',
                    'Friday'    => 'Friday',
                    'Saturday'  => 'Saturday',
                ],
            ], ++$order);

        $phone = $this->makeField($site_id, 'location_phone', 'Phone',
            'Display phone number, e.g. "(605) 644-6461".',
            'text', ['field_maxl' => 32], ++$order);

        $street = $this->makeField($site_id, 'location_street_address', 'Street Address',
            'Street address only, e.g. "722 1/2 Main Street". City / state / zip are separate fields.',
            'text', ['field_maxl' => 200], ++$order);

        $city = $this->makeField($site_id, 'location_city', 'City',
            'City, e.g. "Spearfish".',
            'text', ['field_maxl' => 80], ++$order);

        $state = $this->makeField($site_id, 'location_state', 'State',
            'Two-letter state abbreviation, e.g. "SD".',
            'text', ['field_maxl' => 8], ++$order);

        $zip = $this->makeField($site_id, 'location_zip', 'Zip Code',
            'Postal code, e.g. "57783".',
            'text', ['field_maxl' => 16], ++$order);

        $map_url = $this->makeField($site_id, 'location_map_url', 'Map URL',
            'Google Maps link for the address (target="_blank"). Optional — if blank, the address renders as plain text.',
            'url', [
                // EE's Url_Ft::validate() reads `allowed_url_schemes` and calls
                // in_array() on it directly — missing/empty crashes with a
                // TypeError. Set the fieldtype's defaults explicitly.
                'allowed_url_schemes' => [
                    'http://'  => 'http://',
                    'https://' => 'https://',
                ],
                'url_scheme_placeholder' => '',
            ], ++$order);

        // Attach fields to the group
        $group->ChannelFields = [$open_time, $close_time, $closed_days, $phone, $street, $city, $state, $zip, $map_url];
        $group->save();

        // ----- Channel -----------------------------------------------------
        $channel = ee('Model')->make('Channel');
        $channel->site_id        = $site_id;
        $channel->channel_name   = 'locations';
        $channel->channel_title  = 'Locations';
        $channel->channel_lang   = 'en';
        $channel->channel_url    = ee()->config->item('site_url');
        $channel->deft_status    = 'open';
        $channel->save();

        $channel->FieldGroups = [$group];
        $channel->save();

        // ----- Add "Locations" to section_block select options -------------
        $section_block = ee('Model')->get('ChannelField')
            ->filter('field_name', 'section_block')->first();

        if ($section_block) {
            $settings = $section_block->field_settings;
            if (is_array($settings) && isset($settings['value_label_pairs'])) {
                $settings['value_label_pairs']['locations'] = 'Locations';
                $section_block->field_settings = $settings;
                $section_block->save();
            }
        }
    }

    public function down()
    {
        // Remove "Locations" from section_block options
        $section_block = ee('Model')->get('ChannelField')
            ->filter('field_name', 'section_block')->first();

        if ($section_block) {
            $settings = $section_block->field_settings;
            if (is_array($settings) && isset($settings['value_label_pairs']['locations'])) {
                unset($settings['value_label_pairs']['locations']);
                $section_block->field_settings = $settings;
                $section_block->save();
            }
        }

        // Delete the channel + its entries
        $channel = ee('Model')->get('Channel')
            ->filter('channel_name', 'locations')->first();
        if ($channel) {
            ee('Model')->get('ChannelEntry')
                ->filter('channel_id', $channel->channel_id)->delete();
            $channel->delete();
        }

        // Delete the field group
        $group = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'Locations Channel Fields')->first();
        if ($group) {
            $group->delete();
        }

        // Drop each field
        foreach (['location_open_time', 'location_close_time', 'location_closed_days', 'location_phone', 'location_street_address', 'location_city', 'location_state', 'location_zip', 'location_map_url'] as $name) {
            $f = ee('Model')->get('ChannelField')->filter('field_name', $name)->first();
            if ($f) {
                $f->delete();
            }
        }
    }

    private function makeField($site_id, $name, $label, $instructions, $type, array $extra_settings, $field_order)
    {
        $base_settings = [
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
        ];

        $f = ee('Model')->make('ChannelField');
        $f->site_id            = $site_id;
        $f->field_name         = $name;
        $f->field_label        = $label;
        $f->field_instructions = $instructions;
        $f->field_type         = $type;
        $f->field_required     = 'n';
        $f->field_search       = 'n';
        $f->field_order        = $field_order;
        $f->field_text_direction = 'ltr';
        $f->field_fmt          = 'none';
        $f->field_show_fmt     = 'n';
        $f->field_settings     = array_merge($base_settings, $extra_settings);
        $f->save();
        return $f;
    }
}
