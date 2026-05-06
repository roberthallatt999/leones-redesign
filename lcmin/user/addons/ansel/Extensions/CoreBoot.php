<?php

namespace BoldMinded\Ansel\Extensions;

use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class CoreBoot extends AbstractRoute
{
    public function process()
    {
        if (REQ !== 'CP') {
            return;
        }

        $usages = ee()->session->flashdata('ansel_usages');

        if (!$usages || (is_array($usages) && count($usages) === 0)) {
            return;
        }

        foreach ($usages as $usage) {
            $usageExists = ee('db')
                ->where($usage)
                ->get('file_usage');

            if ($usageExists->num_rows() === 0) {
                ee('db')->insert('file_usage', $usage);
            }
        }

        $this->updateFilesTotalRecords(array_column($usages, 'file_id'));
    }

    /**
     * Copied from core ContentModel.php since it is a protected static :(
     *
     * Recount stats for file usage
     *
     * @param array $file_ids
     * @return void
     */
    private function updateFilesTotalRecords($file_ids = [])
    {
        if (!empty($file_ids)) {
            $updateQuery = 'UPDATE exp_files SET total_records = (SELECT COUNT(exp_file_usage.file_id) FROM exp_file_usage WHERE exp_file_usage.file_id = exp_files.file_id AND exp_file_usage.file_id IN (' . implode(', ', $file_ids) . ')) WHERE exp_files.file_id IN (' . implode(', ', $file_ids) . ')';
            ee('db')->query($updateQuery);
        }
    }
}
