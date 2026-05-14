<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\ComponentDiscoveryService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * List Resources Tool
 *
 * Lists all available MCP resources with their URIs, names, descriptions, and metadata.
 * This tool is useful when MCP clients cannot directly access resources but can call tools.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'list_resources',
    description: 'List all available MCP resources with their URIs, names, descriptions, and metadata'
)]
class ListResourcesTool extends AbstractTool
{
    public function description(): string
    {
        return 'List all available MCP resources with their URIs, names, descriptions, and metadata. Useful when MCP clients cannot directly access resources but can call tools.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'filter' => $schema->string()
                ->description('Optional filter to search resources by name, URI, or description')
                ->default(''),
        ], [])->toArray();
    }

    public function handle(array $params): array
    {
        $filter = trim($params['filter'] ?? '');

        try {
            $discoveryService = new ComponentDiscoveryService();
            $allComponents = $discoveryService->discoverAllComponents();

            // Filter for resources only
            $resources = array_filter($allComponents, function ($component) {
                return $component['type'] === 'resource';
            });

            // Apply filter if provided
            if (! empty($filter)) {
                $filterLower = strtolower($filter);
                $resources = array_filter($resources, function ($resource) use ($filterLower) {
                    $name = strtolower($resource['name'] ?? '');
                    $uri = strtolower($resource['uri'] ?? '');
                    $description = strtolower($resource['description'] ?? '');
                    $addon = strtolower($resource['addon'] ?? '');

                    return strpos($name, $filterLower) !== false
                        || strpos($uri, $filterLower) !== false
                        || strpos($description, $filterLower) !== false
                        || strpos($addon, $filterLower) !== false;
                });
            }

            // Format resources for output
            $formattedResources = [];
            foreach ($resources as $resource) {
                $formattedResources[] = [
                    'uri' => $resource['uri'] ?? null,
                    'name' => $resource['name'] ?? null,
                    'description' => $resource['description'] ?? '',
                    'mime_type' => $resource['mimeType'] ?? 'application/json',
                    'category' => $resource['category'] ?? null,
                    'addon' => $resource['addon'] ?? null,
                    'is_template' => ! empty($resource['isTemplate']),
                    'class' => $resource['class'] ?? null,
                    'method' => $resource['method'] ?? null,
                ];
            }

            // Sort by addon, then by name
            usort($formattedResources, function ($a, $b) {
                $addonCompare = strcmp($a['addon'] ?? '', $b['addon'] ?? '');
                if ($addonCompare !== 0) {
                    return $addonCompare;
                }

                return strcmp($a['name'] ?? '', $b['name'] ?? '');
            });

            return [
                'success' => true,
                'resources' => $formattedResources,
                'count' => count($formattedResources),
                'filter' => $filter ?: null,
                'timestamp' => date('c'),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Failed to list resources: '.$e->getMessage(),
                'resources' => [],
                'count' => 0,
                'timestamp' => date('c'),
            ];
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
