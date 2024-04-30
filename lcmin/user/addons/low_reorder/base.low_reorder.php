<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Low\Reorder\Library\Fields;
use Low\Reorder\Library\Param;
use Low\Reorder\Library\Sieve;
use Low\Reorder\FluxCapacitor\Conduit\Version;

/**
 * Low Reorder Base Trait
 *
 * @package        low_reorder
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-reorder
 * @copyright      Copyright (c) 2019, Low
 */
trait Low_reorder_base
{

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Add-on version
     *
     * @var        string
     * @access     public
     */
    public $version;

    // --------------------------------------------------------------------

    /**
     * Package name
     *
     * @var        string
     * @access     protected
     */
    protected $package = 'low_reorder';

    /**
     * This add-on's info based on setup file
     *
     * @access      private
     * @var         object
     */
    protected $info;

    /**
     * Main class shortcut
     *
     * @var        string
     * @access     protected
     */
    protected $class_name;

    /**
     * Site id shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $site_id;

    /**
     * Libraries used
     *
     * @var        array
     * @access     protected
     */
    protected $libraries = array();

    /**
     * License Key shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $license_key;

    /**
     * Ignore Site shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $ignore_site;

    /**
     * Models used
     *
     * @var        array
     * @access     protected
     */
    protected $models = array(
        'low_reorder_set_model',
        'low_reorder_order_model'
    );

    /**
     * Default extension settings
     */
    protected $default_settings = array(
        'can_create_sets' => array()
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Initialize Base data
     *
     * @access     public
     * @return     void
     */
    public function initializeBaseData()
    {
        // Instantiate version class
        $version = new Version();

        // -------------------------------------
        //  Set info and version
        // -------------------------------------

        $this->info = ee('App')->get($this->package);
        $this->version = $this->info->getVersion();

        // -------------------------------------
        //  Load helper, libraries and models
        // -------------------------------------

        ee()->load->helper($this->package);
        ee()->load->model($this->models);

        // -------------------------------------
        //  Class name shortcut
        // -------------------------------------

        $this->class_name = ucfirst($this->package);

        // -------------------------------------
        //  Get site shortcut
        // -------------------------------------

        $this->site_id = (int) ee()->config->item('site_id');

        // -------------------------------------
        //  Get license key and ignore site shortcut
        // -------------------------------------

        if (REQ == 'CP') {
			$this->license_key = $version->getLicenseKey();
			$this->ignore_site = $version->getIgnoreSite();
		}
    }

    // --------------------------------------------------------------------

    /**
     * Get simple list of entries based on given parameters, set order and limit
     *
     * @access     private
     * @param      array
     * @param      string
     * @param      int
     * @return     array
     */
    protected function get_entries($params, $set_order = array(), $limit = false)
    {
        // Add site ID to parameters
        $site_ids = isset(ee()->TMPL)
            ? array_values(ee()->TMPL->site_ids)
            : array(ee()->config->item('site_id'));

        $select = ['entry_id', 'channel_id', 'title', 'status', 'url_title', 'entry_date'];

        $sieve = new Sieve();
        $sieve->select($select);
        $sieve->params($params);
        $sieve->filter('site_id', 'IN', $site_ids);

        // Sticky only
        if (! empty($params['sticky']) && in_array($params['sticky'], array('y', 'yes'))) {
            $sieve->filter('sticky', 'y');
        }

        // Initiate get_entries
        $entries = array();

        // Populate entries array with selected keys only
        foreach ($sieve->get() as $entry) {
            $row = [];

            foreach ($select as $key) {
                $row[$key] = $entry->$key;
            }

            $entries[$entry->entry_id] = $row;
        }

        // Order the set if necessary
        if (is_array($set_order) && ! empty($set_order)) {
            $ordered = [];

            foreach ($set_order as $id) {
                if (array_key_exists($id, $entries)) {
                    $ordered[$id] = $entries[$id];
                }
            }

            $entries = $ordered;
        }

        // Reset Entries
        $entries = array_values($entries);

        // Limit
        if ($limit) {
            $entries = array_slice($entries, 0, $limit);
        }

        // Return clean array
        return $entries;
    }

    // --------------------------------------------------------------------
}
// End trait low_reorder_base
