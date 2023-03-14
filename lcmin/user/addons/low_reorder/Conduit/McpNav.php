<?php

namespace Low\Reorder\Conduit;

use Low\Reorder\FluxCapacitor\Conduit\McpNav as FluxNav;

class McpNav extends FluxNav
{
    protected function defaultItems()
    {
        $default_items = array(
            'index' => lang('low_reorder_module_name'),
            'settings' => lang('settings'),
        );

        return array_merge($default_items);
    }

    protected function defaultButtons()
    {
        return array(
            'index' => array('edit/new' => 'New'),
        );
    }

    protected function defaultActiveMap()
    {
        return array(
            'edit' => 'index',
            'new' => 'index',
        );
    }
}
