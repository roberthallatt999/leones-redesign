<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * GraphQL Service
 *
 * Executes GraphQL introspection and queries against configured or provided
 * endpoints for headless workflows.
 */
class GraphqlService
{
    private const DEFAULT_TIMEOUT_SECONDS = 15;

    /**
     * GraphQL schema introspection query.
     */
    private const INTROSPECTION_QUERY = <<<'GRAPHQL'
query IntrospectionQuery {
  __schema {
    queryType { name }
    mutationType { name }
    subscriptionType { name }
    types {
      kind
      name
      description
      fields(includeDeprecated: true) {
        name
      }
    }
    directives {
      name
      description
      locations
    }
  }
}
GRAPHQL;

    public function introspect(?string $endpointUrl = null, array $headers = [], int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS): array
    {
        $endpoint = $this->resolveEndpoint($endpointUrl);
        if ($endpoint === null) {
            return [
                'success' => false,
                'error' => 'No GraphQL endpoint is configured. Pass endpoint_url explicitly or set EE_GRAPHQL_ENDPOINT / graphql_endpoint config.',
                'endpoint' => null,
                'data' => null,
                'errors' => [],
            ];
        }

        return $this->execute(
            $endpoint,
            self::INTROSPECTION_QUERY,
            [],
            null,
            $headers,
            $timeoutSeconds,
            true
        );
    }

    public function query(
        string $query,
        ?string $endpointUrl = null,
        array $variables = [],
        ?string $operationName = null,
        array $headers = [],
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        bool $allowMutations = false
    ): array {
        $query = trim($query);
        if ($query === '') {
            return [
                'success' => false,
                'error' => 'query is required.',
                'endpoint' => null,
                'data' => null,
                'errors' => [],
            ];
        }

        $endpoint = $this->resolveEndpoint($endpointUrl);
        if ($endpoint === null) {
            return [
                'success' => false,
                'error' => 'No GraphQL endpoint is configured. Pass endpoint_url explicitly or set EE_GRAPHQL_ENDPOINT / graphql_endpoint config.',
                'endpoint' => null,
                'data' => null,
                'errors' => [],
            ];
        }

        if (! $allowMutations && $this->looksLikeMutation($query)) {
            return [
                'success' => false,
                'error' => 'Mutation operations are blocked by default. Set allow_mutations=true if needed.',
                'endpoint' => $endpoint,
                'data' => null,
                'errors' => [],
            ];
        }

        return $this->execute(
            $endpoint,
            $query,
            $variables,
            $operationName,
            $headers,
            $timeoutSeconds,
            false
        );
    }

    public function resolveEndpoint(?string $endpointUrl = null): ?string
    {
        $explicit = trim((string) $endpointUrl);
        if ($explicit !== '') {
            return $this->isHttpUrl($explicit) ? $explicit : null;
        }

        $configured = trim((string) $this->getConfigItem('graphql_endpoint', ''));
        if ($configured !== '') {
            return $this->isHttpUrl($configured) ? $configured : null;
        }

        $envEndpoint = trim((string) (getenv('EE_GRAPHQL_ENDPOINT') ?: ''));
        if ($envEndpoint !== '') {
            return $this->isHttpUrl($envEndpoint) ? $envEndpoint : null;
        }

        $siteUrl = trim((string) $this->getConfigItem('site_url', ''));
        if ($siteUrl !== '') {
            $fallback = rtrim($siteUrl, '/').'/graphql';

            return $this->isHttpUrl($fallback) ? $fallback : null;
        }

        return null;
    }

    private function getConfigItem(string $key, $default = null)
    {
        try {
            $ee = ee();
            if (is_object($ee) && isset($ee->config) && is_object($ee->config) && method_exists($ee->config, 'item')) {
                $value = $ee->config->item($key);

                return $value !== null ? $value : $default;
            }
        } catch (\Throwable $e) {
            return $default;
        }

        return $default;
    }

