<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Clear Cache Tool
 *
 * Provides functionality to clear ExpressionEngine caches of various types.
 * This tool allows clearing page caches, tag caches, database caches, or all caches.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'clear_cache',
    description: 'Clear ExpressionEngine caches by type (all, page, tag, db)'
)]
class ClearCacheTool extends AbstractTool
{
    /**
     * Available cache types
     */
    private const AVAILABLE_CACHES = [
        'all' => 'Clear all caches',
        'page' => 'Clear template/page caches',
        'tag' => 'Clear tag caches',
        'db' => 'Clear database caches',
    ];

    public function description(): string
    {
        return 'Clear ExpressionEngine caches by type (all, page, tag, db)';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'cache_type' => $schema->enum(array_keys(self::AVAILABLE_CACHES))
                ->description('The type of cache to clear')
                ->default('all'),
        ], [])->toArray();
    }

    public function isDestructive(): bool
    {
        return true;
    }

    public function isIdempotent(): bool
    {
        return true;
    }

    public function handle(array $params): array
    {
        $cacheType = $params['cache_type'] ?? 'all';

        // Validate cache type
        if (! array_key_exists($cacheType, self::AVAILABLE_CACHES)) {
            throw new \InvalidArgumentException("Invalid cache type: {$cacheType}. Available types: ".implode(', ', array_keys(self::AVAILABLE_CACHES)));
        }

        try {
            // Load required EE components
            ee()->load->driver('cache');
            ee()->load->library('functions');

            // Clear the specified cache type
            ee()->functions->clear_caching($cacheType);

            $description = self::AVAILABLE_CACHES[$cacheType];

            return [
                'success' => true,
                'message' => "Successfully cleared {$cacheType} cache",
                'cache_type' => $cacheType,
                'description' => $description,
                'timestamp' => date('c'),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to clear cache: '.$e->getMessage(),
                'cache_type' => $cacheType,
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ];
        }
    }
}
