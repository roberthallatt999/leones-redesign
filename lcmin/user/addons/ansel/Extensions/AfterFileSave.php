<?php

namespace BoldMinded\Ansel\Extensions;

use ExpressionEngine\Model\File\Directory;
use ExpressionEngine\Model\File\File;
use ExpressionEngine\Model\File\FileSystemEntity;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class AfterFileSave extends AbstractRoute
{
    public function process(File|FileSystemEntity|Directory $file, array $values = [])
    {
        if (!bool_config_item('ansel_sync_meta_fields')) {
            return;
        }

        if ($file instanceof Directory) {
            return;
        }

        $anselFile = ee('Model')->get('ansel:Image')
            ->filter('file_id', $file->file_id)
            ->first();

        if ($anselFile) {
            $anselFile->title = $file->title;
            $anselFile->description = $file->description;

            $anselFile->save();
        }
    }
}
