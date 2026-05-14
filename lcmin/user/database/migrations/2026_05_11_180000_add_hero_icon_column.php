<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add an optional `hero_icon` textarea column to the Hero Block Grid so
 * editors can paste raw SVG/HTML per page when a hero needs a decorative
 * icon above the title. Pages that don't need one leave it blank and the
 * template skips the wrapper.
 *
 * IMPLEMENTATION NOTE — direct SQL, not the EE Model/Grid_lib path.
 *
 * The natural way to add a Grid column programmatically is
 * grid_model->save_col_settings(), which then calls
 * api_channel_fields->set_datatype() to add the col_id_N column to the
 * per-field data table. But that path requires a fully-bootstrapped
 * fieldtype handler registry, which isn't available inside the migration
 * CLI runner — it crashes with either "No such property: api_channel_fields"
 * (no library load) or "TypeError: Argument #1 must be of type EE_Fieldtype,
 * null given" (library loaded, but textarea fieldtype not registered).
 *
 * The earlier 2026_05_11_173000 migration only got away with using
 * grid_model->save_col_settings() because it ALSO created the parent
 * ChannelField via ee('Model'), which side-effect-loads the fieldtype
 * registry. Adding a column to an EXISTING Grid doesn't trigger that load.
 *
 * Rather than reverse-engineer the EE bootstrap, we apply the two writes
 * directly: an INSERT into exp_grid_columns and an ALTER TABLE on the
 * per-field grid data table. This matches exactly what the Model layer
 * would have written.
 */
class AddHeroIconColumn extends Migration
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
            ->where('col_name', 'hero_icon')
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
                'col_type'         => 'textarea',
                'col_label'        => 'Hero Icon (SVG)',
                'col_name'         => 'hero_icon',
                'col_instructions' => 'Optional. Paste raw SVG (or other inline HTML) to render above the title. Leave blank for no icon. Renders verbatim — no escaping.',
                'col_required'     => 'n',
                'col_search'       => 'n',
                'col_width'        => 0,
                'col_settings'     => json_encode([
                    'field_ta_rows'           => 6,
                    'field_content_type'      => 'all',
                    'field_fmt'               => 'none',
                    'field_show_fmt'          => 'n',
                    'field_show_smileys'      => 'n',
                    'field_show_file_selector'=> 'n',
                    'field_text_direction'    => 'ltr',
                ]),
            ]);
            $col_id = (int) ee()->db->insert_id();
        }

        // Add the data-table column if not already present
        $has_col = ee()->db->query(
            "SHOW COLUMNS FROM `{$data_table}` LIKE 'col_id_{$col_id}'"
        )->num_rows();
        if (! $has_col) {
            ee()->db->query("ALTER TABLE `{$data_table}` ADD COLUMN `col_id_{$col_id}` TEXT NULL");
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
            ->where('col_name', 'hero_icon')
            ->get('grid_columns')->row_array();
        if (! $col) {
            return;
        }
        $col_id = (int) $col['col_id'];

        ee()->db->query("ALTER TABLE `{$data_table}` DROP COLUMN IF EXISTS `col_id_{$col_id}`");
        ee()->db->where('col_id', $col_id)->delete('grid_columns');
    }
}
