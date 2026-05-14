<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Get Config Tool
 *
 * Retrieve ExpressionEngine configuration values by key.
 * Useful for querying settings like base_url, site_url, debug mode, etc.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'get_config',
    description: 'Get ExpressionEngine configuration values by key (e.g., base_url, site_url, debug, etc.)'
)]
class GetConfigTool extends AbstractTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Get ExpressionEngine configuration values by key (e.g., base_url, site_url, debug, etc.)';

    public function description(): string
    {
        return $this->description;
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'key' => $schema->string()
                ->description('The configuration key to retrieve (e.g., base_url, site_url, site_name, debug, is_system_on, etc.)')
                ->required()
                ->examples(['base_url', 'site_url', 'site_name', 'debug', 'is_system_on']),
        ], ['key'])->toArray();
    }

    public function handle(array $params): array
    {
        $key = trim($params['key'] ?? '');

        if (empty($key)) {
            throw new \InvalidArgumentException('Configuration key is required');
        }

        try {
            // Get both config and defaults arrays using reflection
            // ExpressionEngine's item() method checks both config and defaults,
            // so we need to check both to accurately determine if a key exists
            $config = ee()->config->config;

            // Use reflection to access the protected defaults property
            $reflection = new \ReflectionClass(ee()->config);
            $defaultsProperty = $reflection->getProperty('defaults');
            $defaultsProperty->setAccessible(true);
            $defaults = $defaultsProperty->getValue(ee()->config);

            // Check if key exists in either config or defaults (matching item() behavior)
            $existsInConfig = array_key_exists($key, $config);
            $existsInDefaults = array_key_exists($key, $defaults);
            $keyExists = $existsInConfig || $existsInDefaults;

            if (! $keyExists) {
                return [
                    'key' => $key,
                    'value' => null,
                    'exists' => false,
                    'message' => "Configuration key '{$key}' not found",
                ];
            }

            // Get the actual value using item() which handles both config and defaults
            $value = ee()->config->item($key);

            // Determine the source of the value
            $source = $existsInConfig ? 'config' : 'defaults';

            // Return the config value
            return [
                'key' => $key,
                'value' => $value,
                'exists' => true,
                'type' => gettype($value),
                'source' => $source,
            ];

        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to retrieve config value: '.$e->getMessage());
        }
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function isIdempotent(): bool
    {
        return true;
    }
}
