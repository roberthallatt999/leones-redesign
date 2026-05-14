<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Promote the optional Hero Icon (SVG) from a Grid column on the Hero Block
 * to a top-level field on the Pages channel. This lets channel field layouts
 * hide it from non-admin editor roles (channel layouts only operate on
 * top-level fields — they can't reach Grid columns).
 *
 * Trade-off: the icon is now per-page rather than per-Hero-Block-instance.
 * In practice each page has one hero, so the pairing-loss is theoretical.
 *
 * Changes:
 *   - Create textarea ChannelField `page_hero_icon` (field_fmt=none so raw
 *     SVG passes through, paired with raw_output="yes" in the template).
 *   - Attach it to the "Pages Channel Fields" field group (group_id=2).
 *   - Copy the existing About entry's icon value from the Grid column
 *     (exp_channel_grid_field_38.col_id_19) into the new field.
 *   - Drop the `hero_icon` column from the hero_block Grid.
 *
 * After this migration runs, an admin needs to update channel-field layouts
 * for the Pages channel: create or edit the layout assigned to non-admin
 * editor roles and toggle `page_hero_icon` to "Hidden".
 */
class PromoteHeroIconToTopLevel extends Migration
{
    public function up()
    {
        $site_id = 1;

        // ----- Locate the hero_block Grid + its hero_icon column -----
        $hero_grid = ee('Model')->get('ChannelField')
            ->filter('field_name', 'hero_block')->first();
        if (! $hero_grid) {
            throw new \RuntimeException('hero_block Grid field not found.');
        }
        $grid_field_id  = (int) $hero_grid->field_id;
        $grid_data_tbl  = 'channel_grid_field_' . $grid_field_id;

        $icon_col = ee()->db->where('field_id', $grid_field_id)
            ->where('col_name', 'hero_icon')
            ->get('grid_columns')->row_array();
        $icon_col_id = $icon_col ? (int) $icon_col['col_id'] : null;
        $icon_col_data_col = $icon_col_id ? ('col_id_' . $icon_col_id) : null;

        // ----- Read existing icon data per entry, so we can carry it over -----
        // Grid rows store entry_id directly; we want a one-row-per-entry map of
        // the icon value. (If a page has multiple Hero Block instances with
        // different icons, this keeps the first non-empty one.)
        $existing_icons = [];
        if ($icon_col_data_col) {
            $rows = ee()->db
                ->select("entry_id, {$icon_col_data_col} AS svg")
                ->from($grid_data_tbl)
                ->where("({$icon_col_data_col} IS NOT NULL AND {$icon_col_data_col} != '')")
                ->order_by('row_order', 'ASC')
                ->get()->result_array();
            foreach ($rows as $r) {
                $eid = (int) $r['entry_id'];
                if (! isset($existing_icons[$eid]) && $r['svg'] !== '' && $r['svg'] !== null) {
                    $existing_icons[$eid] = $r['svg'];
                }
            }
        }

        // ----- Create the new top-level field -----
        $field = ee('Model')->make('ChannelField');
        $field->site_id              = $site_id;
        $field->field_name           = 'page_hero_icon';
        $field->field_label          = 'Page Hero Icon (SVG)';
        $field->field_instructions   = 'Optional. Paste raw SVG markup to render as a decorative icon above the hero title. Leave blank for no icon. Renders verbatim — no escaping. Admin-only field; hide via channel layouts for non-admin roles.';
        $field->field_type           = 'textarea';
        $field->field_required       = 'n';
        $field->field_search         = 'n';
        $field->field_text_direction = 'ltr';
        $field->field_fmt            = 'none';
        $field->field_show_fmt       = 'n';
        $field->field_settings       = [
            'field_ta_rows'           => 6,
            'field_content_type'      => 'all',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
        ];
        $field->save();
        $new_field_id = (int) $field->field_id;

        // ----- Attach to "Pages Channel Fields" field group -----
        $pages_group = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'Pages Channel Fields')->first();
        if ($pages_group) {
            $existing_ids = $pages_group->ChannelFields->getIds();
            $existing_ids[] = $new_field_id;
            $pages_group->ChannelFields = ee('Model')->get('ChannelField', $existing_ids)->all();
            $pages_group->save();
        }

        // ----- Migrate icon values into the new per-field data table -----
        $new_data_tbl = 'channel_data_field_' . $new_field_id;
        foreach ($existing_icons as $entry_id => $svg) {
            ee()->db->insert($new_data_tbl, [
                'entry_id'                  => $entry_id,
                'field_id_' . $new_field_id => $svg,
                'field_ft_' . $new_field_id => 'none',
            ]);
        }

        // ----- Drop the Grid column (data + columns row) -----
        if ($icon_col_id) {
            ee()->db->query("ALTER TABLE `exp_{$grid_data_tbl}` DROP COLUMN `{$icon_col_data_col}`");
            ee()->db->where('col_id', $icon_col_id)->delete('grid_columns');
        }
    }

    public function down()
    {
        // Drop the new top-level field (Model->delete handles per-field table).
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'page_hero_icon')->first();
        if ($field) {
            $field->delete();
        }

        // NOTE: re-creating the Grid column with its old col_id is not
        // attempted automatically. Restore the pre-promotion DB snapshot
        // (`ddev snapshot restore pre-hero-icon-promotion`) for a true rollback.
    }
}
