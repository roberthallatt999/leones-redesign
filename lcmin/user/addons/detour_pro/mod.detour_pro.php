<?php

// This is what we have to do for EE2 support
require_once 'addon.setup.php';

use EEHarbor\DetourPro\FluxCapacitor\Base\Mod;

/**
 * Detour Pro Module Front End File
 *
 * @package     ExpressionEngine
 * @subpackage  Addons
 * @category    Module
 * @author      Mike Hughes - City Zen
 * @author      Tom Jaeger - EEHarbor
 * @link        http://eeharbor.com/detour_pro
 */

class Detour_pro extends Mod
{
    public $return_data;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Tag to add current URL as 404
     */
    public function not_found()
    {
        //if the URI is exct match of 404 template, we do nothing
        if (ee()->uri->uri_string=='' || ee()->uri->uri_string==ee()->config->item('site_404')) {
            return;
        }

        $not_found = array(
            'original_url'  => trim(ee()->uri->uri_string),
            'site_id'       => ee()->config->item('site_id'),
            'detour_id' => null
        );

        //is it already there?
        $check_q = ee()->db->select('notfound_id, hits')
            ->from('detours_not_found')
            ->where($not_found)
            ->get();
        if ($check_q->num_rows()==0) {
            $not_found['hit_date'] = date("Y-m-d");
            ee()->db->insert('detours_not_found', $not_found);
        } else {
            ee()->db->where('notfound_id', $check_q->row('notfound_id'))
                ->update('detours_not_found', ['hits' => ((int)$check_q->row('hits') + 1)]);
        }
    }
}
/* End of file mod.detour_pro.php */
/* Location: /system/expressionengine/third_party/detour_pro/mod.detour_pro.php */
