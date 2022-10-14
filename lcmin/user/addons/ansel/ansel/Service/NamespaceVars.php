<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service;

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
