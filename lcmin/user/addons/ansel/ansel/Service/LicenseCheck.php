<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

use BuzzingPixel\Ansel\Service\GlobalSettings;
use EllisLab\ExpressionEngine\Service\URL\URLFactory as CPURL;
use EllisLab\ExpressionEngine\Service\Alert\AlertCollection as CpAlertService;
use EllisLab\ExpressionEngine\Service\Alert\Alert;
use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;

/**
 * Class LicenseCheck
 */
class LicenseCheck
{
	/**
	 * @var GlobalSettings $globalSettings
	 */
	protected $globalSettings;

	/**
	 * @var CPURL $cpUrl
	 */
	protected $cpUrl;

	/**
	 * @var CpAlertService $cpAlertService
	 */
	protected $cpAlertService;

	/**
	 * @var \EE_Javascript $javascript
	 */
	protected $javascript;

	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * @var string $siteUrl
	 */
	private $siteUrl;

	/**
	 * @var string $siteIndex
	 */
	private $siteIndex;

	/**
	 * @var string $result
	 */
	private $result;

	/**
	 * Constructor
	 *
	 * @param GlobalSettings $globalSettings
	 * @param CPURL $cpUrl
	 * @param CpAlertService $cpAlertService
	 * @param \EE_Javascript $javascript
	 * @param RecordBuilder $recordBuilder
	 * @param string $siteUrl
	 * @param string $siteIndex
	 */
	public function __construct(
		GlobalSettings $globalSettings,
		$cpUrl,
		$cpAlertService,
		$javascript,
		RecordBuilder $recordBuilder,
		$siteUrl,
		$siteIndex
	) {
		// Inject dependencies
		$this->globalSettings = $globalSettings;
		$this->cpUrl = $cpUrl;
		$this->cpAlertService = $cpAlertService;
		$this->javascript = $javascript;
		$this->recordBuilder = $recordBuilder;
		$this->siteUrl = $siteUrl;
		$this->siteIndex = $siteIndex;
	}

	/**
	 * Run
	 *
	 * @return string trial|expired|valid|invalid
	 */
	public function run()
	{
		// Check if the result has already been set
		if ($this->result) {
			return $this->result;
		}

		// Check for license key
		if ($this->globalSettings->license_key) {
			return $this->checkLicenseStatus();
		}

		// Now we know there is no license key, check for trial
		$this->result = $this->checkTrial();

		// Return the result
		return $this->result;
	}

	/**
	 * Place action ID for JS
	 */
	public function placeActionIdForJS()
	{
		if (REQ !== 'CP') {
			return;
		}

		// Get the action record
		$actionRecord = $this->recordBuilder->get('Action');
		$actionRecord->filter('class', 'Ansel');
		$actionRecord->filter('method', 'licensePing');
		$actionRecord = $actionRecord->first();

		// Set the URL
		$url = rtrim($this->siteUrl, '/') . '/' . $this->siteIndex;

		// Output the javascript
		$this->javascript->output(
			"window.ANSEL = window.ANSEL || {};" .
			"window.ANSEL.licensePingActionId = {$actionRecord->action_id};" .
			"window.ANSEL.licensePingUrl = '{$url}';"
		);
	}

	/**
	 * Check trial
	 *
	 * @return string trial|expired
	 */
	private function checkTrial()
	{
		// Check for valid trial
		if (time() < $this->globalSettings->encoding) {
			return 'trial';
		}

		// Now we know the trial is invalid

		if (REQ === 'CP') {
			// Set the purchase link
			$purchaseLink = 'https://buzzingpixel.com/software/ansel-ee';

			// Get license settings page link
			$licenseLink = $this->cpUrl->make('addons/settings/ansel', array(
				'controller' => 'License'
			));

			// Get lang and replace placeholders
			$text = strtr(lang('ansel_trial_expired_body'), array(
				'{{purchaseLinkStart}}' => "<a href=\"{$purchaseLink}\">",
				'{{licenseLinkStart}}' => "<a href=\"{$licenseLink}\">",
				'{{linkEnd}}' => "</a>"
			));

			// Show the banner
			/** @var Alert $alert */
			$alert = $this->cpAlertService->makeBanner('ansel-no-license');
			$alert->asIssue();
			$alert->cannotClose();
			$alert->withTitle(lang('ansel_trial_expired'));
			$alert->addToBody($text);
			$alert->now();
		}

		// Return the license status
		return 'expired';
	}

	/**
	 * Check license status
	 *
	 * @return string valid|invalid
	 */
	private function checkLicenseStatus()
	{
		if ($this->globalSettings->encoding_data === 'invalid') {
			if (REQ === 'CP') {
				// Set the account link
				$accountLink = 'https://buzzingpixel.com/account';

				// Set the purchase link
				$purchaseLink = 'https://buzzingpixel.com/software/ansel-ee';

				// Get license settings page link
				$licenseLink = $this->cpUrl->make('addons/settings/ansel', array(
					'controller' => 'License'
				));

				// Get lang and replace placeholders
				$text = strtr(lang('ansel_license_invalid_body'), array(
					'{{accountLinkStart}}' => "<a href=\"{$accountLink}\">",
					'{{purchaseLinkStart}}' => "<a href=\"{$purchaseLink}\">",
					'{{licenseLinkStart}}' => "<a href=\"{$licenseLink}\">",
					'{{linkEnd}}' => "</a>"
				));

				// Show the banner
				/** @var Alert $alert */
				$alert = $this->cpAlertService->makeBanner('ansel-license-invalid');
				$alert->asIssue();
				$alert->cannotClose();
				$alert->withTitle(lang('ansel_license_invalid'));
				$alert->addToBody($text);
				$alert->now();
			}

			return 'invalid';
		}

		return 'valid';
	}
}
