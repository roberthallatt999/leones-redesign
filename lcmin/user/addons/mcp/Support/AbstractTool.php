<?php

namespace ExpressionEngine\Addons\Mcp\Support;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Attributes\EePermissions;

/**
 * Abstract base class for MCP Tools
 *
 * Provides common functionality and structure for implementing MCP tools.
 * Extend this class and implement the abstract methods to create a tool.
 */
abstract class AbstractTool
{
    use SuppressesOutput;

    /**
     * Get the tool's name
     */
    public function name(): string
    {
        // Default to class name without suffix, converting CamelCase to snake_case
        $className = basename(str_replace('\\', '/', static::class));
        $baseName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
        $baseName = preg_replace('/_(tool|resource|prompt)$/i', '', $baseName);

        return $baseName;
    }

    /**
     * Get the tool's description
     */
    abstract public function description(): string;

    /**
     * Get the tool's category
     */
    public function category(): ?string
    {
        // Check for EeCategory attribute
        $reflection = new \ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(EeCategory::class);

        if (! empty($attributes)) {
            return $attributes[0]->newInstance()->category;
        }

        return null;
    }

    /**
     * Get the tool's permissions
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        // Check for EePermissions attribute
        $reflection = new \ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(EePermissions::class);

        $permissions = [];
        foreach ($attributes as $attribute) {
            $permissions = array_merge($permissions, $attribute->newInstance()->permissions);
        }

        return array_unique($permissions);
    }

    /**
     * Get the tool's input schema
     *
     * Override this method to define the input parameters for your tool.
     * Return an array where keys are parameter names and values are schema definitions.
     *
     * Example:
     * return [
     *     'location' => ['type' => 'string', 'description' => 'The location to get weather for'],
     *     'units' => ['type' => 'string', 'enum' => ['celsius', 'fahrenheit'], 'default' => 'celsius']
     * ];
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * Handle the tool execution
     *
     * @param  array  $arguments  The input arguments
     * @return mixed The tool result
     */
    abstract public function handle(array $arguments);

    /**
     * Check if the tool is idempotent
     *
     * Idempotent tools can be called multiple times with the same result.
     * Override this method to return true if your tool is idempotent.
     */
    public function isIdempotent(): bool
    {
        return false;
    }

    /**
     * Check if the tool is read-only
     *
     * Read-only tools don't modify any data.
     * Override this method to return true if your tool is read-only.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Check if the tool may perform destructive operations
     *
     * Destructive tools may delete data, drop tables, clear caches, or make
     * other irreversible changes. AI clients may require extra user confirmation
     * before calling destructive tools.
     *
     * Override this method to return true if your tool can be destructive.
     */
    public function isDestructive(): bool
    {
        return false;
    }

    /**
     * Check if the tool interacts with an open world
     *
     * Open-world tools interact with external entities beyond the local
     * environment (e.g., making HTTP requests, calling external APIs).
     * Closed-world tools only affect the local system.
     *
     * Override this method to return true if your tool is open-world.
     */
    public function isOpenWorld(): bool
    {
        return false;
    }

    /**
     * Validate input arguments
     *
     * Override this method to add custom validation logic.
     * Throw exceptions for invalid input.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(array $arguments): void
    {
        // Default implementation does no validation
    }

    /**
     * Get tool metadata for registration
     *
     * @return array{name: string, description: string, category: string|null, permissions: array, schema: array, idempotent: bool, readonly: bool, destructive: bool, openWorld: bool}
     */
    public function getMetadata(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'category' => $this->category(),
            'permissions' => $this->permissions(),
            'schema' => $this->schema(),
            'idempotent' => $this->isIdempotent(),
            'readonly' => $this->isReadOnly(),
            'destructive' => $this->isDestructive(),
            'openWorld' => $this->isOpenWorld(),
        ];
    }
}
