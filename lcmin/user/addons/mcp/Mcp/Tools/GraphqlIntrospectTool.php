<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\GraphqlService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * GraphQL Introspect Tool
 *
 * Runs GraphQL introspection against a configured/provided endpoint.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'graphql_introspect',
    description: 'Run GraphQL schema introspection against a configured or explicit endpoint.'
)]
class GraphqlIntrospectTool extends AbstractTool
{
    public function description(): string
    {
        return 'Run GraphQL schema introspection for headless workflows. Uses explicit endpoint_url when provided, otherwise configured defaults.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'endpoint_url' => $schema->string()
                ->description('Optional explicit GraphQL endpoint URL (http/https)'),
            'headers' => $schema->object()
                ->description('Optional request headers map (for auth tokens, etc.)')
                ->default([]),
            'timeout_seconds' => $schema->integer(1, 120)
                ->description('Request timeout in seconds')
                ->default(15),
        ], [])->toArray();
    }

    public function handle(array $arguments): array
    {
        $endpointUrl = isset($arguments['endpoint_url']) ? trim((string) $arguments['endpoint_url']) : null;
        $headers = isset($arguments['headers']) && is_array($arguments['headers']) ? $arguments['headers'] : [];
        $timeout = isset($arguments['timeout_seconds']) ? (int) $arguments['timeout_seconds'] : 15;

        $service = new GraphqlService();
        $result = $service->introspect($endpointUrl, $this->normalizeHeaders($headers), $timeout);
        $result['generated_at'] = date('c');

        return $result;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function isIdempotent(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (! is_scalar($value)) {
                continue;
            }

            $headerName = trim($key);
            if ($headerName === '') {
                continue;
            }

            $normalized[$headerName] = (string) $value;
        }

        return $normalized;
    }
}
