<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Search Index State Service
 *
 * Provides operational visibility into search-related table state, with a
 * focus on Pro Search installations.
 */
class SearchIndexStateService
{
    /**
     * Get overall search index/state diagnostics.
     */
    public function getState(bool $includeRowCounts = true): array
    {
        $prefix = (string) (ee()->db->dbprefix ?? '');
        $proSearchTables = $this->discoverTables($prefix.'pro_search%');
        $coreSearchTables = $this->discoverTables($prefix.'search%');

        $proStates = $this->buildStates($proSearchTables, $includeRowCounts, 'pro_search');
        $coreStates = $this->buildStates($coreSearchTables, $includeRowCounts, 'core_search');
        $totalRows = $this->sumRows($proStates) + $this->sumRows($coreStates);

        return [
            'pro_search_installed' => $this->isAddonInstalled('pro_search'),
            'detected_pro_search_tables' => $proSearchTables,
            'detected_core_search_tables' => $coreSearchTables,
            'pro_search_table_states' => $proStates,
            'core_search_table_states' => $coreStates,
            'table_states' => array_merge($proStates, $coreStates),
            'summary' => [
                'pro_search_table_count' => count($proSearchTables),
                'core_search_table_count' => count($coreSearchTables),
                'table_count' => count($proSearchTables) + count($coreSearchTables),
                'total_rows' => $totalRows,
                'includes_row_counts' => $includeRowCounts,
            ],
            'generated_at' => date('c'),
        ];
    }

    /**
     * Get diagnostics for a specific table by name.
     */
    public function getTableState(string $tableName, bool $includeRowCount = true): array
    {
        $tableName = trim($tableName);
        if ($tableName === '') {
            throw new \InvalidArgumentException('table_name is required.');
        }

        if (! $this->isValidIdentifier($tableName)) {
            throw new \InvalidArgumentException('Invalid table_name format.');
        }

        if (! $this->tableExists($tableName)) {
            throw new \RuntimeException("Table '{$tableName}' was not found.");
        }

        return $this->tableState($tableName, $includeRowCount, true);
    }

    /**
     * @return array<int, string>
     */
    private function discoverTables(string $pattern): array
    {
        $query = ee()->db->query('SHOW TABLES LIKE ?', [$pattern]);
        $rows = $query->result_array();

        $tables = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $values = array_values($row);
            if (isset($values[0]) && is_string($values[0])) {
                $tables[] = $values[0];
            }
        }

        sort($tables);

        return $tables;
    }

    /**
     * @return array{
     *   table_name: string,
     *   exists: bool,
     *   row_count: int|null
     * }
     */
    private function tableState(string $tableName, bool $includeRowCount, ?bool $exists = null): array
    {
        $exists = $exists ?? $this->tableExists($tableName);
        $rowCount = null;

        if ($exists && $includeRowCount) {
            $rowCount = $this->countRows($tableName);
        }

        return [
            'table_name' => $tableName,
            'exists' => $exists,
            'row_count' => $rowCount,
        ];
    }

    private function tableExists(string $tableName): bool
    {
        $query = ee()->db->query(
            'SELECT COUNT(*) AS match_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );
        $row = $query->row_array();

        return (int) ($row['match_count'] ?? 0) > 0;
    }

    private function countRows(string $tableName): int
    {
        if (! $this->isValidIdentifier($tableName)) {
            return 0;
        }

        $query = ee()->db->query('SELECT COUNT(*) AS row_count FROM `'.$tableName.'`');
        $row = $query->row_array();

        return (int) ($row['row_count'] ?? 0);
    }

    private function isValidIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $identifier) === 1;
    }

    private function isAddonInstalled(string $addonName): bool
    {
        try {
            $installed = ee('Addon')->installed();
            foreach ($installed as $addon) {
                if ((string) $addon->getPrefix() === $addonName) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $tableNames
     * @return array<int, array{table_name: string, exists: bool, row_count: int|null, table_family: string}>
     */
    private function buildStates(array $tableNames, bool $includeRowCount, string $family): array
    {
        $states = [];
        foreach ($tableNames as $tableName) {
            $state = $this->tableState($tableName, $includeRowCount, true);
            $state['table_family'] = $family;
            $states[] = $state;
        }

        return $states;
    }

    /**
     * @param  array<int, array{row_count?: int|null}>  $states
     */
    private function sumRows(array $states): int
    {
        $total = 0;
        foreach ($states as $state) {
            $total += (int) ($state['row_count'] ?? 0);
        }

        return $total;
    }
}
