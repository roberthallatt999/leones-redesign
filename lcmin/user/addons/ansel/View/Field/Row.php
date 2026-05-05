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

$isEE6 = false;

if (defined('APP_VER') &&
    version_compare(APP_VER, '6.0.0-b.1', '>=')
) {
    $isEE6 = true;
}

$rowId = uniqid();

// Check if image is going to have neighbors
$imgHasNeighbors = $fieldSettings->show_title || $fieldSettings->show_description;

if (! isset($row)) {
    $row = ee('ansel:Noop');
}

?>

<tr class="js-ansel-row" data-row-id="<?=$rowId?>">
    <td class="grid-field__item-fieldset" style="display: none;">
        <div class="grid-field__item-tools grid-field__item-tools--item-open">
            <a href="" class="grid-field__item-tool js-toggle-grid-item">
                <span class="sr-only">Collapse</span>
                <i class="fal fa-caret-square-up fa-fw"></i>
            </a>

            <button type="button" data-dropdown-offset="0px, -30px" data-dropdown-pos="bottom-end" class="grid-field__item-tool js-dropdown-toggle"><i class="fal fa-fw fa-cog"></i></button>

            <div class="dropdown">
                <a href="" class="dropdown__link js-hide-all-grid-field-items">Collapse All</a>
                <a href="" class="dropdown__link js-show-all-grid-field-items">Expand All</a>
                <div class="dropdown__divider"></div>
                <a href="" class="dropdown__link dropdown__link--danger js-ansel-remove-row" rel="remove_row"><i class="fal fa-fw fa-trash-alt"></i> Remove</a>
            </div>
        </div>

        <div class="field-instruct">
            <label>
                <button type="button" class="js-grid-reorder-handle ui-sortable-handle">
                    <i class="icon--reorder reorder"></i>
                </button>
            </label>
        </div>
    </td>
    <td>
        <div class="grid-field__column-label" role="rowheader">
            <!-- yes "instraction" is a typo in EE core, don't correct the spelling or it will break. -->
            <div class="grid-field__column-label__instraction">
                <label>
                    <?=$fieldSettings->getImageColumnLabel()?>
                    <span class="app-badge label-app-badge js-app-badge">
                        <span class="txt-only">{img:url}</span>
                        <i class="fa-light fa-copy"></i>
                        <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                    </span>
                </label>
            </div>
        </div>
        <div class="ansel-table__image-holder js-ansel-image-holder">
            <div class="ansel-table__image-holder-inner js-ansel-image-holder-inner">
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
            <?php
                $cropButtonClasses = 'ansel-image-toolbar__button ansel-image-toolbar__button--crop';

                if ($isEE6) {
                    $cropButtonClasses .= ' ansel-image-toolbar__button--is-ee-6';
                }
            ?>
            <ul class="ansel-image-toolbar">
                <li class="ansel-image-toolbar__item">
                    <a
                        title="Crop"
                        class="<?=$cropButtonClasses?>"
                    >
                        <span class="ansel-image-toolbar__button-icon-wrapper ansel-image-toolbar__button-icon-wrapper--crop">
                            <?php $this->embed('ansel:Field/Icons/Crop.svg'); ?>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
        <?php $this->embed('ansel:Field/RowHiddenInputs', array(
            'rowId' => $rowId,
            'row' => $row
        )); ?>
    </td>
    <?php if ($fieldSettings->show_title) : ?>
        <td>
            <div class="grid-field__column-label" role="rowheader">
                <div class="grid-field__column-label__instraction">
                    <label>
                        <?=$fieldSettings->getTitleColumnLabel()?>
                        <span class="app-badge label-app-badge js-app-badge">
                            <span class="txt-only">{img:title}</span>
                            <i class="fa-light fa-copy"></i>
                            <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                        </span>
                    </label>
                </div>
            </div>
            <label style="width: 100%">
                <textarea
                    name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][title]"
                    maxlength="1024"
                    rows="4"
                    class="js-ansel-input"
                ><?=htmlentities((string) $row->title)?></textarea>
            </label>
        </td>
    <?php endif; ?>
    <?php if ($fieldSettings->show_description) : ?>
        <td>
            <div class="grid-field__column-label" role="rowheader">
                <div class="grid-field__column-label__instraction">
                    <label>
                        <?=$fieldSettings->getDescriptionColumnLabel()?>
                        <span class="app-badge label-app-badge js-app-badge">
                            <span class="txt-only">{img:description}</span>
                            <i class="fa-light fa-copy"></i>
                            <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                        </span>
                    </label>
                </div>
            </div>
            <label style="width: 100%">
                <textarea
                    name="<?=$fieldSettings->field_name?>[ansel_row_id_<?=$rowId?>][description]"
                    maxlength="1024"
                    rows="4"
                    class="js-ansel-input"
                ><?=htmlentities((string) $row->description)?></textarea>
            </label>
        </td>
    <?php endif; ?>
    <?php if ($fieldSettings->show_cover) : ?>
        <td>
            <div class="grid-field__column-label" role="rowheader">
                <div class="grid-field__column-label__instraction">
                    <label>
                        <?=$fieldSettings->getCoverColumnLabel()?>
                        <span class="app-badge label-app-badge js-app-badge">
                            <span class="txt-only">{img:cover}</span>
                            <i class="fa-light fa-copy"></i>
                            <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                        </span>
                    </label>
                </div>
            </div>
            <label>
                <div class="field-control">
                    <?php
                    echo $this->embed('ee:_shared/form/fields/toggle', [
                        'class' => 'js-ansel-input js-ansel-input-cover',
                        'yes_no' => true,
                        'value' => $row->cover ? 'y' : 'n',
                        'disabled' => false,
                        'field_name' => $fieldSettings->field_name . '[ansel_row_id_'. $rowId .'][cover]'
                    ]);
                    ?>
                </div>
            </label>
        </td>
    <?php endif; ?>
    <td class="grid-field__column--tools">
        <div class="grid-field__column-tools">
            <button type="button" class="button button--small button--default cursor-move js-grid-reorder-handle ui-sortable-handle">
                <span class="grid-field__column-tool"><i class="fal fa-arrows-alt"></i></span>
            </button>
            <button type="button" rel="remove_row" class="button button--small button--default button--danger__passive js-ansel-remove-row">
                <span class="grid-field__column-tool" title="remove row"><i class="fal fa-trash-alt"><span class="hidden">remove row</span></i></span>
            </button>
        </div>
    </td>
</tr>
