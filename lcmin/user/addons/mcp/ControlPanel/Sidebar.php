<?php

namespace ExpressionEngine\Addons\Mcp\ControlPanel;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractSidebar;

class Sidebar extends AbstractSidebar
{
    // Automatically generate the sidebar using the add-on's Mcp routes
    public $automatic = false;

    // No header - sidebar items will appear directly in the main sidebar
    public $header = null;

    public function process()
    {
        // Get the CP/Sidebar object for manual sidebar adjustments
        // $this->getSidebar();
    }
}
