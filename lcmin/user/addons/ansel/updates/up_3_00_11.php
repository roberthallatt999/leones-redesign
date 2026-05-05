<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_11 extends AbstractUpdate
{
    public function doUpdate()
    {
        if (!ee('db')->field_exists('assets_file_id', 'ansel_images')) {
            ee()->load->dbforge();

            $fields = [
                'assets_file_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'default' => 0
                ],
            ];

            ee()->dbforge->add_column('ansel_images', $fields);
        }
    }
}
