<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add a `use_background_image` toggle column to the Hero Block Grid so
 * editors can opt out of the full-bleed image hero. When set to "n", the
 * hero renders title + subtitle on a transparent background — text colour
 * inherits from the page body (dark on cream, etc.) instead of white.
 *
 * Default "y" so existing entries (e.g. About) continue rendering the
 * image hero unchanged.
 *
 * Implementation note: same direct-SQL pattern as add_hero_icon_column —
 * grid_model->save_col_settings() isn't usable from the migration CLI
 * (fieldtype handler registry isn't bootstrapped). We INSERT into
 * exp_grid_columns and ALTER the per-field data table directly.
 */
class AddUseBackgroundImageColumn extends Migration
{
    public function up()
    {
        $hero_grid = ee('Model')->get('ChannelField')
            ->filter('field_name', 'hero_block')
            ->first();
        if (! $hero_grid) {
            throw new \RuntimeException('hero_block Grid field not found.');
        }
        $field_id = (int) $hero_grid->field_id;
        $data_table = 'exp_channel_grid_field_' . $field_id;

        // Idempotent re-run guard
        $existing = ee()->db->where('field_id', $field_id)
            ->where('col_name', 'use_background_image')
            ->get('grid_columns')->row_array();
        if ($existing) {
            $col_id = (int) $existing['col_id'];
        } else {
            $max_order = (int) ee()->db
                ->select_max('col_order', 'max_order')
                ->where('field_id', $field_id)
                ->get('grid_columns')->row('max_order');

            ee()->db->insert('grid_columns', [
                'field_id'         => $field_id,
                'content_type'     => 'channel',
                'col_order'        => $max_order + 1,
                'col_type'         => 'toggle',
                'col_label'        => 'Use Background Image',
                'col_name'         => 'use_background_image',
                'col_instructions' => 'Render the hero image as a full-bleed background (text-on-image style). Turn off for a text-only hero on the page body background (e.g. policy pages).',
                'col_required'     => 'n',
                'col_search'       => 'n',
                'col_width'        => 0,
                'col_settings'     => json_encode([
                    'field_default_value' => '1',
                    'field_fmt'           => 'none',
                    'field_show_fmt'      => 'n',
                ]),
            ]);
            $col_id = (int) ee()->db->insert_id();
        }

        // Add the data-table column if not already present (default 'y'
        // so existing rows light up the image-hero variant unchanged).
        $has_col = ee()->db->query(
            "SHOW COLUMNS FROM `{$data_table}` LIKE 'col_id_{$col_id}'"
        )->num_rows();
        if (! $has_col) {
            ee()->db->query("ALTER TABLE `{$data_table}` ADD COLUMN `col_id_{$col_id}` CHAR(1) NULL DEFAULT '1'");
        }
    }

    public function down()
    {
        $hero_grid = ee('Model')->get('ChannelField')
            ->filter('field_name', 'hero_block')
            ->first();
        if (! $hero_grid) {
            return;
        }
        $field_id = (int) $hero_grid->field_id;
        $data_table = 'exp_channel_grid_field_' . $field_id;

        $col = ee()->db->where('field_id', $field_id)
            ->where('col_name', 'use_background_image')
            ->get('grid_columns')->row_array();
        if (! $col) {
            return;
        }
        $col_id = (int) $col['col_id'];

        ee()->db->query("ALTER TABLE `{$data_table}` DROP COLUMN IF EXISTS `col_id_{$col_id}`");
        ee()->db->where('col_id', $col_id)->delete('grid_columns');
    }
}
