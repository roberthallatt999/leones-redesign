<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\SearchIndexStateService;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Search Index State Resource
 *
 * Provides read-only diagnostics for search index state.
 */
#[EeCategory('developer')]
class SearchIndexStateResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://search/index-state';
    }

    public function name(): ?string
    {
        return 'search-index-state';
    }

    public function description(): ?string
    {
        return 'Search index diagnostics (Pro Search/core search tables). Use ee://search/index-state for summary or ee://search/index-state/{tableName} for table detail.';
    }

    #[McpResource(
        uri: 'ee://search/index-state',
        name: 'search_index_state',
        description: 'Search index summary diagnostics'
    )]
    public function listState(): mixed
    {
        $service = new SearchIndexStateService();

        return $service->getState(true);
    }

    #[McpResourceTemplate(
        uriTemplate: 'ee://search/index-state/{tableName}',
        name: 'search_index_state_table',
        description: 'Search index diagnostics for a specific table'
    )]
    public function getTableState(string $tableName): mixed
    {
        $service = new SearchIndexStateService();

        return [
            'table' => $service->getTableState($tableName, true),
            'generated_at' => date('c'),
        ];
    }

    public function fetch(array $params = []): mixed
    {
        $service = new SearchIndexStateService();
        $includeRowCounts = isset($params['include_row_counts']) ? (bool) $params['include_row_counts'] : true;

        if (isset($params['table_name']) && is_string($params['table_name']) && trim($params['table_name']) !== '') {
            return [
                'table' => $service->getTableState(trim($params['table_name']), $includeRowCounts),
                'generated_at' => date('c'),
            ];
        }

        return $service->getState($includeRowCounts);
    }
}
