<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Install;

use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as QueryBuilder;
use EllisLab\ExpressionEngine\Model\Addon\Fieldtype as FieldTypeRecord;

/**
 * Class FieldTypeUpdaterService
 */
class FieldTypeUpdaterService
{
	/**
	 * @var string $addonVersion
	 */
	private $addonVersion;

	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * Constructor
	 *
	 * @param string $addonVersion
	 * @param RecordBuilder $recordBuilder
	 */
	public function __construct($addonVersion, RecordBuilder $recordBuilder)
	{
		$this->addonVersion = $addonVersion;
		$this->recordBuilder = $recordBuilder;
	}

	/**
	 * Update the field type
	 */
	public function update()
	{
		// Get the FieldType record
		/** @var QueryBuilder $moduleRecord */
		$fieldTypeRecord = $this->recordBuilder->get('Fieldtype');
		$fieldTypeRecord->filter('name', 'ansel');
		$fieldTypeRecord = $fieldTypeRecord->first();

		/** @var FieldTypeRecord $fieldTypeRecord */

		// Make sure we got the field type record
		if (! $fieldTypeRecord) {
			return;
		}

		// Update the version on the field type record
		$fieldTypeRecord->setProperty('version', $this->addonVersion);

		// Save the record
		$fieldTypeRecord->save();
	}
}
