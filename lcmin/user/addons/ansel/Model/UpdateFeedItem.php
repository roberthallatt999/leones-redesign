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
 * @property string $version
 * @property string $downloadUrl
 * @property \DateTime $date
 * @property array $notes
 * @property bool $new
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
class UpdateFeedItem extends Model
{
    /**
     * @var \EE_Typography $eeTypography
     */
    private $eeTypography;

    /**
     * Constructor
     *
     * @param \EE_Typography $eeTypography
     */
    public function __construct(\EE_Typography $eeTypography)
    {
        // Run the parent constructor method
        parent::__construct();

        // Inject dependencies
        $this->eeTypography = $eeTypography;
    }

    /**
     * Clone object
     */
    public function __clone()
    {
        if ($this->date) {
            $this->date = clone $this->date;
        }
    }

    /**
     * Model properties
     */
    protected $version;
    protected $downloadUrl;
    protected $date;
    protected $notes;
    protected $new;

    /**
     * @var array $_typed_columns
     */
    // @codingStandardsIgnoreStart
    protected static $_typed_columns = array( // @codingStandardsIgnoreEnd
        'version' => 'string',
        'downloadUrl' => 'string',
        'new' => 'bool'
    );

    /**
     * Date setter
     *
     * @param string|\DateTime $val
     */
    // @codingStandardsIgnoreStart
    protected function set__date($val) // @codingStandardsIgnoreEnd
    {
        if (! $val instanceof \DateTime) {
            $val = new \DateTime($val);
        }

        $this->setRawProperty('date', $val);
    }

    /**
     * Notes setter
     *
     * @param array $val
     */
    // @codingStandardsIgnoreStart
    protected function set__notes($val) // @codingStandardsIgnoreEnd
    {
        // Make sure value is an array
        if (gettype($val) === 'array') {
            $this->setRawProperty('notes', $val);
        }
    }

    /**
     * Notes getter
     */
    // @codingStandardsIgnoreStart
    protected function get__notes() // @codingStandardsIgnoreEnd
    {
        // Make sure an array is always returned
        return gettype($this->notes) === 'array' ? $this->notes : array();
    }

    /**
     * Get notes markdown
     */
    public function getNotesMarkdown()
    {
        $itemsToRemove = array();

        foreach ($this->notes as $note) {
            if (strpos($note, '#') === 0) {
                $itemsToRemove[] = '[' . substr($note, 2) . '] ';
            }
        }

        $mdString = html_entity_decode(
            implode("\n\n", $this->notes),
            ENT_QUOTES
        );

        foreach ($itemsToRemove as $item) {
            $mdString = str_replace($item, '', $mdString);
        }

        return $this->eeTypography->markdown($mdString);
    }
}
