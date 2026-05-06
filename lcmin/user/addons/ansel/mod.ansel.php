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
 *  Copyright (c) 2019. BoldMinded, LLC
 *  All rights reserved.
 *
 *  This source is commercial software. Use of this software requires a
 *  site license for each domain it is used on. Use of this software or any
 *  of its source code without express written permission in the form of
 *  a purchased commercial or other license is prohibited.
 *
 *  THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 *  KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 *  IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 *  PARTICULAR PURPOSE.
 *
 *  As part of the license agreement for this software, all modifications
 *  to this source must be submitted to the original author for review and
 *  possible inclusion in future releases. No compensation will be provided
 *  for patches, although where possible we will attribute each contribution
 *  in file revision notes. Submitting such modifications constitutes
 *  assignment of copyright to the original author (Brian Litzinger and
 *  BoldMinded, LLC) for such modifications. If you do not wish to assign
 *  copyright to the original author, your license to  use and modify this
 *  source is null and void. Use of this software constitutes your agreement
 *  to this clause.
 */

/**
 * Class Ansel
 *
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
// @codingStandardsIgnoreStart
class Ansel
// @codingStandardsIgnoreEnd
{
    /**
     * Image uploader (action)
     */
    public function imageUploader()
    {
        ee('ansel:FieldUploaderController')->post();
        exit();
    }

    /**
     * Ansel images tag pair
     *
     * @return string
     */
    public function images()
    {
        $tagParams = ee()->TMPL->tagparams ?? [];
        $tagData = ee()->TMPL->tagdata ?: false;

        // Handle aliases
        if (isset($tagParams['entry_id'])) {
            $tagParams['content_id'] = $tagParams['entry_id'];
        }

        if (isset($tagParams['url_title'])) {
            $entry = ee('Model')->get('ChannelEntry')->filter('url_title', $tagParams['url_title'])->first();

            if ($entry) {
                $tagParams['content_id'] = $entry->entry_id;
            }
        }

        if (isset($tagParams['field_name'])) {
            $field = ee('Model')->get('ChannelField')->filter('field_name', $tagParams['field_name'])->first();

            if ($field) {
                $tagParams['field_id'] = $field->field_id;
            }
        }

        // Run the controller
        return ee('ansel:ImagesTagController')->parse($tagParams, $tagData);
    }
}
