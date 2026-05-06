<?php

namespace BoldMinded\Ansel\Commands;

use ExpressionEngine\Cli\Cli;

class CommandMigrateAssets extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'migrate-assets';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'ansel:migrate-assets';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Assists in migrating Ansel from Assets to the native File Manager';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Assists in migrating Ansel from Assets to the native File Manager';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php ansel:migrate:assets';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upload_directory,dir:' => 'Upload Directory ID',
    ];

    /**
     * Run the command
     */
    public function handle(): void
    {
        if (!ee('Addon')->get('assets')?->isInstalled()) {
            return;
        }

        $directoryId = $this->option('--dir');

        $files = ee('db')
            ->where(['upload_location_id' => $directoryId])
            ->get('files');

        if ($files->num_rows() === 0) {
            $this->output->outln(sprintf(
                '<<red>>Invalid directory ID - #%d<<reset>>',
                $directoryId,
            ));
        }

        foreach ($files->result() as $file) {
            $fileId = $file->file_id;
            $fileName = $file->file_name;

            $assetsFile = ee('db')->where([
                'file_name' => $file->file_name,
                'filedir_id' => $directoryId,
            ])->get('assets_files');

            if ($assetsFile->num_rows() === 0) {
                $this->output->outln(sprintf(
                    '<<yellow>>No files found in Assets matching %s. Skipping.<<reset>>',
                    $fileName
                ));

                continue;
            }

            if ($assetsFile->num_rows() > 1) {
                $this->output->outln(sprintf(
                    '<<yellow>>Multiple files found in Assets matching %s. Skipping.<<reset>>',
                    $fileName
                ));

                continue;
            }

            //$cleanFileName = pathinfo($fileName, PATHINFO_FILENAME);
            //$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

            $originalFileId = $assetsFile->row('file_id');

            $existsInAnsel = ee('db')->where([
                'assets_file_id' => 0,
                'original_file_id' => $originalFileId,
            ])->get('ansel_images');

            if ($existsInAnsel->num_rows() === 0) {
                $this->output->outln(sprintf(
                    '<<yellow>>No matching updates found in Ansel for file #%d - %s<<reset>>',
                    $fileId,
                    $fileName
                ));

                continue;
            }

            $this->output->outln(sprintf(
                '<<green>>Updating file #%d - %s<<reset>>',
                $fileId,
                $fileName
            ));

            ee('db')->where([
                'assets_file_id' => 0,
                'original_file_id' => $originalFileId,
            ])->update('ansel_images', [
                'assets_file_id' => $originalFileId,
                'original_file_id' => $fileId,
            ]);
        }
    }
}
