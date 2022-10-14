<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Controller\CP;

use EllisLab\ExpressionEngine\Service\Sidebar\Sidebar;
use EllisLab\ExpressionEngine\Service\URL\URLFactory as CPURL;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use BuzzingPixel\Ansel\Service\GlobalSettings;
use EllisLab\ExpressionEngine\Core\Request;
use EllisLab\ExpressionEngine\Service\Alert\AlertCollection as CpAlertService;
use BuzzingPixel\Ansel\Service\UpdatesFeed;
use BuzzingPixel\Ansel\Service\LicensePing;

/**
 * Class BaseCP
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
abstract class BaseCP
{
	/**
	 * @var string $controller
	 */
	protected $controller;

	/**
	 * @var Sidebar $sidebar
	 */
	protected $sidebar;

	/**
	 * @var CPURL $cpUrl
	 */
	protected $cpUrl;

	/**
	 * @var ViewFactory $viewFactory
	 */
	protected $viewFactory;

	/**
	 * @var GlobalSettings $globalSettings
	 */
	protected $globalSettings;

	/**
	 * @var Request $request
	 */
	protected $request;

	/**
	 * @var CpAlertService $cpAlertService
	 */
	protected $cpAlertService;

	/**
	 * @var \EE_Functions $eeFunctions
	 */
	protected $eeFunctions;

	/**
	 * @var \Cp $cp
	 */
	protected $cp;

	/**
	 * @var UpdatesFeed $updatesFeed
	 */
	protected $updatesFeed;

	/**
	 * @var LicensePing $licensePing
	 */
	protected $licensePing;

	/**
	 * @var \EE_Typography $eeTypography
	 */
	protected $eeTypography;

	/**
	 * @var string $urlThirdThemes
	 */
	protected $urlThirdThemes;

	/**
	 * @var string $pathThirdThemes
	 */
	protected $pathThirdThemes;

	/**
	 * Constructor
	 *
	 * @param string $controller
	 * @param Sidebar $sidebar
	 * @param CPURL $cpUrl
	 * @param ViewFactory $viewFactory
	 * @param GlobalSettings $globalSettings
	 * @param Request $request
	 * @param CpAlertService $cpAlertService
	 * @param \EE_Functions $eeFunctions
	 * @param \Cp $cp
	 * @param UpdatesFeed $updatesFeed
	 * @param LicensePing $licensePing
	 * @param \EE_Typography $eeTypography
	 * @param string $urlThirdThemes
	 * @param string $pathThirdThemes
	 */
	public function __construct(
		$controller,
		Sidebar $sidebar,
		CPURL $cpUrl,
		ViewFactory $viewFactory,
		GlobalSettings $globalSettings,
		Request $request,
		CpAlertService $cpAlertService,
		\EE_Functions $eeFunctions,
		\Cp $cp,
		UpdatesFeed $updatesFeed,
		LicensePing $licensePing,
		\EE_Typography $eeTypography,
		$urlThirdThemes,
		$pathThirdThemes
	) {
		// Inject dependencies
		$this->controller = $controller;
		$this->sidebar = $sidebar;
		$this->cpUrl = $cpUrl;
		$this->viewFactory = $viewFactory;
		$this->globalSettings = $globalSettings;
		$this->request = $request;
		$this->cpAlertService = $cpAlertService;
		$this->eeFunctions = $eeFunctions;
		$this->cp = $cp;
		$this->updatesFeed = $updatesFeed;
		$this->licensePing = $licensePing;
		$this->eeTypography = $eeTypography;
		$this->urlThirdThemes = (string) $urlThirdThemes;
		$this->pathThirdThemes = (string) $pathThirdThemes;

		// Create the sidebar
		$this->createSidebar();


		/**
		 * Add CSS and JS
		 */

		$cssPath = "{$this->pathThirdThemes}ansel/css/style.min.css";
		if (is_file($cssPath)) {
			$cssFileTime = filemtime($cssPath);
		} else {
			$cssFileTime = uniqid();
		}
		$css = "{$this->urlThirdThemes}ansel/css/style.min.css";
		$cssTag = "<link rel=\"stylesheet\" href=\"{$css}?v={$cssFileTime}\">";
		$this->cp->add_to_head($cssTag);

		$jsPath = "{$this->pathThirdThemes}ansel/js/script.min.js";
		if (is_file($jsPath)) {
			$jsFileTime = filemtime($jsPath);
		} else {
			$jsFileTime = uniqid();
		}
		$js = "{$this->urlThirdThemes}ansel/js/script.min.js";
		$jsTag = "<script type=\"text/javascript\" src=\"{$js}?v={$jsFileTime}\"></script>";
		$this->cp->add_to_foot($jsTag);
	}

	/**
	 * Create sidebar
	 */
	private function createSidebar()
	{
		// Set updates tab text
		$updatesTabText = lang('updates');

		// Get number of updates
		$updates = $this->updatesFeed->getNumber();

		if ($updates) {
			$updatesTabText .= " <strong style='font-style: normal'>({$updates})</strong>";
		}

		// Add the heading
		/** @var \EllisLab\ExpressionEngine\Service\Sidebar\Header $header */
		$header = $this->sidebar->addHeader(lang('Settings'));

		// Create a list under the header
		/** @var \EllisLab\ExpressionEngine\Service\Sidebar\BasicList $list */
		$list = $header->addBasicList();

		/**
		 * Add links to the list
		 */

		// Global Settings
		/** @var \EllisLab\ExpressionEngine\Service\Sidebar\BasicItem $link */
		$link = $list->addItem(
			lang('global_settings'),
			$this->cpUrl->make('addons/settings/ansel')
		);

		if ($this->controller === 'GlobalSettings') {
			$link->isActive();
		} else {
			$link->isInactive();
		}

		// Updates
		$link = $list->addItem(
			$updatesTabText,
			$this->cpUrl->make('addons/settings/ansel', array(
				'controller' => 'Updates'
			))
		);

		if ($this->controller === 'Updates') {
			$link->isActive();
		} else {
			$link->isInactive();
		}

		// License
		$link = $list->addItem(
			lang('license'),
			$this->cpUrl->make('addons/settings/ansel', array(
				'controller' => 'License'
			))
		);

		if ($this->controller === 'License') {
			$link->isActive();
		} else {
			$link->isInactive();
		}
	}

	/**
	 * All CP controllers should implement the get method and return an array
	 *
	 * @return array
	 */
	abstract public function get();
}
