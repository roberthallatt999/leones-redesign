<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_05 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');
        $tableName = $db->dbprefix . 'ansel_images';

        $db->query(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` %s',
            $tableName,
            'description',
            'description',
            'varchar(1024)'
        ));

        $db->query(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` %s',
            $tableName,
            'title',
            'title',
            'varchar(1024)'
        ));

        $columnIndexes = [
            'content_id',
            'field_id',
            'row_id',
            'col_id',
            'content_type',
            'disabled',
            'position',
        ];

        foreach ($columnIndexes as $column) {
            $indexName = $column. '_index';
            $exists = $db->query("SHOW KEYS FROM `{$tableName}` WHERE Key_name='{$indexName}'");

            if ($exists->num_rows() === 0) {
                $db->query(sprintf(
                    'ALTER TABLE `%s` ADD INDEX %s (`%s`)',
                    $tableName,
                    $indexName,
                    $column,
                ));
            }
        }

        $exists = $db->query("SHOW KEYS FROM `{$tableName}` WHERE Key_name='ansel_go_fast_index'");

        if ($exists->num_rows() === 0) {
            $db->query(sprintf(
                'ALTER TABLE `%s` ADD INDEX %s (`%s`)',
                $tableName,
                'ansel_go_fast_index',
                implode('`, `', $columnIndexes),
            ));
        }
    }
}
