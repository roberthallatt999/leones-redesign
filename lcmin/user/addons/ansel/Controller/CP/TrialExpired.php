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
 * Class TrialExpired
 */
class TrialExpired extends BaseCP
{
	/**
	 * Get method
	 *
	 * @return array
	 */
	public function get()
	{
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

		// Show alert
		/** @var Alert $alert */
		$alert = $this->cpAlertService->makeInline('ansel-trial-expired');
		$alert->asIssue();
		$alert->cannotClose();
		$alert->withTitle(lang('ansel_trial_expired'));
		$alert->addToBody($text);
		$alert->now();

		return array(
			'heading' => lang('ansel_trial_expired'),
			'body' => $this->viewFactory->make('ansel:CP/AlertOnly')
				->render()
		);
	}
}
