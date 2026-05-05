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
        // Call the appropriate controller
        return $this->callController(ee('Request')->get('controller', 'GlobalSettings'));
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
        $class = 'BoldMinded\Ansel\Controller\CP\\' . $controller;

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
