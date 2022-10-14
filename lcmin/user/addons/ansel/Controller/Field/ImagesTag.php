<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\Field;

use BuzzingPixel\Ansel\Model\ImagesTagParams;
use BuzzingPixel\Ansel\Service\AnselImages\ImagesTag as AnselImagesTag;
use BuzzingPixel\Ansel\Service\NoResults;
use BuzzingPixel\Ansel\Service\ParseInternalTags;

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

	/**
	 * Constructor
	 *
	 * @param ImagesTagParams $imagesTagParams
	 * @param AnselImagesTag $imagesTag
	 * @param NoResults $noResults
	 * @param ParseInternalTags $parseInternalTags
	 * @param \EE_Template $parser
	 */
	public function __construct(
		ImagesTagParams $imagesTagParams,
		AnselImagesTag $imagesTag,
		NoResults $noResults,
		ParseInternalTags $parseInternalTags,
		\EE_Template $parser
	) {
		// Inject dependencies
		$this->imagesTagParams = $imagesTagParams;
		$this->imagesTag = $imagesTag;
		$this->noResults = $noResults;
		$this->parseInternalTags = $parseInternalTags;
		$this->parser = $parser;
	}

	/**
	 * Parse images tag
	 *
	 * @param array $tagParams
	 * @param array|bool $tagData
	 * @return string
	 */
	public function parse(
		$tagParams,
		$tagData
	) {
		// Set up the ImagesTagParams
		$this->imagesTagParams->populate($tagParams);
		$this->imagesTag->populateTagParams($tagParams);

		// Check if we should only run count
		if ($tagData === false || $this->imagesTagParams->count === true) {
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


		/**
		 * Now we can get our variables and finalize things
		 */

		// Get variables
		$vars = $this->imagesTag->getVariables();

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
