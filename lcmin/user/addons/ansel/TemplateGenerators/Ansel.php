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
 * All rights reserved.
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

namespace BoldMinded\Ansel\TemplateGenerators;

use BoldMinded\Ansel\Model\ImagesTagParams;
use ExpressionEngine\Service\TemplateGenerator\AbstractFieldTemplateGenerator;

class Ansel extends AbstractFieldTemplateGenerator
{
    private array $pairFields = [
        'manipulations' => [
            'path',
            'url',
            'width',
            'height',
        ],
    ];

    public function getVariables(): array
    {
        /** @var ImagesTagParams $tagParams */
        $tagParams = ee('ansel:ImagesTagParams');

        $separator = ':';
        $prefix = 'img' . $separator;

        $templateEngine = config_item('default_template_engine');

        $fields[] = 'url';

        if ($templateEngine === 'twig') {
            $prefix = '';
            $separator = '.';
            $fields[] = 'url.resize({width: "100", height="100"})';
        } else {
            $fields[] = 'url:resize width="100" height="100"';
        }

        $filteredFields = array_filter($tagParams->getFields(), function ($value) {
            return !preg_match('/^not_/', $value);
        });

        $vars = $this->getFieldVars(array_merge($fields, $filteredFields), $prefix, $separator);

        return $vars;
    }

    private function getFieldVars(array $fields, $prefix = 'img', $separator = ':', $pairName = ''): array
    {
        foreach ($fields as $fieldName) {
            if (array_key_exists($fieldName, $this->pairFields)) {
                $pairFields = $this->getFieldVars($this->pairFields[$fieldName], $prefix, $separator, $fieldName);

                $vars['fields'][$fieldName] = [
                    'field_name' => $prefix . $fieldName,
                    'is_tag_pair' => true,
                    'fields' => $pairFields['fields'],
                ];

                continue;
            }

            if ($pairName === 'manipulations') {
                $vars['fields'][$fieldName] = [
                    'field_name' => $prefix . '[manipulation_name]' . $separator . $fieldName,
                    'is_tag_pair' => false,
                ];
            } else {
                $vars['fields'][$fieldName] = [
                    'field_name' => $prefix . $fieldName,
                    'is_tag_pair' => false,
                ];
            }
        }

        return $vars;
    }
}
