<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use BuzzingPixel\Ansel\Utility\RegEx;
use BuzzingPixel\Ansel\Model\InternalTagParams;

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
