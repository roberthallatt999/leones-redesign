<?php

// @codingStandardsIgnoreStart

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
/** @var array $langArray */
/** @var array $fieldSettingsArray */
/** @var string $uploadKey */
/** @var string $uploadUrl */
/** @var string $fileChooserLink */
/** @var \ExpressionEngine\Service\Model\Collection $rows */

// Check if image is going to have neighbors
$imgHasNeighbors = $fieldSettings->show_title || $fieldSettings->show_description;

?>

<?php echo ee('CP/Alert')->get(sprintf('ansel-field-alerts-%s-%s', $fieldSettings->type, $fieldSettings->field_id)); ?>

<div
    class="ansel-field<?php if (in_array($fieldSettings->type, array('grid', 'blocks'))) : ?> js-ansel-grid-field<?php else : ?> js-ansel-field<?php endif; ?>"
    data-field-settings='<?=json_encode($fieldSettingsArray)?>'
    data-lang='<?=json_encode($langArray)?>'
>
    <?php if ($shouldShowTileMetaFields && !$singleImageDisplay): ?>
        <div class="ansel-toggle-meta">
            <a href="#" class="button button--default button--xsmall js-ansel-show-meta">Show Meta Fields</a>
            <a href="#" class="button button--default button--xsmall hidden js-ansel-hide-meta">Hide Meta Fields</a>
        </div>
    <?php endif; ?>

    <?php // Temp placeholder until the JS initializes ?>
    <label class="field-loading js-ansel-loading">
        <?=lang('loading')?><span></span>
    </label>

    <template class="js-ansel-field-template">

        <input
            type="hidden"
            name="<?=$fieldSettings->field_name?>[placeholder]"
            value="placeholder"
            class="js-ansel-field-input-placeholder"
        >

        <div
            class="file-field__dropzone ansel-dropzone dropzone js-ansel-dropzone js-ansel-hide-max"
            data-upload-key="<?=$uploadKey?>"
            data-upload-url="<?=$uploadUrl?>"
        >
            <div class="file-field__dropzone-button js-ansel-hide-max">
                <?=$fileChooserLink?>
            </div>
        </div>

        <div class="js-ansel-messages"></div>

        <?php if ($shouldShowTile): ?>
            <div class="ansel-grid">
                <div class="js-ansel-table <?= ($shouldSort ? 'js-ansel-sortable-grid' : '') ?>">
                    <div class="ansel-grid__body js-ansel-body<?= $singleImageDisplay ? ' two-columns' : '' ?>">
                        <?php foreach ($rows as $row) : ?>
                            <?php $this->embed('ansel:Field/RowTile', array(
                                'row' => $row,
                                'singleImageDisplay' => $singleImageDisplay,
                                'shouldShowMetaButton' => $shouldShowMetaButton,
                                'shouldSort' => $shouldSort,
                            )); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="grid-field entry-grid">
                <div class="table-responsive<?php if (! $rows->count()) : ?> js-hide<?php endif; ?>">
                    <table class="grid-field__table ansel-table js-ansel-table">
                        <thead>
                            <tr>
                                <th>
                                    <?=lang('image')?>
                                    <span class="app-badge label-app-badge js-app-badge">
                                        <span class="txt-only">{img:url}</span>
                                        <i class="fa-light fa-copy"></i>
                                        <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                                    </span>
                                </th>
                                <?php if ($fieldSettings->show_title) : ?>
                                    <th>
                                        <?=$fieldSettings->getTitleColumnLabel();?>
                                        <span class="app-badge label-app-badge js-app-badge">
                                            <span class="txt-only">{img:title}</span>
                                            <i class="fa-light fa-copy"></i>
                                            <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                                        </span>
                                    </th>
                                <?php endif; ?>
                                <?php if ($fieldSettings->show_description) : ?>
                                    <th>
                                        <?=$fieldSettings->getDescriptionColumnLabel();?>
                                        <span class="app-badge label-app-badge js-app-badge">
                                            <span class="txt-only">{img:description}</span>
                                            <i class="fa-light fa-copy"></i>
                                            <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                                        </span>
                                    </th>
                                <?php endif; ?>
                                <?php if ($fieldSettings->show_cover && !$singleImageDisplay) : ?>
                                    <th>
                                        <?=$fieldSettings->getCoverColumnLabel();?>
                                        <span class="app-badge label-app-badge js-app-badge">
                                            <span class="txt-only">{img:cover}</span>
                                            <i class="fa-light fa-copy"></i>
                                            <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
                                        </span>
                                    </th>
                                <?php endif; ?>
                                <th class="grid-field__column-remove"></th>
                            </tr>
                        </thead>
                        <tbody class="js-ansel-body ui-sortable">
                            <?php foreach ($rows as $row) : ?>
                                <?php $this->embed('ansel:Field/Row', array(
                                    'row' => $row,
                                    'singleImageDisplay' => $singleImageDisplay,
                                    'shouldShowMetaButton' => $shouldShowMetaButton,
                                    'shouldSort' => $shouldSort,
                                )); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <template class="js-ansel-template__row">
            <?php if ($shouldShowTile): ?>
                <?php $this->embed('ansel:Field/RowTile'); ?>
            <?php else: ?>
                <?php $this->embed('ansel:Field/Row'); ?>
            <?php endif; ?>
        </template>

        <template class="js-ansel-template__crop-table">
            <?php $this->embed('ansel:Field/CropTable'); ?>
        </template>

    </template>

</div>
