<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Convert the Page Content Blocks (Fluid Field) block types from
 * field-group-based blocks to Grid-based blocks.
 *
 * Why: field-group blocks inside Fluid REQUIRE a {fields} iterator that
 * repeats chrome once per field. Grid blocks expose named columns inside one
 * {content}{/content} pair, matching the EE 7 docs' `img_card` example, so a
 * Hero Block can render as one composed <header> wrapper.
 *
 * Net schema change:
 *   - Add Grid field `hero_block`            (cols: hero_title, hero_subtitle, hero_image)
 *   - Add Grid field `text_section_block`    (cols: section_running_head, section_title,
 *                                                  section_paragraph_1, section_image,
 *                                                  section_paragraph_2)
 *   - Update Fluid `page_content_blocks` settings: drop the two field-group
 *     block types, add the two new Grid fields as block types
 *   - Delete legacy field groups "Hero Block" + "Text Section Block" and
 *     their 8 inner fields (`hero_*`, `section_*`)
 *   - Hide legacy `top_image`, `text_area_1`, `text_area_2` on the About
 *     entry only (other unported pages keep editing them)
 *
 * Existing about-entry Hero Block data is intentionally dropped — content
 * will be re-entered in the new Grid via the CP. The block has only one
 * instance with three trivial values (title, subtitle, image picker).
 */
class ConvertBlocksToGridFields extends Migration
{
    public function up()
    {
        $site_id    = 1;
        $about_eid  = 13;
        $fluid_name = 'page_content_blocks';

        ee()->load->model('grid_model');

        // ----- Snapshot Fluid field id so we keep it -----
        $fluid = ee('Model')->get('ChannelField')
            ->filter('field_name', $fluid_name)->first();
        if (! $fluid) {
            throw new \RuntimeException("Fluid field `{$fluid_name}` not found.");
        }
        $fluid_id = $fluid->field_id;

        // ----- Settings reused for columns -----
        $wygwam_blob = ee()->db->select('field_settings')
            ->where('field_name', 'text_area_1')
            ->get('channel_fields')->row('field_settings');
        $wygwam_settings = $wygwam_blob ? unserialize(base64_decode($wygwam_blob)) : [];
        // wygwam reads field_text_direction from col_settings inside a Grid too.
        $wygwam_settings['field_text_direction'] = 'ltr';

        // Grid columns store per-column settings as a JSON blob; unlike top-level
        // ChannelFields, there's no separate field_text_direction column on
        // exp_grid_columns, so the text/textarea/wygwam fieldtypes read it from
        // col_settings. Omitting it triggers an E_WARNING on the column form.
        $text_settings = [
            'field_maxl'              => 200,
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
            'field_text_direction'    => 'ltr',
        ];
        $textarea_settings = [
            'field_ta_rows'           => 4,
            'field_content_type'      => 'all',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
            'field_text_direction'    => 'ltr',
        ];
        $file_settings = [
            'field_content_type'  => 'image',
            'allowed_directories' => 'all',
            'show_existing'       => 'y',
            'num_existing'        => 50,
            'field_fmt'           => 'none',
            'field_show_fmt'      => 'n',
        ];

        // ----- Create Grid: hero_block -----
        $hero_grid_id = $this->makeGridField(
            $site_id,
            'hero_block',
            'Hero Block',
            'Composes a full-bleed hero section: title, subtitle, and background image.',
            [
                ['hero_title',    'Hero Title',    'text',     $text_settings],
                ['hero_subtitle', 'Hero Subtitle', 'textarea', $textarea_settings],
                ['hero_image',    'Hero Image',    'file',     $file_settings],
            ]
        );

        // ----- Create Grid: text_section_block -----
        $section_grid_id = $this->makeGridField(
            $site_id,
            'text_section_block',
            'Text Section Block',
            'Composes a centered text section with running head, title, two paragraphs, and an image.',
            [
                ['section_running_head', 'Section Running Head', 'text',     $text_settings],
                ['section_title',        'Section Title',        'text',     $text_settings],
                ['section_paragraph_1',  'Section Paragraph 1',  'wygwam',   $wygwam_settings],
                ['section_image',        'Section Image',        'file',     $file_settings],
                ['section_paragraph_2',  'Section Paragraph 2',  'wygwam',   $wygwam_settings],
            ]
        );

        // ----- Update Fluid field settings to point at the new Grids -----
        $fluid = ee('Model')->get('ChannelField', $fluid_id)->first();
        $fluid->field_settings = [
            'field_channel_fields'       => [(string) $hero_grid_id, (string) $section_grid_id],
            'field_channel_field_groups' => [],
        ];
        $fluid->save();

        // ----- Drop old block-instance rows for entry 13 (their fields are about to be deleted) -----
        ee()->db->where('entry_id', $about_eid)
            ->where_in('field_id', [29, 30, 31, 32, 33, 34, 35, 36])
            ->delete('fluid_field_data');

        // ----- Drop old field groups (Hero Block id=6, Text Section Block id=7) -----
        foreach (['Hero Block', 'Text Section Block'] as $group_name) {
            $g = ee('Model')->get('ChannelFieldGroup')->filter('group_name', $group_name)->first();
            if ($g) {
                $g->delete();
            }
        }

        // ----- Drop old inner fields (the legacy field-group members) -----
        foreach ([
            'hero_title', 'hero_subtitle', 'hero_image',
            'section_running_head', 'section_title', 'section_paragraph_1', 'section_image', 'section_paragraph_2',
        ] as $name) {
            $f = ee('Model')->get('ChannelField')->filter('field_name', $name)->first();
            if ($f) {
                $f->delete();
            }
        }

        // ----- Hide legacy fields on the About entry only -----
        $legacy_ids = ee('Model')->get('ChannelField')
            ->fields('field_id')
            ->filter('field_name', 'IN', ['top_image', 'text_area_1', 'text_area_2'])
            ->all()->pluck('field_id');

        foreach ($legacy_ids as $fid) {
            $exists = ee()->db->where('entry_id', $about_eid)
                ->where('field_id', $fid)
                ->count_all_results('channel_entry_hidden_fields');
            if (! $exists) {
                ee()->db->insert('channel_entry_hidden_fields', [
                    'entry_id' => $about_eid,
                    'field_id' => $fid,
                ]);
            }
        }
    }

