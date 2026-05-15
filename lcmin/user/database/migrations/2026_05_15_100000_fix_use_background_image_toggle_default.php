<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Fix the `use_background_image` toggle column on the hero_block Grid.
 *
 * The original migration (2026_05_15_080000_add_use_background_image_column)
 * set `field_default_value` => 'y' in the column settings and used 'y' as
 * the SQL column DEFAULT. That worked for our template conditional
 * ({if content:use_background_image == "0"} matches '0' but not 'y'), but
 * EE's Toggle_ft::validate() only accepts '', '0', '1', or false unless
 * the `yes_no` setting is true. When an entry submits a row carrying the
 * legacy 'y' default (e.g. the just-added hero_block on the contact page),
 * validation fails and the whole entry refuses to save with a generic
 * "There was a problem with one or more Grid fields" error.
 *
 * Fix: switch to EE-canonical '1' (ON) / '0' (OFF). Existing rows are
 * migrated 'y' -> '1' and 'n' -> '0'. The template conditional already
 * checks for "0" so no template change is needed.
 */
class FixUseBackgroundImageToggleDefault extends Migration
{
    public function up()
    {
        $hero_grid = ee('Model')->get('ChannelField')
            ->filter('field_name', 'hero_block')->first();
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
        $data_col = 'col_id_' . $col_id;

        // 1. Update the column's settings JSON to use a valid toggle default.
        ee()->db->where('col_id', $col_id)->update('grid_columns', [
            'col_settings' => json_encode([
                'field_default_value' => '1',
                'field_fmt'           => 'none',
                'field_show_fmt'      => 'n',
            ]),
        ]);

        // 2. Migrate existing data: 'y' -> '1', 'n' -> '0'.
        ee()->db->query("UPDATE `{$data_table}` SET `{$data_col}` = '1' WHERE `{$data_col}` = 'y'");
        ee()->db->query("UPDATE `{$data_table}` SET `{$data_col}` = '0' WHERE `{$data_col}` = 'n'");

        // 3. Update the SQL column DEFAULT so new rows get '1' (ON), matching
        //    the original "default to image hero" intent.
        ee()->db->query("ALTER TABLE `{$data_table}` ALTER COLUMN `{$data_col}` SET DEFAULT '1'");
    }

    public function down()
    {
        // No rollback — reverting to the broken 'y' default would re-introduce
        // the validation failure. If you must roll back, restore a DB snapshot.
    }
}
