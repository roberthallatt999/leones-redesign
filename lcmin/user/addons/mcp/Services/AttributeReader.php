<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Attributes\EePermissions;
use ReflectionClass;

/**
 * MCP Attribute Reader Service
 *
 * Reads EE-specific MCP attributes from classes and methods to extract
 * category and permission information for MCP elements.
 */
class AttributeReader
{
    /**
     * Get category information for an MCP element class
     *
     * @param  string  $className  The class name to inspect
     * @return string|null The category name, or null if not specified
     */
    public function getCategory(string $className): ?string
    {
        try {
            $reflection = new ReflectionClass($className);

            // Check class-level attributes first
            $attributes = $reflection->getAttributes(EeCategory::class);
            if (! empty($attributes)) {
                return $attributes[0]->newInstance()->category;
            }

            // Check for method-level attributes (for attribute-based discovery)
            foreach ($reflection->getMethods() as $method) {
                $methodAttributes = $method->getAttributes(EeCategory::class);
                if (! empty($methodAttributes)) {
                    return $methodAttributes[0]->newInstance()->category;
                }
            }

            return null;
        } catch (\Throwable $e) {
            // Log error but don't fail - attributes are optional
            ee()->logger->developer("[MCP AttributeReader] Error reading category for {$className}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get permissions information for an MCP element class
     *
     * @param  string  $className  The class name to inspect
     * @return array<int, string> Array of permission requirements
     */
    public function getPermissions(string $className): array
    {
        try {
            $reflection = new ReflectionClass($className);
            $permissions = [];

            // Check class-level attributes first
            $attributes = $reflection->getAttributes(EePermissions::class);
            foreach ($attributes as $attribute) {
                $permissions = array_merge($permissions, $attribute->newInstance()->permissions);
            }

            // Check for method-level attributes (for attribute-based discovery)
            foreach ($reflection->getMethods() as $method) {
                $methodAttributes = $method->getAttributes(EePermissions::class);
                foreach ($methodAttributes as $attribute) {
                    $permissions = array_merge($permissions, $attribute->newInstance()->permissions);
                }
            }

            return array_unique($permissions);
        } catch (\Throwable $e) {
            // Log error but don't fail - attributes are optional
            ee()->logger->developer("[MCP AttributeReader] Error reading permissions for {$className}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Get both category and permissions for an MCP element class
     *
     * @param  string  $className  The class name to inspect
     * @return array{category: string|null, permissions: array<int, string>}
     */
    public function getElementMetadata(string $className): array
    {
        return [
            'category' => $this->getCategory($className),
            'permissions' => $this->getPermissions($className),
        ];
    }
}
