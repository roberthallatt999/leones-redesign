<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Install\UpdateTo2_0_0;

use CI_DB_mysqli_forge as DBForge;

/**
 * Class Images
 */
class Images
{
	/**
	 * @var DBForge $dbForge
	 */
	private $dbForge;

	/**
	 * Constructor
	 *
	 * @param DBForge $dbForge
	 */
	public function __construct(DBForge $dbForge)
	{
		$this->dbForge = $dbForge;
	}

	/**
	 * Process Images changes
	 */
	public function process()
	{
		// Modify the row_id column to have a default of 0
		$this->dbForge->modify_column('ansel_images', array(
			'row_id' => array(
				'name' => 'row_id',
				'null' => false,
				'default' => 0,
				'type' => 'INT',
				'unsigned' => true
			),
		));

		// Modify the col_id column to have a default of 0
		$this->dbForge->modify_column('ansel_images', array(
			'col_id' => array(
				'name' => 'col_id',
				'null' => false,
				'default' => 0,
				'type' => 'INT',
				'unsigned' => true
			),
		));
	}
}
