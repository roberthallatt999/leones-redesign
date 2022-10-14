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
 * Class GlobalSettings
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class GlobalSettings extends BaseCP
{
	/**
	 * @var array $excludedItems
	 */
	private $excludedItems = array(
		'license_key',
		'phone_home',
		'check_for_updates',
		'updates_available',
		'update_feed',
		'encoding',
		'encoding_data'
	);

	/**
	 * Get method for displaying global settings page
	 *
	 * @return array
	 */
	public function get()
	{
		// We need to find the last key
		$lastKey = '';
		foreach ($this->globalSettings as $key => $settings) {
			if (in_array($key, $this->excludedItems)) {
				continue;
			}

			$lastKey = $key;
		}

		return array(
			'heading' => lang('global_settings'),
			'body' => $this->viewFactory->make('ansel:CP/GlobalSettings')
				->render(array(
					'globalSettings' => $this->globalSettings,
					'cpUrl' => $this->cpUrl,
					'excludedItems' => $this->excludedItems,
					'lastKey' => $lastKey
				))
		);
	}

	/**
	 * Post method for saving global settings
	 */
	public function post()
	{
		// Iterate through settings and assign values from post
		foreach ($this->globalSettings as $key => $val) {
			// Check if this is an excluded item
			if (in_array($key, $this->excludedItems)) {
				continue;
			}

			// Set the value of the property
			$this->globalSettings->{$key} = $this->request->post($key);
		}

		// Save the model
		$this->globalSettings->save();

		// Show the success message
		/** @var Alert $alert */
		$alert = $this->cpAlertService->makeInline('ansel-settings-updated');
		$alert->asSuccess();
		$alert->canClose();
		$alert->withTitle(lang('settings_updated'));
		$alert->addToBody(lang('settings_updated_success'));
		$alert->defer();

		// Redirect away and back to this page/controller
		$this->eeFunctions->redirect($this->cpUrl->getCurrentUrl());
	}
}
