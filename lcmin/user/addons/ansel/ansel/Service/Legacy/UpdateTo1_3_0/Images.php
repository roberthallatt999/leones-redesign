<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_3_0;

/**
 * Class Images
 */
class Images
{
	/**
	 * Process the update
	 */
	public function process()
	{
		// Load the forge class
		ee()->load->dbforge();

		// Modify the upload_location_id column to accept strings
		// (Treasury prefers to refer to locations by handle)
		ee()->dbforge->modify_column('ansel_images', array(
			'upload_location_id' => array(
				'name' => 'upload_location_id',
				'default' => '',
				'type' => 'VARCHAR',
				'constraint' => 255
			),
		));

		// Add the upload_location_type column
		if (! ee()->db->field_exists('upload_location_type', 'ansel_images')) {
			ee()->dbforge->add_column('ansel_images', array(
				'upload_location_type' => array(
					'default' => 'ee',
					'type' => 'VARCHAR',
					'constraint' => 10
				),
			));
		}

		// Add the upload_location_type column
		if (! ee()->db->field_exists('original_location_type', 'ansel_images')) {
			ee()->dbforge->add_column('ansel_images', array(
				'original_location_type' => array(
					'default' => 'ee',
					'type' => 'VARCHAR',
					'constraint' => 10
				),
			));
		}
	}
}
