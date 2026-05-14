<?php

namespace ExpressionEngine\Addons\Mcp\Attributes;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Attribute;

/**
 * ExpressionEngine MCP Category Attribute
 *
 * Used to categorize MCP elements (tools, resources, prompts) for organization
 * and permission management. Categories help group related functionality and
 * allow administrators to enable/disable groups of elements at once.
 *
 * Example usage:
 * #[EeCategory('developer')]
 * #[EeCategory('content')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class EeCategory
{
    /**
     * The category name
     */
    public string $category;

    /**
     * Create a new category attribute
     *
     * @param  string  $category  The category name (e.g., 'developer', 'content', 'admin')
     */
    public function __construct(string $category)
    {
        $this->category = $category;
    }
}
