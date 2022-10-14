<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use BuzzingPixel\Ansel\Service\GlobalSettings;
use EllisLab\ExpressionEngine\Core\Provider;

/**
 * Class LicensePing
 *
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class LicensePing
{
	/**
	 * @var GlobalSettings $globalSettings
	 */
	protected $globalSettings;
	/**
	 * @var Provider $addon
	 */
	protected $addon;

	/**
	 * Constructor
	 *
	 * @param GlobalSettings $globalSettings
	 * @param Provider $addon
	 */
	public function __construct(
		GlobalSettings $globalSettings,
		Provider $addon
	) {
		// Inject dependencies
		$this->globalSettings = $globalSettings;
		$this->addon = $addon;
	}

	/**
	 * Run ping
	 */
	public function run()
	{
		// Check if this is a pre-release
		if ($this->checkPreRelease()) {
			return;
		}

		// Check if we have a license key
		if (! $this->globalSettings->license_key) {
			return;
		}

		// Check if we should phone home
		if (time() < $this->globalSettings->phone_home &&
			$this->globalSettings->encoding_data !== 'invalid'
		) {
			return;
		}

		// Set the server name
		$server = $this->setServerName();
		if (! $server) {
			return;
		}

		// Run ping
		$result = $this->runPing($server);

		// Check the result
		if (! isset($result->success) || ! $result->success) {
			return;
		}

		// Check for invalid response
		if ($result->message === 'invalid') {
			$this->globalSettings->encoding_data = 'invalid';
			$this->globalSettings->save();
			return;
		}

		// Increment the phone home setting
		$this->globalSettings->encoding_data = 'valid';
		$this->globalSettings->phone_home = strtotime('+1 day', time());
		$this->globalSettings->save();

		return;
	}

	/**
	 * Check pre-release
	 *
	 * @return bool True if pre-release, false if not
	 */
	private function checkPreRelease()
	{
		// Explode on the pre-release separator
		$parts = explode('-', $this->addon->getVersion());

		// If there is only one part, this is an official release
		if (count($parts) < 2) {
			return false;
		}

		// Now we know this is a pre-release

		// Make sure we set the license to valid
		if ($this->globalSettings->encoding_data === 'invalid') {
			$this->globalSettings->encoding_data = 'valid';
			$this->globalSettings->save();
		}

		// Return true that we are in a pre-release state
		return true;
	}

	/**
	 * Set server name
	 *
	 * @return string
	 */
	private function setServerName()
	{
		if (isset($_SERVER['SERVER_NAME'])) {
			return $_SERVER['SERVER_NAME'];
		} elseif (isset($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		}

		return '';
	}

	/**
	 * Run ping
	 *
	 * @param string $server
	 * @return \stdClass
	 */
	private function runPing($server)
	{
		// Set the URL to post to
		$url = 'https://buzzingpixel.com/api/v1/check-license';

		// Set the data to send
		$data = array(
			'app' => 'ansel-ee',
			'domain' => $server,
			'license' => $this->globalSettings->license_key ?: ''
		);

		// Set options
		$options = array(
			'http' => array(
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data)
			),
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
			)
		);

		// Set context
		$context = stream_context_create($options);

		// Send the request and return the result
		return json_decode(file_get_contents($url, false, $context));
	}
}
