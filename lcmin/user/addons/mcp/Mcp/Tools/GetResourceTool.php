<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\ComponentDiscoveryService;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Get Resource Tool
 *
 * Retrieves a specific MCP resource by URI. Supports both regular resources
 * and template resources (with URI parameters). This tool is useful when MCP
 * clients cannot directly access resources but can call tools.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'get_resource',
    description: 'Get a specific MCP resource by URI. Supports both regular resources and template resources with URI parameters.'
)]
class GetResourceTool extends AbstractTool
{
    public function description(): string
    {
        return 'Get a specific MCP resource by URI. Supports both regular resources and template resources with URI parameters. Useful when MCP clients cannot directly access resources but can call tools.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'uri' => $schema->string()
                ->description('The resource URI to retrieve (e.g., "ee://system/info" or "ee://channels/123")')
                ->required(),
            'params' => $schema->object()
                ->description('Optional parameters for template resources. For template resources, parameters can also be extracted from the URI if not provided.')
                ->default([]),
        ], ['uri'])->toArray();
    }

    public function handle(array $params): array
    {
        $uri = trim($params['uri'] ?? '');
        $providedParams = $params['params'] ?? [];

        if (empty($uri)) {
            throw new \InvalidArgumentException('URI is required');
        }

        try {
            $discoveryService = new ComponentDiscoveryService();
            $allComponents = $discoveryService->discoverAllComponents();

            // Filter for resources only
            $resources = array_filter($allComponents, function ($component) {
                return $component['type'] === 'resource';
            });

            // Try to find matching resource
            $matchedResource = null;
            $extractedParams = [];

            // First, try exact URI match (for regular resources)
            foreach ($resources as $resource) {
                if (! empty($resource['isTemplate'])) {
                    continue; // Skip template resources for exact match
                }

                if (($resource['uri'] ?? '') === $uri) {
                    $matchedResource = $resource;
                    break;
                }
            }

            // If no exact match, try template resources
            if (! $matchedResource) {
                foreach ($resources as $resource) {
                    // Check both camelCase and snake_case for isTemplate
                    $isTemplate = ! empty($resource['isTemplate']) || ! empty($resource['is_template']);
                    if (! $isTemplate || empty($resource['uri'])) {
                        continue;
                    }

                    $uriTemplate = $resource['uri'];

                    // Debug: log template matching attempt
                    // error_log("Trying template: {$uriTemplate} against URI: {$uri}");

                    $parsedParams = $this->parseUriTemplate($uriTemplate, $uri);

                    if ($parsedParams !== null) {
                        $matchedResource = $resource;
                        $extractedParams = $parsedParams;
                        break;
                    }
                }
            }

            if (! $matchedResource) {
                return [
                    'success' => false,
                    'error' => "Resource not found for URI: {$uri}",
                    'uri' => $uri,
                    'suggestion' => 'Use list_resources tool to see all available resources',
                ];
            }

            // Merge provided params with extracted params (provided params take precedence)
            $finalParams = array_merge($extractedParams, $providedParams);

            // Get resource content
            $content = $this->fetchResource($matchedResource, $finalParams);

            // Get MIME type
            $mimeType = $matchedResource['mimeType'] ?? 'application/json';

            return [
                'success' => true,
                'uri' => $uri,
                'resource_name' => $matchedResource['name'] ?? null,
                'mime_type' => $mimeType,
                'content' => $content,
                'params_used' => ! empty($finalParams) ? $finalParams : null,
                'timestamp' => date('c'),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Failed to get resource: '.$e->getMessage(),
                'uri' => $uri,
                'timestamp' => date('c'),
            ];
        }
    }

    /**
     * Parse URI template and extract parameters from actual URI
     *
     * @param  string  $uriTemplate  Template pattern (e.g., "ee://channels/{channelId}")
     * @param  string  $actualUri  Actual URI (e.g., "ee://channels/123")
     * @return array|null Extracted parameters or null if no match
     */
    private function parseUriTemplate(string $uriTemplate, string $actualUri): ?array
    {
        // Convert template pattern to regex
        // e.g., "ee://channels/{channelId}" -> "ee://channels/([^/]+)"
        // Extract parameter names first
        preg_match_all('/\{([^}]+)\}/', $uriTemplate, $paramMatches);
        $paramNames = $paramMatches[1];

        // Build regex pattern by escaping the template and replacing {param} with capture groups
        // Split the template into parts separated by {param} patterns
        $parts = preg_split('/\{[^}]+\}/', $uriTemplate);
        $patternParts = [];

        foreach ($parts as $i => $part) {
            // Escape this part (use '#' as delimiter to avoid conflicts with '/' in URIs)
            $patternParts[] = preg_quote($part, '#');
            // Add capture group after each part (except the last)
            if ($i < count($paramNames)) {
                $patternParts[] = '([^/]+)';
            }
        }

        $pattern = '#^'.implode('', $patternParts).'$#';

        if (! preg_match($pattern, $actualUri, $matches)) {
            return null;
        }

        // Extract parameter names from template
        preg_match_all('/\{([^}]+)\}/', $uriTemplate, $paramNames);
        $paramNames = $paramNames[1];

        // Match parameter names with captured values (skip first match which is full string)
        $params = [];
        for ($i = 0; $i < count($paramNames); $i++) {
            $paramName = $paramNames[$i];
            $paramValue = $matches[$i + 1] ?? null;

            if ($paramValue !== null) {
                // Convert camelCase to snake_case for consistency with some resource implementations
                $snakeCaseName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $paramName));
                $params[$snakeCaseName] = $paramValue;
                // Also include original camelCase name for compatibility
                $params[$paramName] = $paramValue;
            }
        }

        return $params;
    }

    /**
     * Fetch resource content by instantiating the resource class and calling appropriate method
     *
     * @param  array  $resource  Resource component data from discovery
     * @param  array  $params  Parameters to pass to fetch method
     * @return mixed Resource content
     */
    private function fetchResource(array $resource, array $params)
    {
        $className = $resource['class'] ?? null;
        $methodName = $resource['method'] ?? null;

        if (! $className || ! class_exists($className)) {
            throw new \RuntimeException("Resource class not found: {$className}");
        }

        // Check if it's an AbstractResource
        $reflection = new \ReflectionClass($className);
        if ($reflection->isSubclassOf(AbstractResource::class)) {
            $instance = new $className();

            // If method is specified (method-level attribute), call that method
            if ($methodName && $reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                $methodParams = $method->getParameters();

                // Special handling: if method is 'fetch' and has array parameter, pass params array directly
                if ($methodName === 'fetch' && count($methodParams) === 1) {
                    $firstParam = $methodParams[0];
                    if ($firstParam->getType() && $firstParam->getType()->getName() === 'array') {
                        return $method->invoke($instance, $params);
                    }
                }

                // If method has no arguments but caller provided params, prefer fetch()
                // so list resources can still consume optional filtering params.
                if (count($methodParams) === 0 && ! empty($params) && $reflection->hasMethod('fetch')) {
                    $fetchMethod = $reflection->getMethod('fetch');
                    $fetchParams = $fetchMethod->getParameters();
                    if (count($fetchParams) === 1) {
                        $firstParam = $fetchParams[0];
                        if ($firstParam->getType() && $firstParam->getType()->getName() === 'array') {
                            return $fetchMethod->invoke($instance, $params);
                        }
                    }
                }

                // Build arguments array matching method parameters
                $args = [];
                foreach ($methodParams as $param) {
                    $paramName = $param->getName();
                    // Try both camelCase and snake_case versions
                    $value = $params[$paramName] ?? $params[$this->toSnakeCase($paramName)] ?? null;

                    if ($value === null && ! $param->isOptional()) {
                        throw new \InvalidArgumentException("Required parameter '{$paramName}' is missing");
                    }

                    $args[] = $value;
                }

                return $method->invokeArgs($instance, $args);
            } else {
                // Call fetch() method with params
                if (! method_exists($instance, 'fetch')) {
                    throw new \RuntimeException("Resource class {$className} does not have a fetch() method");
                }

                return $instance->fetch($params);
            }
        } else {
            // For non-AbstractResource classes, try to call as callable
            if ($methodName && $reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                $instance = $reflection->isInstantiable() ? new $className() : null;

                if ($instance) {
                    $methodParams = $method->getParameters();
                    $args = [];
                    foreach ($methodParams as $param) {
                        $paramName = $param->getName();
                        $value = $params[$paramName] ?? $params[$this->toSnakeCase($paramName)] ?? null;

                        if ($value === null && ! $param->isOptional()) {
                            throw new \InvalidArgumentException("Required parameter '{$paramName}' is missing");
                        }

                        $args[] = $value;
                    }

                    return $method->invokeArgs($instance, $args);
                }
            }

            throw new \RuntimeException("Cannot instantiate or call resource class: {$className}");
        }
    }

    /**
     * Convert camelCase to snake_case
     */
    private function toSnakeCase(string $str): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
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
