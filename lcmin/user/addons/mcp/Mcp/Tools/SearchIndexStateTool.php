<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\SearchIndexStateService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Search Index State Tool
 *
 * Reports search index/state diagnostics (Pro Search + core search tables).
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'search_index_state',
    description: 'Inspect Pro Search/core search table state, table counts, and index diagnostics.'
)]
class SearchIndexStateTool extends AbstractTool
{
    public function description(): string
    {
        return 'Inspect search index state for Pro Search and core search tables, including optional row counts and per-table diagnostics.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'table_name' => $schema->string()
                ->description('Optional specific table name for per-table diagnostics'),
            'include_row_counts' => $schema->boolean()
                ->description('Include row counts in diagnostics')
                ->default(true),
        ], [])->toArray();
    }

    public function handle(array $arguments): array
    {
        $tableName = isset($arguments['table_name']) ? trim((string) $arguments['table_name']) : null;
        $includeRowCounts = isset($arguments['include_row_counts']) ? (bool) $arguments['include_row_counts'] : true;

        $service = new SearchIndexStateService();

        try {
            if ($tableName !== null && $tableName !== '') {
                return [
                    'success' => true,
                    'mode' => 'table',
                    'table' => $service->getTableState($tableName, $includeRowCounts),
                    'generated_at' => date('c'),
                ];
            }

            return [
                'success' => true,
                'mode' => 'summary',
                'state' => $service->getState($includeRowCounts),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'mode' => $tableName ? 'table' : 'summary',
                'generated_at' => date('c'),
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
