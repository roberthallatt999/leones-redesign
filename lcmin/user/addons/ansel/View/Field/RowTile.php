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
/** @var \BoldMinded\Ansel\Record\Image $row */

$rowId = uniqid();

if (! isset($row)) {
    $row = ee('ansel:Noop');
}
?>

<div class="ansel-grid__row js-ansel-row" data-row-id="<?=$rowId?>">
    <div class="list-item">
        <!-- Used for the file meta edit slide out. Requires the 'fields-upload-chosen' div to be its sibling -->
        <input type="hidden" class="js-file-input" value="{file:<?= $row->file_id ?>:url}" data-id="<?= $row->file_id ?>" />

        <div class="fields-upload-chosen">
            <div class="fields-upload-tools">
                <div class="button-group button-group-small">
                    <?php if ($shouldSort): ?>
                    <span type="button" class="button button--small button--default cursor-move js-grid-reorder-handle ui-sortable-handle">
                        <span class="grid-field__column-tool"><i class="fal fa-fw fa-arrows-alt"></i></span>
                    </span>
                    <?php endif; ?>
                    <?php if ($row->file_id && $shouldShowMetaButton): ?>
                    <span class="js-ansel-edit-meta edit-meta button button--default" title="Edit original file meta data"><i class="fal fa-money-check-pen"></i></span>
                    <?php endif; ?>
                    <span class="js-ansel-crop-row button button--default" title="Crop"><i class="fal fa-crop-alt"></i><span class="hidden">Crop</span></span>
                    <span class="js-ansel-remove-row button button--default button--danger__passive" title="Remove"><i class="fal fa-trash-alt"></i></span>
                </div>
            </div>
            <div class="ansel-grid__image">
                <div class="ansel-grid__image-holder js-ansel-image-holder">
                    <div class="ansel-grid__image-holder-inner js-ansel-image-holder-inner">
                        <img
                            <?php if ($row->_file_location) : ?>
                                <?php
                                $type = pathinfo($row->_file_location, PATHINFO_EXTENSION);
                                $contents = file_get_contents($row->_file_location);
                                $base64 = "data:image/{$type};base64,";
                                $base64 .= base64_encode($contents);
                                ?>
                                src="<?=$base64?>"
                            <?php else : ?>
                                <?php if ($row->getOriginalUrl() === '') : ?>
                                    data-source-file-missing="true"
                                    src="<?=$row->getThumbUrl()?>"
                                <?php else : ?>
                                    src="<?=$row->getOriginalUrl()?>"
                                <?php endif; ?>
                            <?php endif; ?>
                            alt=""
                            class="js-ansel-row-image"
                            style="display: none"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $this->embed('ansel:Field/RowHiddenInputs', array(
        'rowId' => $rowId,
        'row' => $row
    )); ?>

    <div class="ansel-meta-fields">
        <?php if ($fieldSettings->show_title) : ?>
            <label class="ansel-meta-label">
                <?=$fieldSettings->getTitleColumnLabel()?>
                <span class="app-badge label-app-badge js-app-badge">
                    <span class="txt-only">{img:title}</span>
                    <i class="fa-light fa-copy"></i>
                    <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                </span>
                <input
                    type="text"
                    name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][title]"
                    maxlength="255"
                    value="<?=htmlentities((string) $row->title)?>"
                    class="js-ansel-input js-ansel-meta-title"
                />
            </label>
        <?php endif; ?>
        <?php if ($fieldSettings->show_description) : ?>
            <label class="ansel-meta-label">
                <?=$fieldSettings->getDescriptionColumnLabel()?>
                <span class="app-badge label-app-badge js-app-badge">
                    <span class="txt-only">{img:description}</span>
                    <i class="fa-light fa-copy"></i>
                    <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                </span>
                <input
                    type="text"
                    name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][description]"
                    maxlength="255"
                    value="<?=htmlentities((string) $row->description)?>"
                    class="js-ansel-input js-ansel-meta-description"
                />
            </label>
        <?php endif; ?>
        <?php if ($fieldSettings->show_cover && !$singleImageDisplay) : ?>
            <label class="ansel-meta-label">
                <?=$fieldSettings->getCoverColumnLabel()?>
                <span class="app-badge label-app-badge js-app-badge">
                    <span class="txt-only">{img:cover}</span>
                    <i class="fa-light fa-copy"></i>
                    <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                </span>
                <?php
                echo $this->embed('ee:_shared/form/fields/toggle', [
                    'class' => 'js-ansel-input js-ansel-input-cover',
                    'yes_no' => true,
                    'value' => $row->cover ? 'y' : 'n',
                    'disabled' => false,
                    'field_name' => $fieldSettings->field_name . '[ansel_row_id_'. $rowId .'][cover]'
                ]);
                ?>
            </label>
        <?php endif; ?>
    </div>
</div>
