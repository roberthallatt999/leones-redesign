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

namespace BoldMinded\Ansel\Service;

/**
 * Class NamespaceVars
 */
class NamespaceVars
{
    /**
     * Run namespace on vars
     * @param array $vars
     * @param string $namespace
     * @return array
     */
    public function run($vars, $namespace = 'img:')
    {
        $namespace = rtrim($namespace, ':') . ':';

        $returnVars = array();

        foreach ($vars as $var => $val) {
            $returnVars["{$namespace}{$var}"] = $val;
        }

        return $returnVars;
    }

    /**
     * Namespace a set of vars
     * @param array $varSet
     * @param string $namespace
     * @return array
     */
    public function namespaceSet($varSet, $namespace = 'img:')
    {
        $namespace = rtrim($namespace, ':') . ':';

        $returnSet = array();

        foreach ($varSet as $key => $vars) {
            $returnSet[$key] = $this->run($vars, $namespace);
        }

        return $returnSet;
    }
}
