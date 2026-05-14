<?php

namespace ExpressionEngine\Addons\Mcp\Support;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Attributes\EePermissions;

/**
 * Abstract base class for MCP Prompts
 *
 * Provides common functionality and structure for implementing MCP prompts.
 * Extend this class and implement the abstract methods to create a prompt.
 */
abstract class AbstractPrompt
{
    use SuppressesOutput;

    /**
     * Get the prompt's name
     */
    public function name(): string
    {
        // Default to class name without suffix
        $className = basename(str_replace('\\', '/', static::class));

        return strtolower(preg_replace('/Prompt$/', '', $className));
    }

    /**
     * Get the prompt's description
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Get the prompt's category
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
     * Get the prompt's permissions
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
     * Get the prompt's argument definitions
     *
     * Override this method to define the arguments your prompt accepts.
     * Return an array where keys are argument names and values are schema definitions.
     *
     * Example:
     * return [
     *     'topic' => ['type' => 'string', 'description' => 'The topic to write about'],
     *     'tone' => ['type' => 'string', 'enum' => ['formal', 'casual'], 'default' => 'formal']
     * ];
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * Handle the prompt request
     *
     * @param  array  $arguments  The input arguments
     * @return array The prompt messages array
     */
    abstract public function handle(array $arguments): array;

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
     * Get prompt metadata for registration
     *
     * @return array{name: string, description: string|null, category: string|null, permissions: array, arguments: array}
     */
    public function getMetadata(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'category' => $this->category(),
            'permissions' => $this->permissions(),
            'arguments' => $this->arguments(),
        ];
    }
}