    private function execute(
        string $endpoint,
        string $query,
        array $variables,
        ?string $operationName,
        array $headers,
        int $timeoutSeconds,
        bool $isIntrospection
    ): array {
        if (! $this->isHttpUrl($endpoint)) {
            return [
                'success' => false,
                'error' => 'endpoint_url must be a valid http(s) URL.',
                'endpoint' => $endpoint,
                'data' => null,
                'errors' => [],
            ];
        }

        if ($timeoutSeconds < 1 || $timeoutSeconds > 120) {
            $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS;
        }

        $payload = [
            'query' => $query,
            'variables' => (object) $variables,
        ];

        if ($operationName !== null && trim($operationName) !== '') {
            $payload['operationName'] = trim($operationName);
        }

        $result = $this->postJson($endpoint, $payload, $headers, $timeoutSeconds);
        if (! $result['ok']) {
            return [
                'success' => false,
                'error' => $result['error'],
                'endpoint' => $endpoint,
                'status_code' => $result['status_code'],
                'data' => null,
                'errors' => [],
            ];
        }

        $decoded = json_decode((string) $result['body'], true);
        if (! is_array($decoded)) {
            return [
                'success' => false,
                'error' => 'GraphQL endpoint returned non-JSON response.',
                'endpoint' => $endpoint,
                'status_code' => $result['status_code'],
                'data' => null,
                'errors' => [],
                'raw_response' => $result['body'],
            ];
        }

        $errors = isset($decoded['errors']) && is_array($decoded['errors']) ? $decoded['errors'] : [];
        $data = $decoded['data'] ?? null;

        return [
            'success' => empty($errors),
            'endpoint' => $endpoint,
            'status_code' => $result['status_code'],
            'is_introspection' => $isIntrospection,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array{ok: bool, status_code: int, body: string, error: string|null}
     */
    private function postJson(string $endpoint, array $payload, array $headers, int $timeoutSeconds): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            return [
                'ok' => false,
                'status_code' => 0,
                'body' => '',
                'error' => 'Failed to encode GraphQL payload.',
            ];
        }

        $headerLines = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        foreach ($headers as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                continue;
            }
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            $headerLines[] = $key.': '.$value;
        }

        if (function_exists('curl_init')) {
            return $this->postWithCurl($endpoint, $json, $headerLines, $timeoutSeconds);
        }

        return $this->postWithStreamContext($endpoint, $json, $headerLines, $timeoutSeconds);
    }

    /**
     * @param  array<int, string>  $headers
     * @return array{ok: bool, status_code: int, body: string, error: string|null}
     */
    private function postWithCurl(string $endpoint, string $json, array $headers, int $timeoutSeconds): array
    {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [
                'ok' => false,
                'status_code' => 0,
                'body' => '',
                'error' => 'Unable to initialize cURL.',
            ];
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $timeoutSeconds));

        $body = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [
                'ok' => false,
                'status_code' => $statusCode,
                'body' => '',
                'error' => $error !== '' ? $error : 'GraphQL request failed.',
            ];
        }

        return [
            'ok' => $statusCode >= 200 && $statusCode < 500,
            'status_code' => $statusCode,
            'body' => (string) $body,
            'error' => null,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @return array{ok: bool, status_code: int, body: string, error: string|null}
     */
    private function postWithStreamContext(string $endpoint, string $json, array $headers, int $timeoutSeconds): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => $timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($endpoint, false, $context);
        $statusCode = 0;

        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches) === 1) {
                $statusCode = (int) $matches[1];
            }
        }

        if ($body === false) {
            return [
                'ok' => false,
                'status_code' => $statusCode,
                'body' => '',
                'error' => 'GraphQL request failed.',
            ];
        }

        return [
            'ok' => $statusCode >= 200 && $statusCode < 500,
            'status_code' => $statusCode,
            'body' => $body,
            'error' => null,
        ];
    }

    private function looksLikeMutation(string $query): bool
    {
        $compact = ltrim($query);

        return preg_match('/^mutation\b/i', $compact) === 1;
    }

    private function isHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        return in_array($scheme, ['http', 'https'], true);
    }
}
