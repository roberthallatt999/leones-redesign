<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\GraphqlService;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;

/**
 * GraphQL Schema Resource
 *
 * Provides GraphQL introspection output using configured/default endpoint.
 */
#[EeCategory('developer')]
class GraphqlSchemaResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://graphql/schema';
    }

    public function name(): ?string
    {
        return 'graphql-schema';
    }

    public function description(): ?string
    {
        return 'GraphQL schema introspection for headless workflows. Uses configured/default endpoint unless endpoint_url is provided via get_resource params.';
    }

    #[McpResource(
        uri: 'ee://graphql/schema',
        name: 'graphql_schema',
        description: 'GraphQL schema introspection'
    )]
    public function readSchema(): mixed
    {
        $service = new GraphqlService();
        $result = $service->introspect(null, [], 15);
        $result['generated_at'] = date('c');

        return $result;
    }

    public function fetch(array $params = []): mixed
    {
        $endpointUrl = isset($params['endpoint_url']) ? (string) $params['endpoint_url'] : null;
        $headers = isset($params['headers']) && is_array($params['headers']) ? $params['headers'] : [];
        $timeout = isset($params['timeout_seconds']) ? (int) $params['timeout_seconds'] : 15;

        $service = new GraphqlService();
        $result = $service->introspect($endpointUrl, $this->normalizeHeaders($headers), $timeout);
        $result['generated_at'] = date('c');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
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
