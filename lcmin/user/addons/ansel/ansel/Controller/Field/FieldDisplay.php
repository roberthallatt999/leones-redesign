<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\Field;

use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use BuzzingPixel\Ansel\Service\GlobalSettings;
use BuzzingPixel\Ansel\Model\FieldSettings as FieldSettingsModel;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use BuzzingPixel\Ansel\Service\UploadKeys;
use BuzzingPixel\Ansel\Service\Sources\SourceRouter;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Class FieldDisplay
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class FieldDisplay
{
	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * @var Collection $collection
	 */
	private $collection;

	/**
	 * @var GlobalSettings $globalSettings
	 */
	private $globalSettings;

	/**
	 * @var FieldSettingsModel $fieldSettings
	 */
	protected $fieldSettings;

	/**
	 * @var ViewFactory $viewFactory
	 */
	protected $viewFactory;

	/**
	 * @var UploadKeys $uploadKeys
	 */
	protected $uploadKeys;

	/**
	 * @var SourceRouter $sourceRouter
	 */
	protected $sourceRouter;

	/**
	 * @var int $siteId
	 */
	protected $siteId;

	/**
	 * @var bool $assetsSource
	 */
	protected $isCP;

	/**
	 * Constructor
	 *
	 * @param RecordBuilder $recordBuilder
	 * @param Collection $collection
	 * @param GlobalSettings $globalSettings
	 * @param FieldSettingsModel $fieldSettings
	 * @param ViewFactory $viewFactory
	 * @param UploadKeys $uploadKeys
	 * @param SourceRouter $sourceRouter
	 * @param int $siteId
	 * @param bool $isCP
	 * @param array $rawFieldSettings
	 */
	public function __construct(
		RecordBuilder $recordBuilder,
		Collection $collection,
		GlobalSettings $globalSettings,
		FieldSettingsModel $fieldSettings,
		ViewFactory $viewFactory,
		UploadKeys $uploadKeys,
		SourceRouter $sourceRouter,
		$siteId = 1,
		$isCP = true,
		$rawFieldSettings = array()
	) {
		// Populate the model
		$fieldSettings->set($rawFieldSettings);

		// Inject dependencies
		$this->recordBuilder = $recordBuilder;
		$this->collection = $collection;
		$this->globalSettings = $globalSettings;
		$this->fieldSettings = $fieldSettings;
		$this->viewFactory = $viewFactory;
		$this->uploadKeys = $uploadKeys;
		$this->sourceRouter = $sourceRouter;
		$this->siteId = $siteId;
		$this->isCP = $isCP;
	}

	/**
	 * Get method
	 *
	 * @param int $contentId
	 * @param int $rowId
	 * @param int $colId
	 * @param array $postBackData
	 * @return mixed
	 */
	public function get(
		$contentId = null,
		$rowId = null,
		$colId = null,
		$postBackData = array()
	) {
		// Create lang array
		$langArray = array(
			'drag_images_to_upload',
			'browser_does_not_support_drag_and_drop',
			'please_use_fallback_form',
			'file_too_big',
			'invalid_file_type',
			'cancel_upload',
			'cancel_upload_confirmation',
			'remove_file',
			'you_cannot_upload_any_more_files',
			'min_image_dimensions_not_met',
			'must_add_1_image',
			'must_add_qty_images',
			'must_add_1_more_image',
			'must_add_qty_more_images',
			'field_over_limit_1',
			'field_over_limit_qty',
			'file_is_not_an_image',
			'source_image_missing'
		);

		// Populate lang array
		$populatedLang = array();
		foreach ($langArray as $key) {
			$populatedLang[$key] = lang($key);
		}

		// Replace straight quotes with placeholders
		foreach ($populatedLang as $key => $val) {
			$populatedLang[$key] = str_replace(
				'\'',
				'{{quotePlaceholder}}',
				$val
			);
		}

		// Retinize the model
		$this->fieldSettings->retinizeReturnValues();

		// Get specific min requirements
		$translate = false;
		if ($this->fieldSettings->min_width && $this->fieldSettings->min_height) {
			$translate = 'min_image_dimensions_not_met_width_and_height';
		} elseif ($this->fieldSettings->min_width) {
			$translate = 'min_image_dimensions_not_met_width_only';
		} elseif ($this->fieldSettings->min_height) {
			$translate = 'min_image_dimensions_not_met_height_only';
		}

		// Check if we should translate
		if ($translate) {
			$populatedLang['min_image_dimensions_not_met'] = str_replace(
				array(
					'{{minWidth}}',
					'{{minHeight}}'
				),
				array(
					$this->fieldSettings->min_width,
					$this->fieldSettings->min_height
				),
				lang($translate)
			);
		}

		// Get the upload directory type
		$type = $this->fieldSettings->getUploadDirectory()->type;

		// Get the file chooser link
		$fileChooserLink = '';
		if ($this->isCP) {
			// Set the source type on the source router
			$this->sourceRouter->setSource($type);

			// Get the file chooser link
			$fileChooserLink = $this->sourceRouter->getFileChooserLink(
				$this->fieldSettings->getUploadDirectory()->identifier
			);
		}

		// Check if we have postback data
		if ($postBackData) {
			// Unset the placeholder
			unset($postBackData['placeholder']);

			// Property map
			$propMap = array(
				'ansel_image_id' => 'id',
				'ansel_image_delete' => '_delete',
				'source_file_id' => 'original_file_id',
				'original_location_type' => 'original_location_type',
				'upload_location_id' => 'upload_location_id',
				'upload_location_type' => 'upload_location_type',
				'filename' => 'filename',
				'extension' => 'extension',
				'file_location' => '_file_location',
				'x' => 'x',
				'y' => 'y',
				'width' => 'width',
				'height' => 'height',
				'order' => 'position',
				'title' => 'title',
				'caption' => 'caption',
				'cover' => 'cover'
			);

			// Create an array for the records
			$recordArray = array();

			// Iterate over data
			foreach ($postBackData as $data) {
				// Make a record
				$anselRecord = $this->recordBuilder->make('ansel:Image');

				// Set properties
				foreach ($data as $key => $val) {
					$anselRecord->{$propMap[$key]} = $val;
				}

				// Add the record to the array
				$recordArray[] = $anselRecord;
			}

			// Add items to the collection
			$this->collection->__construct($recordArray);

			// Set rows
			$rows = $this->collection;
		} else {
			// Get row query builder
			$rows = $this->recordBuilder->get('ansel:Image');

			// Filter the query builder
			$rows->filter('site_id', $this->siteId);
			$rows->filter('content_id', $contentId);
			$rows->filter('field_id', $this->fieldSettings->field_id);
			$rows->filter('content_type', $this->fieldSettings->type);

			// Filter by row ID if applicable
			if ($rowId && $colId) {
				$rows->filter('row_id', $rowId);
				$rows->filter('col_id', $colId);
			} else {
				$rows->filter('row_id', 'IN', array(
					0,
					''
				));
				$rows->filter('col_id', 'IN', array(
					0,
					''
				));
			}

			// Order the query builder
			$rows->order('position', 'asc');

			// Get the rows
			$rows = $rows->all();
		}

		// Return the view
		return $this->viewFactory->make('ansel:Field/Field')
			->render(array(
				'langArray' => $populatedLang,
				'fieldSettings' => $this->fieldSettings,
				'fieldSettingsArray' => $this->fieldSettings->toArray(
					true,
					false
				),
				'uploadKey' => $this->uploadKeys->createNew(),
				'uploadUrl' => $this->uploadKeys->getUploadUrl(),
				'fileChooserLink' => $fileChooserLink,
				'rows' => $rows
			));
	}
}
