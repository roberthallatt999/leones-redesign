<?php

/**
 * EEHarbor Module File
 *
 * PHP Version 5.3
 *
 * @category   Module
 * @package    ExpressionEngine
 * @subpackage Addons
 * @author     EEHarbor <help@eeharbor.com>
 * @license    https://eeharbor.com/license EEHarbor Add-on License
 * @link       https://eeharbor.com/
 */

require_once 'addon.setup.php';

use EEHarbor\DetourPro\FluxCapacitor\FluxCapacitor;
use EEHarbor\DetourPro\FluxCapacitor\Base\Ext;

class Detour_pro_ext extends Ext
{
    public $settings       = array();
    public $name           = 'Detour Pro';
    public $description    = 'Reroute urls to another URL.';
    public $settings_exist = 'n';
    public $docs_url       = 'https://eeharbor.com/detour-pro/documentation';

    public function __construct()
    {
        parent::__construct();

        $this->settings = $this->flux->getSettings(true);
    }

    public function activateExtension()
    {
        ee()->db->where('class', 'Detour_ext');
        ee()->db->delete('extensions');

        ee()->functions->clear_caching('db');

        $this->register_extension('sessions_start', null, 1, 'n');
        $this->register_extension('cp_custom_menu');
    }

    public function disableExtension()
    {
        parent::disableExtension();

        ee()->functions->clear_caching('db');
    }

    public function updateExtension($current = false)
    {
        $updated = false;

        if (!$current || $current == $this->version) {
            return false;
        }

        // add cp_custom_menu hook
        if (version_compare($current, '2.0.11', "<")) {
            $this->register_extension("cp_custom_menu");
            $updated = true;
        }

        if ($updated) {
            $this->update_version();
        }
    }

    /**************************************************\
     ******************* ALL HOOKS: *******************
    \**************************************************/

    public function sessions_start()
    {
        if (REQ == 'CP') {
            return false;
        }

        if (isset($this->settings['url_detect']) && $this->settings['url_detect'] == 'php') {
            $site_index_file = (ee()->config->item('site_index')) ? ee()->config->item('site_index') . '/' : null;
            $url             = str_replace($site_index_file, '', $_SERVER['REQUEST_URI']);
            $baseUrlPath = ee()->config->item('base_url');

            $position = strpos($url,"?");

            if (is_int($position)) {
                $url = substr($url, 0 , $position);
            }

            $url   = ltrim($url, '/');
            if (empty($this->settings['allow_trailing_slash']) || $this->settings['allow_trailing_slash'] != 1) {
                $url = rtrim($url, '/');
            }

        } else {
            $url = trim(ee()->uri->uri_string);
        }

        $url = urldecode($url);

        $query_literal = false;
        $sql = "SELECT detour_id, original_url, new_url, detour_method, start_date, end_date
                FROM exp_detours
                WHERE (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                AND '" . ee()->db->escape_str($url) . "' LIKE REPLACE(original_url, '_', '[_') ESCAPE '['
                AND site_id = " . ee()->config->item('site_id');
                $detour = ee()->db->query($sql)->row_array();

        if (empty($detour) && !empty($this->settings['allow_trailing_slash']) && $this->settings['allow_trailing_slash'] == 1) {
            $search = $url . "/";
            $sql = "SELECT detour_id, original_url, new_url, detour_method, start_date, end_date
            FROM exp_detours
            WHERE (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            AND '" . ee()->db->escape_str($search) . "' LIKE REPLACE(original_url, '_', '[_') ESCAPE '['
            AND site_id = " . ee()->config->item('site_id');
            $detour = ee()->db->query($sql)->row_array();
        }

        if (empty($detour) && !empty($this->settings['allow_qs']) && $this->settings['allow_qs'] == 1) {
            $search = $url . "?" . http_build_query($_GET);
            $sql = "SELECT detour_id, original_url, new_url, detour_method, start_date, end_date
                FROM exp_detours
                WHERE (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                AND '" . ee()->db->escape_str($search) . "' LIKE REPLACE(original_url, '_', '[_') ESCAPE '['
                AND site_id = " . ee()->config->item('site_id');
                $detour = ee()->db->query($sql)->row_array();
                /*This is a detour set up like the following:  
                old: resources/search/?color=red
                new: resources/search/?color=blue
                Where it doesn't make sense to pass the old filter paramater
                since they want the param changed as well, flag the detour as a literal.*/
                if (!empty($detour)) {
                    $query_literal = true;
                }            
        }


        if (!empty($detour)) {
            $newUrl = $this->segmentReplace($url, $detour['original_url'], $detour['new_url'], $query_literal);
            $site_url   = (ee()->config->item('site_url')) ? rtrim(ee()->config->item('site_url'), '/') . '/' : '';
            $site_index = (ee()->config->item('site_index')) ? rtrim(ee()->config->item('site_index'), '/') . '/' : '';
            $site_index = $site_url . $site_index;

            if (isset($this->settings['hit_counter']) && $this->settings['hit_counter'] == 'y') {
                // Update detours_hits table
                ee()->db->set('detour_id', $detour['detour_id']);
                ee()->db->set('hit_date', 'NOW()', false);
                ee()->db->insert('detours_hits');
            }

            if ($url != $newUrl) {
                if (substr($detour['new_url'], 0, 4) == 'http') {
                    header('Location: ' . $newUrl, true, $detour['detour_method']);
                } else {
                    header('Location: ' . $site_index . ltrim($newUrl, '/'), true, $detour['detour_method']);
                    //header('Location: ' . $newUrl, true, $detour['detour_method']);
                }
                ee()->extensions->end_script = true;
                exit;
            }
        }
    }

    /**************************************************\
     ******************* ALL ELSE: *******************
    \**************************************************/

    protected function segmentReplace($url, $originalUrl, $newUrl, $query_literal)
    {
        $replace     = $this->headsOrTails($originalUrl);
        $segments    = ee()->uri->segment_array();
        $newSegments = array();


        $originalUrlClean = trim($originalUrl, '%/');
        $newUrlClean      = trim($newUrl, '%');


        if (empty($this->settings['allow_trailing_slash']) || $this->settings['allow_trailing_slash'] != 1) {
            $newUrlClean = rtrim($newUrlClean, '/');
        }

        switch ($replace) {
            case 'both':
                $newUrl = str_replace($originalUrlClean, $newUrlClean, $url);
                break;

            case 'head':
                $newUrl = str_replace($originalUrlClean, $newUrlClean, $url);
                $newUrl = substr($newUrl, 0, (strpos($newUrl, $newUrlClean) + strlen($newUrlClean)));
                break;

            case 'tail':
                $newUrl = str_replace($originalUrlClean, $newUrlClean, $url);
                $newUrl = substr($newUrl, strpos($newUrl, $newUrlClean), strlen($newUrl));
                break;
        }

        if ($_SERVER['QUERY_STRING'] && !empty($this->settings['allow_qs']) && $this->settings['allow_qs'] == 1 && !$query_literal) {
            $newUrl .= '?' . http_build_query($_GET);
        }

        return $newUrl;
    }

    protected function headsOrTails($original_url)
    {
        if (substr($original_url, -2, 2) == '%%' && substr($original_url, 0, 2) == '%%') {
            return 'both';
        }

        if (substr($original_url, -2, 2) == '%%' && substr($original_url, 0, 2) != '%%') {
            return 'tail';
        }

        if (substr($original_url, -2, 2) != '%%' && substr($original_url, 0, 2) == '%%') {
            return 'head';
        }

        return 'none';
    }
}
//END CLASS