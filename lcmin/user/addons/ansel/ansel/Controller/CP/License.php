<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\CP;

use EllisLab\ExpressionEngine\Service\Alert\Alert;

/**
 * Class License
 */
class License extends BaseCP
{
	/**
	 * Get method for displaying license settings page
	 *
	 * @return array
	 */
	public function get()
	{
		// Check if the license was valid or not
		if ($this->globalSettings->encoding_data === 'invalid') {
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

			// Show alert
			/** @var Alert $alert */
			$alert = $this->cpAlertService->makeInline('ansel-license-invalid');
			$alert->asIssue();
			$alert->cannotClose();
			$alert->withTitle(lang('ansel_license_invalid'));
			$alert->addToBody($text);
			$alert->now();
		}

		// Get the license file contents
		$licenseText = file_get_contents(ANSEL_LICENSE_PATH);
		$licenseText = $this->eeTypography->markdown($licenseText);

		return array(
			'heading' => lang('ansel_license'),
			'body' => $this->viewFactory->make('ansel:CP/License')
				->render(array(
					'globalSettings' => $this->globalSettings,
					'cpUrl' => $this->cpUrl,
					'licenseText' => $licenseText
				))
		);
	}

	/**
	 * Post method for saving license key
	 */
	public function post()
	{
		// Set the license key on the globalSettings model
		$this->globalSettings->license_key = $this->request->post('license_key');

		// Reset phone home so that it runs on next request
		$this->globalSettings->phone_home = 0;

		// Save the model
		$this->globalSettings->save();

		// Run license ping
		$this->licensePing->run();

		// Check if the license was valid or not
		if ($this->globalSettings->encoding_data !== 'invalid') {
			// Show the success message
			/** @var Alert $alert */
			$alert = $this->cpAlertService->makeInline('ansel-license-updated');
			$alert->asSuccess();
			$alert->canClose();
			$alert->withTitle(lang('license_updated'));
			$alert->addToBody(lang('license_updated_success'));
			$alert->defer();
		}

		// Redirect away and back to this page/controller
		$this->eeFunctions->redirect($this->cpUrl->getCurrentUrl());
	}
}
