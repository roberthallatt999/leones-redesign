<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Add-ons
 * @category    Ansel
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/ansel
 * @license
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace BoldMinded\Ansel\Controller\CP;

use ExpressionEngine\Service\Sidebar\Sidebar;
use ExpressionEngine\Service\URL\URLFactory as CPURL;
use ExpressionEngine\Service\View\ViewFactory;
use BoldMinded\Ansel\Service\GlobalSettings;
use ExpressionEngine\Core\Request;
use ExpressionEngine\Service\Alert\AlertCollection as CpAlertService;
use BoldMinded\Ansel\Service\UpdatesFeed;

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
        // Add the heading
        /** @var \ExpressionEngine\Service\Sidebar\Header $header */
        $header = $this->sidebar->addHeader(lang('Settings'));

        // Create a list under the header
        /** @var \ExpressionEngine\Service\Sidebar\BasicList $list */
        $list = $header->addBasicList();

        /**
         * Add links to the list
         */

        // Global Settings
        /** @var \ExpressionEngine\Service\Sidebar\BasicItem $link */
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
            'Releases',
            $this->cpUrl->make('addons/settings/ansel', array(
                'controller' => 'Releases'
            ))
        );

        if ($this->controller === 'Releases') {
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
