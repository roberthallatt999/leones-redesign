<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'class' => 'Ansel_ext',
                'hook' => 'after_channel_entry_save',
                'method' => 'after_channel_entry_save',
            ],
            [
                'class' => 'Ansel_ext',
                'hook' => 'core_boot',
                'method' => 'core_boot',
            ],
        ]);

        $db = ee('db');

        // Remove the license pinging
        $db
            ->where([
                'class' => 'Ansel',
                'method' => 'licensePing',
            ])
            ->delete('actions');

        $prefix = $db->dbprefix;
        $tableName = $prefix . 'ansel_images';

        if (!$db->field_exists('directory_id', 'ansel_images')) {
            $db->query("ALTER TABLE `{$tableName}` ADD `directory_id` int(10) NOT NULL DEFAULT 0 AFTER `upload_location_id`");
        }

        // Add Publisher columns
        if (!$db->field_exists('publisher_lang_id', 'ansel_images')) {
            $db->query("ALTER TABLE `{$tableName}` ADD `publisher_lang_id` int(4) NOT NULL DEFAULT 1 AFTER `content_id`");
        }
        if (!$db->field_exists('publisher_status', 'ansel_images')) {
            $db->query("ALTER TABLE `{$tableName}` ADD `publisher_status` varchar(24) NOT NULL DEFAULT 'open' AFTER `publisher_lang_id`");
        }

        $settingExists = $db->where([
            'settings_key' => 'default_prepend_to_table',
        ])->get('ansel_settings');

        if ($settingExists->num_rows() === 0) {
            $db->insert('ansel_settings', [
                'settings_type' => 'bool',
                'settings_key' => 'default_prepend_to_table',
                'settings_value' => 'n'
            ]);
        }

        $settingExists = $db->where([
            'settings_key' => 'default_tile_view',
        ])->get('ansel_settings');

        if ($settingExists->num_rows() === 0) {
            $db->insert('ansel_settings', [
                'settings_type' => 'bool',
                'settings_key' => 'default_tile_view',
                'settings_value' => 'y'
            ]);
        }
    }
}
