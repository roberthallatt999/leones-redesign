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

namespace BoldMinded\Ansel\Controller\Field;

use BoldMinded\Ansel\Model\ImagesTagParams;
use BoldMinded\Ansel\Service\AnselImages\ImagesTag as AnselImagesTag;
use BoldMinded\Ansel\Service\NoResults;
use BoldMinded\Ansel\Service\ParseInternalTags;

/**
 * Class ImagesTag
 */
class ImagesTag
{
    /**
     * @var ImagesTagParams $imagesTagParams
     */
    private $imagesTagParams;

    /**
     * @var AnselImagesTag $imagesTag
     */
    private $imagesTag;

    /**
     * @var NoResults $noResults
     */
    private $noResults;

    /**
     * @var ParseInternalTags $parseInternalTags
     */
    private $parseInternalTags;

    /**
     * @var \EE_Template $parser
     */
    private $parser;

    private bool $isNativeTemplateEngine;

    public function __construct(
        ImagesTagParams $imagesTagParams,
        AnselImagesTag $imagesTag,
        NoResults $noResults,
        ParseInternalTags $parseInternalTags,
        \EE_Template $parser,
        bool $isNativeTemplateEngine,
    ) {
        // Inject dependencies
        $this->imagesTagParams = $imagesTagParams;
        $this->imagesTag = $imagesTag;
        $this->noResults = $noResults;
        $this->parseInternalTags = $parseInternalTags;
        $this->parser = $parser;
        $this->isNativeTemplateEngine = $isNativeTemplateEngine;
    }

    /**
     * Parse images tag
     */
    public function parse(
        array $tagParams,
        string|bool $tagData,
        array|string $fieldData = '',
        array $settings = [],
    ): string {
        // Set up the ImagesTagParams
        $this->imagesTagParams->populate($tagParams);
        $this->imagesTag->populateTagParams($tagParams);

        // Check if we should only run count
        if (
            $this->isNativeTemplateEngine &&
            ($tagData === false || $this->imagesTagParams->count === true)
        ) {
            return $this->imagesTag->count();
        }

        /**
         * Parse internal tags
         */

        // Start an array for internal tags
        $internalTags = array();

        // Parse {img:url:resize} tags
        $parsedTags = $this->parseInternalTags->parse(
            $tagData,
            'url:resize',
            $this->imagesTagParams->namespace
        );

        // Set tagData
        $tagData = $parsedTags->tagData;

        // Set internal tags
        $internalTags = array_merge($internalTags, $parsedTags->tags);

        // Add internal tags to ImagesTag service
        $this->imagesTag->populateInternalTags($internalTags);

        if ($fieldData && is_string($fieldData) && ee('LivePreview')->hasEntryData()) {
            $fieldData = json_decode($fieldData, true);

            /** @var FieldSettings $fieldSettings */
            $fieldSettingsModel = ee('ansel:FieldSettingsModel');
            $fieldSettingsModel->fill($settings);

            if ($fieldSettingsModel->getPreviewDirectory() === null) {
                return 'Ansel can not display images. Preview Directory is undefined.';
            }
        }

        if (is_string($fieldData) && $fieldData !== '') {
            $fieldData = json_decode($fieldData, true);
        }

        // Make sure we're sending an array b/c that's what getVariables expects.
        // I don't know in which scenarios EE will send a string or null, but it doesn't
        // surprise me b/c how inconsistent EE's core code is and how little typechecking it does.
        if (
            $fieldData === '' ||
            $fieldData === null ||
            json_last_error() !== JSON_ERROR_NONE
        ) {
            $fieldData = [];
        }

        // Get variables
        $vars = $this->imagesTag->getVariables($fieldData, $settings, $tagParams);

        // Check for no result
        if (! $vars) {
            return $this->noResults->parse(
                $tagData,
                $this->imagesTagParams->namespace
            );
        }

        // Return parsed variables tag data
        return $this->parser->parse_variables($tagData, $vars);
    }
}
