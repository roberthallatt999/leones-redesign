<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Replace the monolithic `text_section_block` Grid (id=39) with four
 * atomic block types attached directly to `page_content_blocks`:
 *
 *   running_head_block (text)    — small uppercase tagline
 *   heading_block      (text)    — section h2
 *   paragraph_block    (wygwam)  — rich text paragraph
 *   image_block        (file)    — single image
 *
 * Why: editors couldn't reorder content within a Text Section Block (rows
 * had fixed columns), and nesting a Fluid inside another Fluid is forbidden
 * by EE core (fluid_field::accepts_content_type explicitly excludes
 * 'fluid_field'). Atomic blocks at the outer Fluid level give true
 * "page builder" drag-reorder UX, and the blocks become reusable on every
 * page that uses page_content_blocks.
 *
 * Existing About-entry text_section_block content is migrated into the
 * new atomic block instances in the original visual order:
 *   running_head → heading → paragraph_1 → image → paragraph_2
 *
 * After up() runs:
 *   - text_section_block ChannelField (id=39) is dropped (cleans up its
 *     per-field grid table + grid_columns rows via Model delete events).
 *   - The orphaned fluid_field_data row for field_id=39 is removed.
 *   - Fluid 37 settings list the 5 new block types alongside hero_block.
 */
class AtomizeTextSectionBlock extends Migration
{
    public function up()
    {
        $site_id   = 1;
        $about_eid = 13;
        $fluid_id  = 37;

        // Lookup pages-channel field group (we'll need it... actually no —
        // the atomic blocks are inner Fluid block types, NOT direct channel
        // fields, so they don't go on a group. They're referenced by
        // `field_channel_fields` in the Fluid settings.)

        // Source wygwam settings from an existing wygwam field so editor
        // config matches the rest of the site.
        $wygwam_blob = ee()->db->select('field_settings')
            ->where('field_name', 'text_area_1')
            ->get('channel_fields')->row('field_settings');
        $wygwam_settings = $wygwam_blob ? unserialize(base64_decode($wygwam_blob)) : [];

        $text_settings = [
            'field_maxl'              => 200,
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
        ];
        $file_settings = [
            'field_content_type'  => 'image',
            'allowed_directories' => 'all',
            'show_existing'       => 'y',
            'num_existing'        => 50,
            'field_fmt'           => 'none',
            'field_show_fmt'      => 'n',
        ];

        // ----- Create the four atomic block-type fields -----
        $running_head = $this->makeField($site_id, 'running_head_block', 'Running Head Block',
            'Renders a small uppercase tagline above a heading or section.',
            'text', $text_settings);
        $heading = $this->makeField($site_id, 'heading_block', 'Heading Block',
            'Renders a section heading (h2) in the page content.',
            'text', $text_settings);
        $paragraph = $this->makeField($site_id, 'paragraph_block', 'Paragraph Block',
            'Renders one rich-text paragraph in the page content. Add multiple paragraph blocks for separated copy.',
            'wygwam', $wygwam_settings);
        $image = $this->makeField($site_id, 'image_block', 'Image Block',
            'Renders a single content image. Add another for additional images.',
            'file', $file_settings);

        // ----- Migrate existing text_section_block grid data to the new
        //       Fluid block instances on the About entry -----
        $tsb_row = ee()->db->where('entry_id', $about_eid)
            ->get('channel_grid_field_39')->row_array();

        if ($tsb_row) {
            $next_order = (int) ee()->db
                ->select_max('order', 'max_order')
                ->where('entry_id', $about_eid)
                ->where('fluid_field_id', $fluid_id)
                ->get('fluid_field_data')->row('max_order');

            $migrations = [
                ['field' => $running_head, 'value' => $tsb_row['col_id_13'] ?? ''],
                ['field' => $heading,      'value' => $tsb_row['col_id_14'] ?? ''],
                ['field' => $paragraph,    'value' => $tsb_row['col_id_15'] ?? ''],
                ['field' => $image,        'value' => $tsb_row['col_id_16'] ?? ''],
                ['field' => $paragraph,    'value' => $tsb_row['col_id_17'] ?? ''],
            ];

            foreach ($migrations as $m) {
                if (! $m['value']) continue;  // skip empty columns
                $next_order++;
                $this->createFluidBlockInstance($fluid_id, $about_eid, $m['field'], $next_order, $m['value']);
            }
        }

        // ----- Remove the old text_section_block Fluid block-instance row
        //       (field_id=39 → soon to be deleted) -----
        ee()->db->where('entry_id', $about_eid)
            ->where('field_id', 39)
            ->delete('fluid_field_data');

        // ----- Delete the old text_section_block ChannelField (cleans up
        //       its per-field grid data table + grid_columns rows) -----
        $tsb = ee('Model')->get('ChannelField')
            ->filter('field_name', 'text_section_block')->first();
        if ($tsb) {
            $tsb->delete();
        }

        // ----- Update Fluid 37 settings: replace [38, 39] with
        //       [38, running_head, heading, paragraph, image] -----
        $fluid = ee('Model')->get('ChannelField', $fluid_id)->first();
        $fluid->field_settings = [
            'field_channel_fields' => [
                (string) 38,                          // hero_block (unchanged)
                (string) $running_head->field_id,
                (string) $heading->field_id,
                (string) $paragraph->field_id,
                (string) $image->field_id,
            ],
            'field_channel_field_groups' => [],
        ];
        $fluid->save();
    }

    public function down()
    {
        // Drop the four atomic-block fields.
        foreach (['running_head_block', 'heading_block', 'paragraph_block', 'image_block'] as $name) {
            $f = ee('Model')->get('ChannelField')->filter('field_name', $name)->first();
            if ($f) {
                $f->delete();
            }
        }
        // Note: re-creating text_section_block + restoring its data is
        // non-trivial. Use `ddev snapshot restore pre-atomic-blocks` for
        // a true rollback.
    }

    /**
     * Create a Fluid block instance: one row in exp_fluid_field_data plus
     * one row in the inner field's per-field data table (entry_id=0 for
     * Fluid-owned data; field_data_id links back to fluid_field_data.id).
     */
    private function createFluidBlockInstance($fluid_id, $entry_id, $inner_field, $order, $value)
    {
        $inner_id = (int) $inner_field->field_id;
        $data_table = 'channel_data_field_' . $inner_id;

        ee()->db->insert($data_table, [
            'entry_id'              => 0,
            'field_id_' . $inner_id => $value,
            'field_ft_' . $inner_id => 'none',
        ]);
        $data_id = (int) ee()->db->insert_id();

        ee()->db->insert('fluid_field_data', [
            'fluid_field_id' => $fluid_id,
            'entry_id'       => $entry_id,
            'field_id'       => $inner_id,
            'field_data_id'  => $data_id,
            'order'          => $order,
            'field_group_id' => null,
            'group'          => null,
        ]);
    }

    private function makeField($site_id, $name, $label, $instructions, $type, array $settings)
    {
        $f = ee('Model')->make('ChannelField');
        $f->site_id              = $site_id;
        $f->field_name           = $name;
        $f->field_label          = $label;
        $f->field_instructions   = $instructions;
        $f->field_type           = $type;
        $f->field_required       = 'n';
        $f->field_search         = 'n';
        $f->field_text_direction = 'ltr';
        $f->field_fmt            = $type === 'wygwam' ? ($settings['field_fmt'] ?? 'none') : 'none';
        $f->field_show_fmt       = 'n';
        $f->field_settings       = $settings;
        $f->save();
        return $f;
    }
}
