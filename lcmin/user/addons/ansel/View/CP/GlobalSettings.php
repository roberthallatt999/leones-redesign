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

/** @var BoldMinded\Ansel\Service\GlobalSettings $globalSettings */
/** @var ExpressionEngine\Service\URL\URLFactory $cpUrl */
/** @var array $excludedItems */
/** @var string $lastKey */

$splitLeft = 'w-7';
$splitRight = 'w-9';
?>

<?=ee('CP/Alert')->getAllInlines()?>

<div class="panel">
    <?=form_open(
        $cpUrl->make('addons/settings/ansel', array(
            'controller' => 'GlobalSettings'
        )),
        array(
            'class' => 'settings'
        )
    )?>
    <div class="panel-heading">
        <div class="form-btns form-btns-top">
            <div class="title-bar title-bar--large">
                <h3 class="title-bar__title"><?=lang('global_settings')?></h3>

                <div class="title-bar__extra-tools">
                    <button
                        class="button button--primary"
                        type="submit"
                        data-shortcut="s"
                        value="Save Settings"
                        data-submit-text="Save Settings"
                        data-work-text="Saving..."
                    >
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <? /* Iterate through settings */ ?>
        <?php foreach ($globalSettings as $key => $setting) : ?>
            <? /* Check for excluded items */ ?>
            <?php
            if (in_array($key, $excludedItems)) {
                continue;
            }
            ?>

            <fieldset>
                <div class="field-instruct ">
                    <label for="<?=$key?>"><?=lang("{$key}")?></label>
                    <em><?=lang("{$key}_explain")?></em>
                </div>
                <div class="field-control">
                    <?php if ($globalSettings->getType($key) === 'bool') : ?>
                        <?php $this->embed('ee:_shared/form/field', array(
                            'grid' => false,
                            'field_name' => $key,
                            'field' => array(
                                'type' => 'yes_no',
                                'value' => $setting
                            )
                        ));?>
                    <?php else : ?>
                        <input
                            <?php if ($globalSettings->getType($key) === 'int') : ?>
                                type="number"
                            <?php else : ?>
                                type="text"
                            <?php endif; ?>
                            name="<?=$key?>"
                            value="<?=$setting?>"
                            <?php if ($globalSettings->getType($key) === 'int') : ?>
                                min="0"
                            <?php endif; ?>
                            <?php if ($key === 'default_image_quality') : ?>
                                max="100"
                            <?php endif; ?>
                            id="<?=$key?>"
                        >
                    <?php endif; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>
    </div>
    <div class="panel-footer">
        <div class="form-btns">
            <button
                class="button button--primary"
                type="submit"
                data-shortcut="s"
                value="Save Settings"
                data-submit-text="Save Settings"
                data-work-text="Saving..."
            >
                Save Settings
            </button>
        </div>
    </div>
    <?=form_close()?>
</div>
