<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

/**
 * Class Ansel
 *
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
// @codingStandardsIgnoreStart
class Ansel
// @codingStandardsIgnoreEnd
{
	/**
	 * License check (action)
	 */
	public function licensePing()
	{
		ee('ansel:LicensePing')->run();
		exit();
	}

	/**
	 * Image uploader (action)
	 */
	public function imageUploader()
	{
		ee('ansel:FieldUploaderController')->post();
		exit();
	}

	/**
	 * Ansel images tag pair
	 *
	 * @return string
	 */
	public function images()
	{
		// Get license check service
		$licenseCheckService = ee('ansel:LicenseCheck');

		// Run license check
		$licenseStatus = $licenseCheckService->run();

		// Check if trial expired or license invalid
		if ($licenseStatus === 'expired') {
			return lang('ansel_trial_expired');
		} elseif ($licenseStatus === 'invalid') {
			return lang('ansel_license_invalid');
		}

		// Make sure tagParams is an array
		$tagParams = ee()->TMPL->tagparams;
		$tagParams = gettype($tagParams) === 'array' ? $tagParams : array();

		// Check if there is tag data
		$tagData = ee()->TMPL->tagdata ?: false;

		// Run the controller
		return ee('ansel:ImagesTagController')->parse($tagParams, $tagData);
	}
}
