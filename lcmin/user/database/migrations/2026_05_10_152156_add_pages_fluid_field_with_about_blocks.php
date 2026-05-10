<?php

use ExpressionEngine\Service\Migration\Migration;

/**
 * Add a Fluid Field to the `pages` channel and define the first two block
 * types the About page needs:
 *
 *   - Hero Block:           hero_title, hero_subtitle, hero_image
 *   - Text Section Block:   section_running_head, section_title,
 *                           section_paragraph_1, section_image,
 *                           section_paragraph_2
 *
 * The Fluid field (`page_content_blocks`) is attached to the existing
 * "Pages Channel Fields" field group so every page entry can compose its
 * content from blocks. Existing pages-channel fields (top_image, text_area_1,
 * text_area_2) are LEFT IN PLACE for backward compatibility while content
 * migrates over time.
 *
 * Future migrations add more block types as additional pages are wired.
 */
class AddPagesFluidFieldWithAboutBlocks extends Migration
{
    public function up()
    {
        $site_id = 1;

        // Source the wygwam + file-field settings from existing fields with the
        // same types so defaults match the rest of the site.
        $wygwam_blob = ee()->db->select('field_settings')
            ->where('field_name', 'text_area_1')
            ->get('channel_fields')->row('field_settings');
        $wygwam_settings = $wygwam_blob ? unserialize(base64_decode($wygwam_blob)) : [];

        $file_settings = [
            'field_content_type' => 'image',
            'allowed_directories'=> 'all',
            'show_existing'      => 'y',
            'num_existing'       => 50,
            'field_fmt'          => 'none',
            'field_show_fmt'     => 'n',
        ];

        $text_settings = [
            'field_maxl'              => 200,
            'field_content_type'      => 'all',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
        ];

        $textarea_settings = [
            'field_ta_rows'           => 4,
            'field_content_type'      => 'all',
            'field_fmt'               => 'none',
            'field_show_fmt'          => 'n',
            'field_show_smileys'      => 'n',
            'field_show_file_selector'=> 'n',
        ];

        $order = 0;

        // ===== HERO BLOCK FIELD GROUP =====
        $hero_title = $this->makeField($site_id, 'hero_title',    'Hero Title',    'text',     ++$order, $text_settings);
        $hero_sub   = $this->makeField($site_id, 'hero_subtitle', 'Hero Subtitle', 'textarea', ++$order, $textarea_settings);
        $hero_img   = $this->makeField($site_id, 'hero_image',    'Hero Image',    'file',     ++$order, $file_settings);

        $hero_group = ee('Model')->make('ChannelFieldGroup');
        $hero_group->site_id    = $site_id;
        $hero_group->group_name = 'Hero Block';
        $hero_group->short_name = 'hero_block';
        $hero_group->save();
        $hero_group->ChannelFields = [$hero_title, $hero_sub, $hero_img];
        $hero_group->save();

        // ===== TEXT SECTION BLOCK FIELD GROUP =====
        $sec_run   = $this->makeField($site_id, 'section_running_head', 'Section Running Head', 'text',   ++$order, $text_settings);
        $sec_title = $this->makeField($site_id, 'section_title',        'Section Title',        'text',   ++$order, $text_settings);
        $sec_p1    = $this->makeField($site_id, 'section_paragraph_1',  'Section Paragraph 1',  'wygwam', ++$order, $wygwam_settings);
        $sec_img   = $this->makeField($site_id, 'section_image',        'Section Image',        'file',   ++$order, $file_settings);
        $sec_p2    = $this->makeField($site_id, 'section_paragraph_2',  'Section Paragraph 2',  'wygwam', ++$order, $wygwam_settings);

        $section_group = ee('Model')->make('ChannelFieldGroup');
        $section_group->site_id    = $site_id;
        $section_group->group_name = 'Text Section Block';
        $section_group->short_name = 'text_section_block';
        $section_group->save();
        $section_group->ChannelFields = [$sec_run, $sec_title, $sec_p1, $sec_img, $sec_p2];
        $section_group->save();

        // ===== FLUID FIELD =====
        $fluid = ee('Model')->make('ChannelField');
        $fluid->site_id              = $site_id;
        $fluid->field_name           = 'page_content_blocks';
        $fluid->field_label          = 'Page Content Blocks';
        $fluid->field_instructions   = 'Compose this page\'s content from one or more blocks. Add a block, fill in its fields, drag to reorder.';
        $fluid->field_type           = 'fluid_field';
        $fluid->field_required       = 'n';
        $fluid->field_search         = 'n';
        $fluid->field_order          = ++$order;
        $fluid->field_text_direction = 'ltr';
        $fluid->field_fmt            = 'none';
        $fluid->field_show_fmt       = 'n';
        $fluid->field_settings       = [
            'field_channel_fields'       => [],
            'field_channel_field_groups' => [$hero_group->group_id, $section_group->group_id],
        ];
        $fluid->save();

        // ===== ATTACH FLUID FIELD TO PAGES CHANNEL FIELD GROUP =====
        $pages_group = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'Pages Channel Fields')->first();

        if ($pages_group) {
            $existing = $pages_group->ChannelFields->getIds();
            $existing[] = $fluid->field_id;
            $pages_group->ChannelFields = ee('Model')->get('ChannelField', $existing)->all();
            $pages_group->save();
        }
    }

    public function down()
    {
        // Drop Fluid field
        $fluid = ee('Model')->get('ChannelField')
            ->filter('field_name', 'page_content_blocks')->first();
        if ($fluid) {
            $fluid->delete();
        }

        // Drop block field groups
        foreach (['Hero Block', 'Text Section Block'] as $name) {
            $g = ee('Model')->get('ChannelFieldGroup')->filter('group_name', $name)->first();
            if ($g) {
                $g->delete();
            }
        }

        // Drop block fields
        foreach ([
            'hero_title', 'hero_subtitle', 'hero_image',
            'section_running_head', 'section_title', 'section_paragraph_1', 'section_image', 'section_paragraph_2',
        ] as $name) {
            $f = ee('Model')->get('ChannelField')->filter('field_name', $name)->first();
            if ($f) {
                $f->delete();
            }
        }
    }

    private function makeField($site_id, $name, $label, $type, $order, array $settings)
    {
        $f = ee('Model')->make('ChannelField');
        $f->site_id              = $site_id;
        $f->field_name           = $name;
        $f->field_label          = $label;
        $f->field_type           = $type;
        $f->field_required       = 'n';
        $f->field_search         = 'n';
        $f->field_order          = $order;
        $f->field_text_direction = 'ltr';
        $f->field_fmt            = 'none';
        $f->field_show_fmt       = 'n';
        $f->field_settings       = $settings;
        $f->save();
        return $f;
    }
}
