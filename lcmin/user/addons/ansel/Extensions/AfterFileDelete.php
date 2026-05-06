<?php

namespace BoldMinded\Ansel\Extensions;

use BoldMinded\Ansel\Record\Image;
use ExpressionEngine\Model\File\Directory;
use ExpressionEngine\Model\File\File;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class AfterFileDelete extends AbstractRoute
{
    public function process(File|Directory $file, array $values = [])
    {
        if ($file instanceof Directory) {
            return;
        }

        // If a saved image is deleted from the FM, cleanup the Ansel table too.
        try {
            /** @var Image $anselFile */
            $anselFile = ee('Model')->get('ansel:Image')
                ->filter('file_id', $file->file_id)
                ->first();

            if ($anselFile) {
                $anselFile->delete();

                ee('ansel:Logger')->developer(
                    sprintf(
                        '[Ansel] An image was deleted from the File Manager that was used in an Ansel field: %s',
                        $anselFile->filename
                    ),
                    true
                );
            }
        } catch (\Exception $exception) {
            //ee('CP/Alert')->makeBanner()
            //    ->asAlert()
            //    ->cannotClose()
            //    ->withTitle('')
            //    ->addToBody('')
            //    ->defer();
        }
    }
}
