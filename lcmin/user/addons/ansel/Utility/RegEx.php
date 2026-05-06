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

namespace BoldMinded\Ansel\Utility;

/**
 * Class RegEx
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class RegEx
{
    /**
     * Host regex
     *
     * @return string
     */
    public static function host()
    {
        return '/^((?:https?:)?\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})/';
    }

    /**
     * Param regex
     *
     * @return string
     */
    public static function param()
    {
        return '/\s?([^=]*)=["\']([^=]*)["\']/s';
    }

    /**
     * No Results regex
     *
     * @param string $namespace
     * @return string
     */
    public static function noResults($namespace = 'img:')
    {
        $ld = LD;
        $rd = RD;
        return "#{$ld}if {$namespace}no_results{$rd}(.*?){$ld}/if{$rd}#s";
    }

    /**
     * Between tags regex
     *
     * @return string
     */
    public static function tagBetween()
    {
        return '((.+?)=([\"\'])(.+?)([\"\'])( *|\r\n*|\n*|\r*|\t*)*?)?';
    }

    /**
     * Tag regex
     *
     * @param string $tag
     * @param string $namespace
     * @return string
     */
    public static function tag($tag = 'tag', $namespace = 'img:')
    {
        return '/{' . $namespace . $tag . self::tagBetween() . '}/';
    }
}
