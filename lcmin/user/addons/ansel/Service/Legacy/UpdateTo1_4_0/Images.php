<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_4_0;

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
	}
}
