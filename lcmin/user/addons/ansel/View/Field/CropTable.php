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

$isEE6 = false;

if (defined('APP_VER') &&
    version_compare(APP_VER, '6.0.0-b.1', '>=')
) {
    $isEE6 = true;
}

?>

<div>
    <div class="ansel-bg-overlay"></div>
    <table class="ansel-crop-table">
        <tbody>
            <tr class="ansel-crop-table__row">
                <td class="ansel-crop-table__cell">
                    <img src="" alt="" class="ansel-crop-table__img js-ansel-crop-image">
                    <ul class="toolbar ansel-tool-bar">
                        <li class="remove js-cancel-crop">
                            <?php
                                $cancelButtonWrapperClasses = 'ansel-tool-bar__button-icon-wrapper ansel-tool-bar__button-icon-wrapper--cancel';
                                if ($isEE6) {
                                    $cancelButtonWrapperClasses .= ' ansel-tool-bar__button-icon-wrapper--cancel-ee-6';
                                }
                            ?>
                            <a class="ansel-tool-bar__anchor ansel-tool-bar__anchor--cancel">
                                <span class="<?=$cancelButtonWrapperClasses?>">
                                    <?php $this->embed('ansel:Field/Icons/Close.svg'); ?>
                                </span>
                            </a>
                        </li>
                        <li class="approve js-approve-crop">
                            <?php
                                $approveButtonWrapperClasses = 'ansel-tool-bar__button-icon-wrapper ansel-tool-bar__button-icon-wrapper--approve';
                                if ($isEE6) {
                                    $approveButtonWrapperClasses .= ' ansel-tool-bar__button-icon-wrapper--approve-ee-6';
                                }
                            ?>
                            <a class="ansel-tool-bar__anchor ansel-tool-bar__anchor--approve">
                                <span class="<?=$approveButtonWrapperClasses?>">
                                    <?php $this->embed('ansel:Field/Icons/Checkmark.svg'); ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </td>
            </tr>
        </tbody>
    </table>
</div>
