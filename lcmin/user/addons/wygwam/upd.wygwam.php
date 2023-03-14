<?php

use EEHarbor\Wygwam\Helper;
use EEHarbor\Wygwam\FluxCapacitor\Base\Upd;
use EEHarbor\Wygwam\FluxCapacitor\Conduit\Version;

/**
 * Wygwam Update Class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Wygwam_upd extends Upd
{

    /**
     * @var EllisLab\ExpressionEngine\Service\Addon\Addon
     */
    private $_info = null;
    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->_info = Helper::getInfo();

        ee()->load->dbforge();
    }

    // --------------------------------------------------------------------

    /**
     * Install Wygwam
     */
    public function install()
    {
        parent::install();

        // -------------------------------------------
        //  Create the exp_wygwam_configs table
        // -------------------------------------------

        if (!ee()->db->table_exists('wygwam_configs')) {
            ee()->dbforge->add_field(array(
                'config_id'   => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'auto_increment' => true),
                'config_name' => array('type' => 'varchar', 'constraint' => 32),
                'settings'    => array('type' => 'text')
            ));

            ee()->dbforge->add_key('config_id', true);
            ee()->dbforge->create_table('wygwam_configs');
        }

        // -------------------------------------------
        //  Populate it
        // -------------------------------------------
        $toolbars = Helper::defaultToolbars();

        foreach ($toolbars as $name => &$toolbar) { // WTF PHP
            $config_settings = array_merge(Helper::defaultConfigSettings(), array('toolbar' => $toolbar));

            /**
             * @var $config \EEHarbor\Wygwam\Model\Config
             */
            $config = ee('Model')->make('wygwam:Config');
            $config->config_name = $name;
            $config->settings = $config_settings;
            $config->save();
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Update Wygwam.
     */
    public function update($current = '')
    {
        $model = Helper::getFieldtypeModel();
        $model->version = $this->_info->getVersion();
        $model->save();

        // Update fields
        $fields = ee('Model')->get('ChannelField')->filter('field_type', '==', 'wygwam')->all();

        foreach ($fields as $field) {
            /**
             * @var EllisLab\ExpressionEngine\Model\Channel\ChannelField $field
             */
            $fieldSettings = $field->field_settings;

            // config => config_id
            if (isset($fieldSettings['config']) && $fieldSettings['config']) {
                $fieldSettings['config_id'] = $fieldSettings['config'];
                unset($fieldSettings['config']);
            }

            $fieldSettings['field_wide'] = true;
            $field->field_settings = $fieldSettings;
            $field->save();
        }

        // Update Grid columns
        if (ee()->db->table_exists('grid_columns')) {
            $result_query = ee()->db
                    ->select('col_id, col_settings')
                    ->from('grid_columns')
                    ->where('col_type', 'wygwam')
                    ->get();

            foreach ($result_query->result_array() as $row) {
                $decoded = json_decode($row['col_settings']);

                if (isset($decoded->config)) {
                    $decoded->config_id = $decoded->config;
                    unset($decoded->config);
                    ee()->db->update('grid_columns', array('col_settings' => json_encode($decoded)), array('col_id' => $row['col_id']));
                }
            }
        }

        // If older version
        if (version_compare($current, '4.1.14', '<')) {
            $version = new Version();
            $settings = Helper::getGlobalSettings();

            if (!empty($settings['license_key'])) {
                $data = array(
                    'site_id' => ee()->config->item('site_id'),
                    'addon' => 'EEHarbor\Wygwam',
                    'license_key' => $settings['license_key'],
                );

                $version->saveLicenseKey($data);
            }
        }

        // -------------
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Uninstall Wygwam.
     */
    public function uninstall()
    {
        // Remove from modules
        ee('Model')->get('Module')->filter('module_name', '==', 'wygwam')->delete();

        // Drop the exp_wygwam_configs table
        ee()->load->dbforge();
        ee()->dbforge->drop_table('wygwam_configs');

        return true;
    }
}
