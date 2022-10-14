<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/** @var \BuzzingPixel\Ansel\Model\FieldSettings $fieldSettings */
/** @var string $rowId */
/** @var \BuzzingPixel\Ansel\Record\Image $row */

?>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][ansel_image_id]"
	class="js-ansel-input js-ansel-input-image-id"
	value="<?=$row->id?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][ansel_image_delete]"
	class="js-ansel-input js-ansel-input-image-delete"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][source_file_id]"
	class="js-ansel-input js-ansel-source-file-id"
	value="<?=$row->original_file_id?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][original_location_type]"
	class="js-ansel-input js-ansel-original-location-type"
	<?php if ($row->original_location_type) : ?>
	value="<?=$row->original_location_type?>"
	<?php else : ?>
	value="<?=$fieldSettings->getUploadDirectory()->type?>"
	<?php endif; ?>
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][upload_location_id]"
	class="js-ansel-input js-ansel-upload-location-id"
	value="<?=$row->upload_location_id?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][upload_location_type]"
	class="js-ansel-input js-ansel-upload-location-type"
	value="<?=$row->upload_location_type?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][filename]"
	class="js-ansel-input js-ansel-filename"
	value="<?=$row->filename?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][extension]"
	class="js-ansel-input js-ansel-extension"
	value="<?=$row->extension?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][file_location]"
	class="js-ansel-input js-ansel-input-file-location"
	<?php if ($row->_file_location) : ?>
	value="<?=$row->_file_location?>"
	<?php endif; ?>
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][x]"
	class="js-ansel-input js-ansel-input-x"
	value="<?=$row->x?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][y]"
	class="js-ansel-input js-ansel-input-y"
	value="<?=$row->y?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][width]"
	class="js-ansel-input js-ansel-input-width"
	value="<?=$row->width?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][height]"
	class="js-ansel-input js-ansel-input-height"
	value="<?=$row->height?>"
>

<input
	type="hidden"
	name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][order]"
	class="js-ansel-input js-ansel-input-order"
	value="<?=$row->position?>"
>
