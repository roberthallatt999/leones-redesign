<?php

namespace EEHarbor\DetourPro\Services;

use EEHarbor\DetourPro\FluxCapacitor\Services\McpNav as FluxNav;

class McpNav extends FluxNav
{
    protected function defaultItems($items = array())
    {
        $default_items = array(
            'index' => lang('nav_home'),
            // 'addUpdate' => lang('nav_add_detour'),
            'settings' => lang('nav_settings'),
            'purge_hits' => lang('nav_purge'),
            'https://eeharbor.com/detour-pro/documentation' => lang('nav_documentation'),
        );

        return array_merge($default_items, $items);
    }

    protected function defaultButtons()
    {
        return array(
            'index' => array('addUpdate' => 'Add'),
        );
    }

    protected function defaultActiveMap()
    {
        return array(
            'detour_pro' => 'index',
            'addUpdate' => 'index',
        );
    }

    public function postGenerateNav()
    {
    }
}
