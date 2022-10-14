<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * @property string $version
 * @property string $downloadUrl
 * @property \DateTime $date
 * @property array $notes
 * @property bool $new
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
class UpdateFeedItem extends Model
{
	/**
	 * @var \EE_Typography $eeTypography
	 */
	private $eeTypography;

	/**
	 * Constructor
	 *
	 * @param \EE_Typography $eeTypography
	 */
	public function __construct(\EE_Typography $eeTypography)
	{
		// Run the parent constructor method
		parent::__construct();

		// Inject dependencies
		$this->eeTypography = $eeTypography;
	}

	/**
	 * Clone object
	 */
	public function __clone()
	{
		if ($this->date) {
			$this->date = clone $this->date;
		}
	}

	/**
	 * Model properties
	 */
	protected $version;
	protected $downloadUrl;
	protected $date;
	protected $notes;
	protected $new;

	/**
	 * @var array $_typed_columns
	 */
	// @codingStandardsIgnoreStart
	protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
		'version' => 'string',
		'downloadUrl' => 'string',
		'new' => 'bool'
	);

	/**
	 * Date setter
	 *
	 * @param string|\DateTime $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__date($val) // @codingStandardsIgnoreEnd
	{
		if (! $val instanceof \DateTime) {
			$val = new \DateTime($val);
		}

		$this->setRawProperty('date', $val);
	}

	/**
	 * Notes setter
	 *
	 * @param array $val
	 */
	// @codingStandardsIgnoreStart
	protected function set__notes($val) // @codingStandardsIgnoreEnd
	{
		// Make sure value is an array
		if (gettype($val) === 'array') {
			$this->setRawProperty('notes', $val);
		}
	}

	/**
	 * Notes getter
	 */
	// @codingStandardsIgnoreStart
	protected function get__notes() // @codingStandardsIgnoreEnd
	{
		// Make sure an array is always returned
		return gettype($this->notes) === 'array' ? $this->notes : array();
	}

	/**
	 * Get notes markdown
	 */
	public function getNotesMarkdown()
	{
		$itemsToRemove = array();

		foreach ($this->notes as $note) {
			if (strpos($note, '#') === 0) {
				$itemsToRemove[] = '[' . substr($note, 2) . '] ';
			}
		}

		$mdString = html_entity_decode(
			implode("\n\n", $this->notes),
			ENT_QUOTES
		);

		foreach ($itemsToRemove as $item) {
			$mdString = str_replace($item, '', $mdString);
		}

		return $this->eeTypography->markdown($mdString);
	}
}
