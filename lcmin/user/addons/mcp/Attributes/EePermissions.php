<?php

namespace ExpressionEngine\Addons\Mcp\Attributes;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Attribute;

/**
 * ExpressionEngine MCP Permissions Attribute
 *
 * Used to specify permission requirements for MCP elements. Permissions can be
 * role-based, user-based, or custom logic. Multiple permissions can be specified
 * and are evaluated as an OR condition (user needs any one of the permissions).
 *
 * Example usage:
 * #[EePermissions(['role:Developer', 'role:Super Admin'])]
 * #[EePermissions(['can:manage_content'])]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class EePermissions
{
    /**
     * Array of permission requirements
     *
     * @var array<int, string>
     */
    public array $permissions;

    /**
     * Create a new permissions attribute
     *
     * @param  array<int, string>  $permissions  Array of permission strings
     *                                           Examples: 'role:Developer', 'can:manage_content', 'user:123'
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }
}
