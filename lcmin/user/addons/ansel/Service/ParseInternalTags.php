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

namespace BoldMinded\Ansel\Service;

use BoldMinded\Ansel\Utility\RegEx;
use BoldMinded\Ansel\Model\InternalTagParams;

/**
 * Class ParseInternalTags
 */
class ParseInternalTags
{
    /**
     * @var InternalTagParams $internalTagParams
     */
    private $internalTagParams;

    /**
     * Constructor
     *
     * @param InternalTagParams $internalTagParams
     */
    public function __construct(InternalTagParams $internalTagParams)
    {
        $this->internalTagParams = $internalTagParams;
    }

    /**
     * Parse internal tags
     *
     * @param string $tagData
     * @param string $tag
     * @param string $namespace
     * @return \stdClass
     */
    public function parse($tagData, $tag, $namespace = 'img:')
    {
        $regex = RegEx::tag($tag, $namespace);

        // Set up matches and replacements
        $matches = array();
        $tags = array();

        // Run regex
        preg_match_all($regex, $tagData, $matches, PREG_SET_ORDER);

        // Loop through the tag matches
        foreach ($matches as $key => $match) {
            // Matches and params
            $paramMatches = array();

            // Clone the internal tag params model
            $params = clone $this->internalTagParams;

            // Get the tag params
            if (isset($match[1])) {
                // Run regex
                preg_match_all(
                    RegEx::param(),
                    $match[1],
                    $paramMatches,
                    PREG_SET_ORDER
                );

                // Set the params to the array
                foreach ($paramMatches as $paramMatch) {
                    $property = trim($paramMatch[1]);
                    if ($params->hasProperty($property)) {
                        $params->setProperty($property, trim($paramMatch[2]));
                    }
                }
            }

            // Add the params and ID to the tags array
            $uniqueId = uniqid('ansel_', true);
            $tags[$key] = new \stdClass();
            $tags[$key]->tagName = $tag;
            $tags[$key]->match = $match[0];
            $tags[$key] ->id = $uniqueId;
            $tags[$key]->tag = "{{$namespace}{$uniqueId}}";
            $tags[$key]->params = $params;
        }

        // Loop through tags and run replacements
        foreach ($tags as $key => $val) {
            $tagData = preg_replace(
                $regex,
                $val->tag,
                $tagData,
                1
            );
        }

        // Set up return data
        $returnData = new \stdClass();
        $returnData->tagData = $tagData;
        $returnData->tags = $tags;

        return $returnData;
    }
}
