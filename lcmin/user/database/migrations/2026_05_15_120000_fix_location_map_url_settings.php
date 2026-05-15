<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Fix the `location_map_url` URL field's missing settings.
 *
 * The original locations migration (2026_05_15_090000_create_locations_channel)
 * created this field with empty field_settings. EE's Url_Ft::validate()
 * reads $this->get_setting('allowed_url_schemes') and passes it straight
 * to in_array($scheme, $allowed) — when the setting is missing, get_setting
 * returns false instead of an array, and in_array(..., false) throws a
 * TypeError on save:
 *
 *   TypeError: in_array(): Argument #2 ($haystack) must be of type array,
 *   bool given in ExpressionEngine/Addons/url/ft.url.php:92
 *
 * Fix: set the URL fieldtype's defaults (http:// + https:// allowed,
 * blank placeholder) on the existing field so validation works.
 */
class FixLocationMapUrlSettings extends Migration
{
    public function up()
    {
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'location_map_url')->first();
        if (! $field) {
            return;
        }

        $settings = is_array($field->field_settings) ? $field->field_settings : [];

        $settings['allowed_url_schemes'] = [
            'http://'  => 'http://',
            'https://' => 'https://',
        ];

        if (! isset($settings['url_scheme_placeholder'])) {
            $settings['url_scheme_placeholder'] = '';
        }

        $field->field_settings = $settings;
        $field->save();
    }

    public function down()
    {
        // No rollback — reverting to the broken empty-settings state would
        // re-introduce the TypeError on save.
    }
}
