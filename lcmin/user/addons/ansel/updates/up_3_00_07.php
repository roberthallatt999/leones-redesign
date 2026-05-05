<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_07 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        $settingExists = $db->where([
            'settings_key' => 'default_webp',
        ])->get('ansel_settings');

        if ($settingExists->num_rows() === 0) {
            $db->insert('ansel_settings', [
                'settings_type' => 'bool',
                'settings_key' => 'default_webp',
                'settings_value' => 'n'
            ]);
        }
    }
}
