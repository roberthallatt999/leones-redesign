<?php
namespace detour_pro;
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(@file_exists(SYSPATH.'ee/legacy/fieldtypes/EE_Fieldtype.php')) {
    require_once SYSPATH.'ee/legacy/fieldtypes/EE_Fieldtype.php';
} elseif(@file_exists(APPPATH.'fieldtypes/EE_Fieldtype.php')) {
    require_once APPPATH.'fieldtypes/EE_Fieldtype.php';
} elseif(defined('APPPATH') && strpos(APPPATH, 'installer') !== false) {
    $realAppPath = str_replace('installer', 'expressionengine', APPPATH);

    if(@file_exists($realAppPath.'fieldtypes/EE_Fieldtype.php')) {
        require_once $realAppPath.'fieldtypes/EE_Fieldtype.php';
    }
}

/**
 * EEHarbor update parent class
 *
 * @package         EEHarbor Fieldtype Parent
 * @version         1.4.3
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2016, Tom Jaeger/EEHarbor
 */

abstract class Eeharbor_ft extends \EE_Fieldtype
{

    public $rows = array();

    public function __construct()
    {
    }

    public function _display_settings_add_row($title, $data, $desc='', $wide=false)
    {
        // different things for different versions of ee
        if($this->eeharbor->is_ee2())
        {
            if(!$wide) {
                ee()->table->add_row($title, $data);
            } else {
                ee()->table->add_row(array(
                    'colspan' => '2',
                    'data'    => $title . $data,
                ));
            }
        }
        else
        {
            $this->rows[] = array(
                'title' => $title,
                'desc' => $desc,
                'wide' => $wide,
                'fields' => array(
                    array(
                        'type' => 'html',
                        'content' => $data,
                    )
                )
            );
        }
    }

    public function _package_display_settings($field_options, $group, $label='field_options')
    {
        if($this->eeharbor->is_ee2())
        {
            return true;
        } else {
            return array(
                $field_options => array(
                    'label' => $label,
                    'group' => $group,
                    'settings' => $this->rows
                )
            );
        }
    }
}