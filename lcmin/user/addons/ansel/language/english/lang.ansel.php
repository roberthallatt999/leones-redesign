<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Add-ons
 * @category    Ansel
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/ansel
 * @license
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

// @codingStandardsIgnoreStart
$lang = array(
    'settings' => 'Settings',
    'global_settings' => 'Global Settings',
    'updates' => 'Updates',
    'license' => 'License',
    'default_host' => 'Default host',
    'default_host_explain' => 'URL to serve images from (great for serving images from CDN by default)',
    'default_max_qty' => 'Default maximum quantity',
    'default_max_qty_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent setting higher or lower max quantity)',
    'default_image_quality' => 'Default image quality',
    'default_image_quality_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent setting higher or lower quality)',
    'default_jpg' => 'Default force JPG setting',
    'default_jpg_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_webp' => 'Default force WebP setting',
    'default_webp_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_retina' => 'Default retina mode',
    'default_retina_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_show_title' => 'Default display title field',
    'default_show_title_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_show_description' => 'Default display description field',
    'default_show_description_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_show_cover' => 'Default display cover field',
    'default_show_cover_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'hide_source_save_instructions' => 'Hide the Upload/Save directory instructions when setting up a new field?',
    'hide_source_save_instructions_explain' => 'When set to no, a brief explanation of how to make best use of the Upload/Save directory paradigm will appear above those options when creating a new field. If you already know how this works it can be annoying and you may wish to hide it.',
    'update' => 'Update',
    'updating' => 'Updating',
    'settings_updated' => 'Settings Updated',
    'settings_updated_success' => 'Your settings have been updated successfully!',
    'ansel_updates' => 'Ansel Updates',
    'ansel_license' => 'Ansel License',
    'your_license_key' => 'Your license key',
    'license_updated' => 'License Updated',
    'license_updated_success' => 'Your license key has been saved successfully!',
    'ansel_needs_license' => 'Ansel Needs a License Key',
    'no_license' => 'Thanks so much for purchasing Ansel. All you need to do now is {{startlink}}enter the license key{{endlink}} from your purchase.',
    'upload_save_dir_explanation' => 'Upload, Save, and Live Preview directories explained',
    'upload_save_dir_hide' => 'This message can be hidden in the Ansel settings',
    'upload_save_dir_explain_upload' => 'The upload directory is where raw source images (un-cropped and unmodified) are uploaded and stored. Images in this directory or uploaded to this directory will always be visible when selecting/uploading images to the field.',
    'upload_save_dir_explain_save' => 'The save directory is where Ansel will save and store the captured images. Images are named with the Ansel image ID and timestamp. Images in this directory are transient &mdash; they can come and go as the fields are updated and images are added and removed. The save directory is not meant to be a user-serviceable directory and is not seen by the user when adding/uploading images to the Ansel field.',
    'upload_save_dir_explain_different_sources' => 'It is strongly recommended that you not use the same directory for both Upload and Save. Best practice is to create a separate directory for Ansel to save images to.',
    'upload_preview_dir_explain' => 'If you\'re using the Live Preview feature it is <b>strongly</b> recommended to create a separate upload location that Ansel can use to save temporary files to while viewing an entry in Live Preview. <b>All files in this directory will be deleted after an entry that contains an Ansel field is saved.</b> It is also recommended to use a local directory (not S3 for instance) for faster Live Previews, and to use the same directory for all Ansel fields. ',
    'upload_directory' => 'Upload Directory',
    'upload_directory_explain' => 'Where to upload source images',
    'choose_a_directory' => 'Choose a directory...',
    'save_directory' => 'Save Directory',
    'save_directory_explain' => 'Where to save captured images',
    'preview_directory' => 'Live Preview Directory',
    'preview_directory_explain' => 'Where images are temporarily saved when viewing an entry in Live Preview mode',
    'min_quantity' => 'Min Quantity',
    'optional' => 'Optional',
    'max_quantity' => 'Max Quantity',
    'image_quality' => 'Image Quality',
    'specify_jpeg_image_quality' => 'Specify JPEG image quality (1 - 100)',
    'force_jpeg' => 'Force JPEG',
    'force_jpeg_explain' => 'Force the captured image to save as JPEG',
    'force_webp' => 'Force WebP',
    'force_webp_explain' => 'Force the captured image to save as WebP',
    'retina_mode' => 'Retina Mode',
    'retina_mode_explain' => 'Double dimensions for 2x output',
    'min_width' => 'Min Width',
    'min_height' => 'Min Height',
    'max_width' => 'Max Width',
    'max_height' => 'Max Height',
    'crop_ratio' => 'Crop Ratio',
    'crop_ratio_explain' => 'Constrain image ratio if applicable (1:1, 2:1, 4:3, 16:9). Please note you should make sure your min/max width/height are not in conflict with your crop ratio.',
    'display_title_field' => 'Display title field',
    'display_description_field' => 'Display description field',
    'display_cover_field' => 'Display cover field',
    'customize_title_label' => 'Customize title label',
    'eg_alt_text' => 'e.g. Alt Text',
    'eg_16_9' => 'e.g. 16:9',
    'customize_description_label' => 'Customize description label',
    'eg_image_description' => 'e.g. Image Description',
    'customize_cover_label' => 'Customize cover label',
    'eg_favorite' => 'e.g. Favorite',
    'require_title_field' => 'Require title field?',
    'require_description_field' => 'Require description field?',
    'require_cover_field' => 'Require cover field?',
    'min_width_cannot_be_greater_than_max_width' => 'Min Width cannot be greater than Max Width',
    'min_height_cannot_be_greater_than_max_height' => 'Min Height cannot be greater than Max Height',
    'specify_crop_width_height' => 'Please specify crop ratio in <b>width:height</b> format using only numbers',
    'ee_directories' => 'ExpressionEngine Directories',
    'default_require_title' => 'Default require title',
    'default_require_title_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_require_description' => 'Default require description',
    'default_require_description_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_title_label' => 'Default customize title label',
    'default_title_label_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_description_label' => 'Default customize description label',
    'default_description_label_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_require_cover' => 'Default require cover',
    'default_require_cover_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_cover_label' => 'Default customize cover label',
    'default_cover_label_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'default_prepend_to_table' => 'Default prepend new rows to field',
    'default_prepend_to_table_explain' => 'Default value when adding a new image to a field. If enabled new rows will be added to the top of the table instead of at the bottom (does not affect existing fields or prevent choosing a different setting)',
    'prepend_to_table' => 'Prepend new rows to field',
    'prepend_to_table_explain' => 'New images will be added as the top row instead of added to the bottom.',
    'tile_view' => 'Use Tile View',
    'tile_view_explain' => 'The Ansel field will use a more compact tile layout, however, the Title, Description, and Cover fields, if enabled, will not be displayed by default. You will need to toggle their display.',
    'default_tile_view' => 'Default Tile View',
    'default_tile_view_explain' => 'Default value when creating new Ansel fields (does not affect existing fields or prevent choosing a different setting)',
    'assets_directories' => 'Assets Directories',
    'not_negative_number' => 'Must not be a negative number',
    'max_not_less_than_min' => 'Max quantity must not be less than min quantity',
    'some_data_did_not_validate' => 'Some data did not validate. Please use the back button on your browser.',
    'drag_images_to_upload' => 'Drop File(s) Here to Upload',
    'browser_does_not_support_drag_and_drop' => 'Your browser does not support drag and drop file uploads.',
    'please_use_fallback_form' => 'Please use the fallback form below to upload your images',
    'file_too_big' => 'File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.',
    'invalid_file_type' => "You can't upload files of this type.",
    'cancel_upload' => 'Cancel upload',
    'cancel_upload_confirmation' => 'Are you sure you want to cancel this upload?',
    'remove_file' => 'Remove file',
    'you_cannot_upload_any_more_files' => "You can't upload any more files.",
    'min_image_dimensions_not_met' => 'Minimum image dimensions not met',
    'min_image_dimensions_not_met_width_only' => ' Image must be at least {{minWidth}}px wide.',
    'min_image_dimensions_not_met_height_only' => ' Image must be at least {{minHeight}}px tall.',
    'min_image_dimensions_not_met_width_and_height' => ' Image must be at least {{minWidth}}px wide by {{minHeight}}px tall.',
    'image' => 'Image',
    'title' => 'Title',
    'description' => 'Description',
    'cover' => 'Cover',
    'choose_an_existing_image' => 'Choose an existing image',
    'choose_existing_images' => 'Choose existing images',
    'must_add_1_image' => 'You must add at least 1 image to this field',
    'must_add_qty_images' => 'You must add at least {{qty}} images to this field',
    'must_add_1_more_image' => 'You must add at least 1 more image to this field',
    'must_add_qty_more_images' => 'You must add at least {{qty}} more images to this field',
    'field_over_limit_1' => 'This field is limited to 1 image. All images uploaded beyond that will not be displayed.',
    'field_over_limit_qty' => 'This field is limited to {{qty}} images. All images uploaded beyond that will not be displayed.',
    'file_is_not_an_image' => 'The selected file is not an image',
    'field_requires_at_least_1_image' => 'This field requires at least 1 image',
    'field_requires_at_least_x_images' => 'This field requires at least {{amount}} images',
    'x_field_required_for_each_image' => 'The {{field}} field is required for each image',
    'field_requires_cover' => 'The {{field}} field must be selected on one image',
    'source_image_missing' => 'The source image that was uploaded for this crop has gone missing. It may have been deleted in the file manager. Because of that, this image is no longer editable.',
    'prevent_upload_over_max' => 'Prevent file uploads when max quantity reached',
    'prevent_upload_over_max_explain' => 'Normally, Ansel will allow uploads beyond max quantity gray them out to indicate they will not be displayed. This is great for preparing images for later. But rarely you need to keep those images from uploading at all.',

    'missing_images_title' => 'Missing Images',
    'missing_images_desc' => 'The following images were assigned to this field, but no longer exist in the File Manager for an unknown reason (someone likely deleted them).',
    'missing_images_log' => '[Ansel]: The following image no longer exists in the File Manager for an unknown reason (someone likely deleted it). entry_id: %s, file_id: %s, file_name: %s',

    'missing_upload_directory_title' => 'Missing Upload Directory',
    'missing_upload_directory_desc' => 'The requested upload directory #%s no longer exists.',

    'missing_upload_location_title' => 'Missing Upload Location',
    'missing_upload_location_desc' => 'The requested upload location #%s no longer exists.',

    'directory_is_symlink' => 'Possible symlink detected',
    'directory_is_symlink_desc' => 'The %s directory appears to be a symlink. For best results, and to avoid errors, it is recommended not to use symlinks to file directories.',

    'no_upload_directory' => 'No Upload Directory',
    'no_upload_directory_desc' => 'This field\'s settings need to be updated and have assigned Upload directory.',

    'no_save_directory' => 'No Save Directory',
    'no_save_directory_desc' => 'This field\'s settings need to be updated and have assigned Save directory.',

    'unique_directory' => 'Upload, Save, and Preview directories must be unique.',

    'invalid_source' => 'Invalid File Source',
    'invalid_source_desc' => 'Ansel no longer supports %s as a valid file source. Please switch to the native ExpressionEngine File Manager. You can <a href="https://docs.boldminded.com/ansel/faqs#does-ansel-support-the-assets-field-type">read more about this change in the documentation</a>.',

    '' => '',
);
