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

namespace BoldMinded\Ansel\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Class Source
 *
 * @property string $url
 * @property string $path
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Source extends Model
{
    /**
     * Model properties
     */
    protected $url;
    protected $path;

    /**
     * @var array $_typed_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
        'url' => 'string',
        'path' => 'string'
    );

    /**
     * Get urlsafe param
     *
     * @param string $name
     * @return mixed
     */
    public function getUrlSafeParam($name)
    {
        return rawurlencode($this->getProperty($name));
    }
}
