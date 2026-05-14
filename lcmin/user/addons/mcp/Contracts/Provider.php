<?php

namespace ExpressionEngine\Addons\Mcp\Contracts;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Services\Registrar;

/**
 * MCP Provider Interface
 *
 * Allows addons to conditionally register MCP elements at runtime.
 * Similar to Laravel's service provider pattern but focused on MCP registration.
 */
interface Provider
{
    /**
     * Register MCP elements for this addon.
     *
     * This method is called during MCP server startup to allow the addon
     * to conditionally register tools, resources, and prompts based on
     * runtime conditions, settings, or permissions.
     *
     * @param  Registrar  $registrar  The registrar instance for this server
     */
    public function register(Registrar $registrar): void;
}
