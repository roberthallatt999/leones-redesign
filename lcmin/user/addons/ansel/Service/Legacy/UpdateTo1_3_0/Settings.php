<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Legacy\UpdateTo1_3_0;

/**
 * Class Settings
 */
class Settings
{
	/**
	 * Process the update
	 */
	public function process()
	{
		// Delete image cache settings (no longer needed)
		ee()->db->delete('ansel_settings', array(
			'settings_key' => 'image_cache_location'
		));
		ee()->db->delete('ansel_settings', array(
			'settings_key' => 'image_cache_url'
		));
	}
}
