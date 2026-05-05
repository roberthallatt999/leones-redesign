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

/** @var \BoldMinded\Ansel\Model\FieldSettings $fieldSettings */
/** @var string $rowId */
/** @var \BoldMinded\Ansel\Record\Image $row */

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
