<?php

use ExpressionEngine\Service\Migration\Migration;

class RegisterTimelineUploadDir extends Migration
{
    /**
     * Execute the migration
     *
     * - Ensure /images/uploads/timeline/ exists on disk (creates if missing).
     * - Register an EE upload directory "Timeline Images" pointing at it.
     * - Update milestone_image (about_timeline channel) field_settings so the
     *   Ansel field stores into the new upload directory.
     */
    public function up()
    {
        $relative_path = 'images/uploads/timeline/';

        // 1. Ensure the directory exists on disk
        $absolute_path = FCPATH . $relative_path;
        if (!is_dir($absolute_path)) {
            @mkdir($absolute_path, 0775, true);
        }

        // 2. Insert an upload pref (idempotent)
        $existing = ee('Model')->get('UploadDestination')
            ->filter('name', 'Timeline Images')->first();

        if (! $existing) {
            $upload = ee('Model')->make('UploadDestination');
            $upload->site_id            = 1;
            $upload->name               = 'Timeline Images';
            $upload->adapter            = 'local';
            $upload->server_path        = '{base_path}' . $relative_path;
            $upload->url                = '{base_url}' . $relative_path;
            $upload->allowed_types      = 'img';
            $upload->allow_subfolders   = 'n';
            $upload->subfolders_on_top  = 'y';
            $upload->default_modal_view = 'list';
            $upload->save();
            $upload_id = $upload->id;
        } else {
            $upload_id = $existing->id;
        }

        // 3. Update milestone_image field_settings to point at the new upload dir
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'milestone_image')->first();

        if ($field) {
            $blob = ee()->db->select('field_settings')
                ->where('field_id', $field->field_id)
                ->get('channel_fields')->row('field_settings');
            $settings = $blob ? unserialize(base64_decode($blob)) : [];
            if (!is_array($settings)) {
                $settings = [];
            }
            $settings['upload_directory'] = 'ee:' . $upload_id;
            $settings['save_directory']   = 'ee:' . $upload_id;
            $field->field_settings = $settings;
            $field->save();
        }
    }

    /**
     * Rollback the migration
     */
    public function down()
    {
        // Restore milestone_image to the original (top_image) settings
        $field = ee('Model')->get('ChannelField')
            ->filter('field_name', 'milestone_image')->first();
        if ($field) {
            $blob = ee()->db->select('field_settings')
                ->where('field_name', 'top_image')
                ->get('channel_fields')->row('field_settings');
            $settings = $blob ? unserialize(base64_decode($blob)) : [];
            if (is_array($settings) && !empty($settings)) {
                $field->field_settings = $settings;
                $field->save();
            }
        }

        // Remove the upload destination
        $upload = ee('Model')->get('UploadDestination')
            ->filter('name', 'Timeline Images')->first();
        if ($upload) {
            $upload->delete();
        }
    }
}