    public function down()
    {
        $about_eid = 13;

        // Un-hide legacy fields on About
        $legacy_ids = ee('Model')->get('ChannelField')
            ->fields('field_id')
            ->filter('field_name', 'IN', ['top_image', 'text_area_1', 'text_area_2'])
            ->all()->pluck('field_id');
        if ($legacy_ids) {
            ee()->db->where('entry_id', $about_eid)
                ->where_in('field_id', $legacy_ids)
                ->delete('channel_entry_hidden_fields');
        }

        // Drop the two Grid fields (this triggers GridFieldtype delete-of cleanup
        // via Model events for the per-field grid table)
        ee()->load->model('grid_model');
        foreach (['hero_block', 'text_section_block'] as $name) {
            $f = ee('Model')->get('ChannelField')->filter('field_name', $name)->first();
            if ($f) {
                $fid = $f->field_id;
                $f->delete();
                // Belt-and-suspenders: drop grid columns + table if model didn't
                ee()->grid_model->delete_field($fid, 'channel');
            }
        }

        // Re-creating the field-group structure is non-trivial and out of scope
        // for an automatic rollback. If a rollback is needed, restore the
        // pre-migration DB snapshot (`ddev snapshot restore pre-grid-blocks-migration`).
    }

    /**
     * Create a Grid ChannelField with the given columns. Returns the new field_id.
     *
     * Programmatic Grid creation needs two steps the CP form normally chains:
     *   1. Save the ChannelField row (auto-makes settings).
     *   2. Call grid_model->create_field() to provision the per-field data table.
     *   3. Call grid_model->save_col_settings() per column to create both the
     *      exp_grid_columns row and the col_id_N column in the data table.
     */
    private function makeGridField($site_id, $name, $label, $instructions, array $columns)
    {
        $f = ee('Model')->make('ChannelField');
        $f->site_id              = $site_id;
        $f->field_name           = $name;
        $f->field_label          = $label;
        $f->field_instructions   = $instructions;
        $f->field_type           = 'grid';
        $f->field_required       = 'n';
        $f->field_search         = 'n';
        $f->field_text_direction = 'ltr';
        $f->field_fmt            = 'none';
        $f->field_show_fmt       = 'n';
        $f->field_settings       = [
            'grid_min_rows'   => 0,
            'grid_max_rows'   => '',
            'allow_reorder'   => 'n',
            'vertical_layout' => 'y',
            'row_counter'     => 'n',
        ];
        $f->save();

        // Provision per-field grid data table
        ee()->grid_model->create_field($f->field_id, 'channel');

        // Add columns in declared order
        $order = 0;
        foreach ($columns as [$col_name, $col_label, $col_type, $col_settings]) {
            ee()->grid_model->save_col_settings([
                'field_id'         => $f->field_id,
                'content_type'     => 'channel',
                'col_order'        => $order++,
                'col_type'         => $col_type,
                'col_label'        => $col_label,
                'col_name'         => $col_name,
                'col_instructions' => '',
                'col_required'     => 'n',
                'col_search'       => 'n',
                'col_width'        => 0,
                'col_settings'     => json_encode($col_settings),
            ], false, 'channel');
        }

        return $f->field_id;
    }
}
