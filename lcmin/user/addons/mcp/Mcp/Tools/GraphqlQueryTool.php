<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\GraphqlService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * GraphQL Query Tool
 *
 * Executes GraphQL query operations against configured/provided endpoint.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'graphql_query',
    description: 'Execute GraphQL queries against configured/provided endpoint (mutations blocked by default).'
)]
class GraphqlQueryTool extends AbstractTool
{
    public function description(): string
    {
        return 'Execute GraphQL query operations for headless workflows. Mutations are blocked unless allow_mutations=true.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'query' => $schema->string()
                ->description('GraphQL query string')
                ->required(),
            'variables' => $schema->object()
                ->description('Optional GraphQL variables object')
                ->default([]),
            'operation_name' => $schema->string()
                ->description('Optional GraphQL operationName'),
            'endpoint_url' => $schema->string()
                ->description('Optional explicit GraphQL endpoint URL (http/https)'),
            'headers' => $schema->object()
                ->description('Optional request headers map (for auth tokens, etc.)')
                ->default([]),
            'timeout_seconds' => $schema->integer(1, 120)
                ->description('Request timeout in seconds')
                ->default(15),
            'allow_mutations' => $schema->boolean()
                ->description('Allow mutation operations. Default false for safety.')
                ->default(false),
        ], ['query'])->toArray();
    }

    public function handle(array $arguments): array
    {
        $query = (string) ($arguments['query'] ?? '');
        $variables = isset($arguments['variables']) && is_array($arguments['variables']) ? $arguments['variables'] : [];
        $operationName = isset($arguments['operation_name']) ? trim((string) $arguments['operation_name']) : null;
        $endpointUrl = isset($arguments['endpoint_url']) ? trim((string) $arguments['endpoint_url']) : null;
        $headers = isset($arguments['headers']) && is_array($arguments['headers']) ? $arguments['headers'] : [];
        $timeout = isset($arguments['timeout_seconds']) ? (int) $arguments['timeout_seconds'] : 15;
        $allowMutations = isset($arguments['allow_mutations']) ? (bool) $arguments['allow_mutations'] : false;

        $service = new GraphqlService();
        $result = $service->query(
            $query,
            $endpointUrl,
            $variables,
            $operationName,
            $this->normalizeHeaders($headers),
            $timeout,
            $allowMutations
        );
        $result['executed_at'] = date('c');

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
