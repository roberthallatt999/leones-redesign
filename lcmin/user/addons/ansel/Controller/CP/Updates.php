<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\CP;

/**
 * Class Updates
 */
class Updates extends BaseCP
{
	/**
	 * Get method for displaying updates
	 *
	 * @return array
	 */
	public function get()
	{
		// Limit collection to last four items
		$obj = new \stdClass();
		$obj->count = 0;
		$lastFour = $this->updatesFeed->get(true)
			->filter(function () use ($obj) {
				$obj->count++;
				return $obj->count < 5;
			});

		return array(
			'heading' => lang('ansel_updates'),
			'body' => $this->viewFactory->make('ansel:CP/Updates')
				->render(array(
					'updatesFeed' => $lastFour
				))
		);
	}
}
