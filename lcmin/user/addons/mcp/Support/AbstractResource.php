<?php

namespace ExpressionEngine\Addons\Mcp\Support;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Attributes\EePermissions;

/**
 * Abstract base class for MCP Resources
 *
 * Provides common functionality and structure for implementing MCP resources.
 * Extend this class and implement the abstract methods to create a resource.
 */
abstract class AbstractResource
{
    use SuppressesOutput;

    /**
     * Get the resource URI
     */
    abstract public function uri(): string;

    /**
     * Get the resource MIME type
     */
    public function mimeType(): string
    {
        return 'application/json';
    }

    /**
     * Get the resource name
     */
    public function name(): ?string
    {
        // Default to class name without suffix
        $className = basename(str_replace('\\', '/', static::class));

        return strtolower(preg_replace('/Resource$/', '', $className));
    }

    /**
     * Get the resource description
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Get the resource's category
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
     * Get the resource's permissions
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
     * Fetch the resource content
     *
     * @param  array  $params  URI parameters (for template resources)
     * @return mixed The resource content
     */
    abstract public function fetch(array $params = []);

    /**
     * Check if this is a resource template
     *
     * Resource templates use URI patterns with parameters.
     * Override this method to return true if your resource is a template.
     */
    public function isTemplate(): bool
    {
        return false;
    }

    /**
     * Get URI template (for template resources)
     *
     * If isTemplate() returns true, override this method to return
     * the URI template pattern (e.g., "user://{userId}/profile").
     */
    public function uriTemplate(): ?string
    {
        return null;
    }

    /**
     * Validate URI parameters (for template resources)
     *
     * @throws \InvalidArgumentException
     */
    public function validateParams(array $params): void
    {
        // Default implementation does no validation
    }

    /**
     * Get resource metadata for registration
     *
     * @return array{uri: string, mimeType: string, name: string|null, description: string|null, category: string|null, permissions: array, isTemplate: bool, uriTemplate: string|null}
     */
    public function getMetadata(): array
    {
        return [
            'uri' => $this->uri(),
            'mimeType' => $this->mimeType(),
            'name' => $this->name(),
            'description' => $this->description(),
            'category' => $this->category(),
            'permissions' => $this->permissions(),
            'isTemplate' => $this->isTemplate(),
            'uriTemplate' => $this->uriTemplate(),
        ];
    }
}
