<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

use BuzzingPixel\Ansel\Service\LicenseCheck;

/**
 * Class Ansel_mcp
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 * @SuppressWarnings(PHPMD.Superglobals)
 */
// @codingStandardsIgnoreStart
class Ansel_mcp
// @codingStandardsIgnoreEnd
{
	/**
	 * Index acts as a router to call controllers
	 *
	 * @return string
	 */
	public function index()
	{
		// Get license check service
		/** @var LicenseCheck $licenseCheckService */
		$licenseCheckService = ee('ansel:LicenseCheck');

		// Run license check
		$licenseStatus = $licenseCheckService->run();

		// Place license ping action ID JS
		$licenseCheckService->placeActionIdForJS();

		// Get controller param
		$controller = ee('Request')->get('controller', 'GlobalSettings');

		// Check if trial expired or license invalid
		if ($controller !== 'License' && $licenseStatus === 'expired') {
			$controller = 'TrialExpired';
		} elseif ($controller !== 'License' && $licenseStatus === 'invalid') {
			$controller = 'InvalidLicense';
		}

		// Call the appropriate controller
		return $this->callController($controller);
	}

	/**
	 * Call a controller
	 *
	 * @param string $controller
	 * @return string
	 */
	private function callController($controller)
	{
		// Get the controller class
		$class = 'BuzzingPixel\Ansel\Controller\CP\\' . $controller;

		// Check if the class exists
		if (! class_exists($class)) {
			return 'No controller found';
		}

		// Get the method
		$method = strtolower($_SERVER['REQUEST_METHOD']);

		// Check if method exists
		if (! method_exists($class, $method)) {
			return "Controller does not implement {$method} method";
		}

		// Return the controller and method
		return ee("ansel:CPController", $controller, $class)->$method();
	}
}
