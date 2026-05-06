<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_01 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'class' => 'Ansel_ext',
                'hook' => 'after_file_save',
                'method' => 'after_file_save',
            ],
        ]);

        $prefix = ee('db')->dbprefix;

        // Rename the caption column
        if (!ee('db')->field_exists('description', 'ansel_images')) {
            ee('db')->query("ALTER TABLE `". $prefix ."ansel_images` CHANGE COLUMN `caption` `description` varchar(255) DEFAULT ''");
        }

        // Update any global settings keys, should just be 3
        ee('db')->query("UPDATE `". $prefix ."ansel_settings` SET `settings_key`=(replace(`settings_key`, 'caption', 'description'))");

        // Rename settings for Ansel fields
        $channelFields = ee('Model')->get('ChannelField')->filter('field_type', 'ansel')->all();
        foreach ($channelFields as $field) {
            $settings = $field->field_settings;

            $settings['show_description'] = $settings['show_caption'] ?? 'n';
            $settings['require_description'] = $settings['require_caption'] ?? 'n';
            $settings['description_label'] = $settings['caption_label'] ?? '';

            unset($settings['show_caption']);
            unset($settings['require_caption']);
            unset($settings['caption_label']);

            $field->field_settings = $settings;
            $field->save();
        }

        $gridFields = ee('db')->where('col_type', 'ansel')->get('grid_columns');
        foreach ($gridFields->result_array() as $field) {
            $settings = json_decode($field['col_settings'], true);

            $settings['show_description'] = $settings['show_caption'] ?? 'n';
            $settings['require_description'] = $settings['require_caption'] ?? 'n';
            $settings['description_label'] = $settings['caption_label'] ?? '';

            unset($settings['show_caption']);
            unset($settings['require_caption']);
            unset($settings['caption_label']);

            ee('db')->update('grid_columns', [
                'col_settings' => json_encode($settings)
            ], [
                'col_id' => $field['col_id']
            ]);
        }

        $bloqs = ee('Addon')->get('bloqs');

        if ($bloqs && $bloqs->isInstalled()) {
            $blockFields = ee('db')->where('type', 'ansel')->get('blocks_atomdefinition');
            foreach ($blockFields->result_array() as $field) {
                $settings = json_decode($field['settings'], true);

                $settings['show_description'] = $settings['show_caption'] ?? 'n';
                $settings['require_description'] = $settings['require_caption'] ?? 'n';
                $settings['description_label'] = $settings['caption_label'] ?? '';

                unset($settings['show_caption']);
                unset($settings['require_caption']);
                unset($settings['caption_label']);

                ee('db')->update('blocks_atomdefinition', [
                    'settings' => json_encode($settings)
                ], [
                    'id' => $field['id']
                ]);
            }
        }

        if (bool_config_item('ansel_sync_meta_fields')) {
            $anselFiles = ee('Model')->get('ansel:Image')
                ->filter('title', '!=' ,'')
                ->orFilter('description', '!=' , '')
                ->all();

            foreach ($anselFiles as $anselFile) {
                // Use straight query instead of full model, quicker and don't want to trigger any more events.
                ee('db')
                    ->update('files', [
                        'title' => $anselFile->title,
                        'description' => $anselFile->description,
                    ], [
                        'file_id' => $anselFile->file_id
                    ])
                ;
            }
        }
    }
}
