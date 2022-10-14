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
 * Class InvalidLicense
 */
class InvalidLicense extends BaseCP
{
	/**
	 * Get method
	 *
	 * @return array
	 */
	public function get()
	{
		// Set the account link
		$accountLink = 'https://buzzingpixel.com/account';

		// Set the purchase link
		$purchaseLink = 'https://buzzingpixel.com/software/ansel-ee';

		// Get license settings page link
		$licenseLink = $this->cpUrl->make('addons/settings/ansel', array(
			'controller' => 'License'
		));

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

		return array(
			'heading' => lang('ansel_license_invalid'),
			'body' => $this->viewFactory->make('ansel:CP/AlertOnly')
				->render()
		);
	}
}
