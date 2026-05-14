<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Database Schema Tool
 *
 * Read the database schema for this application, including table names, columns, data types, indexes, foreign keys, and more.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'database_schema',
    description: 'Read the database schema for this application, including table names, columns, data types, indexes, foreign keys, and more.'
)]
class DatabaseSchema extends AbstractTool
{
    private $activeDb = null;

    /**
     * The tool's description.
     */
    protected string $description = 'Read the database schema for this application, including table names, columns, data types, indexes, foreign keys, and more.';

    public function description(): string
    {
        return $this->description;
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'database' => $schema->string()
                ->description('Optional database connection name to use. Defaults to the application\'s default connection.'),
            'filter' => $schema->string()
                ->description('Filter the tables by name. When provided, returns full detailed schema for matching tables. When omitted, returns a simplified list of all tables with basic info only.'),
        ], [])->toArray();
    }

    public function handle(array $params): array
    {
        $database = isset($params['database']) ? trim((string) $params['database']) : null;

        // Ensure filter is always a string, even if it comes in as an array or other type
        $filter = $params['filter'] ?? '';
        if (is_array($filter)) {
            $filter = '';
        } else {
            $filter = (string) $filter;
        }
        $filter = trim($filter);

        try {
            $this->activeDb = $this->resolveDatabaseConnection($database);

            $schema = $this->getDatabaseStructure($database, $filter);

            return $schema;

        } catch (\Throwable $e) {
            throw new \RuntimeException('Schema retrieval failed: '.$e->getMessage());
        } finally {
            $this->activeDb = null;
        }
    }

    protected function getDatabaseStructure(?string $database, string $filter = ''): array
    {
        return [
            'engine' => $this->getDatabaseEngine(),
            'tables' => $this->getAllTablesStructure($filter),
            'global' => $this->getGlobalStructure(),
        ];
    }

    protected function getAllTablesStructure(string $filter = ''): array
    {
        $structures = [];
        $allTables = $this->getAllTables();

        // If no filter, return simplified list with just table names and basic info
        if (empty($filter)) {
            foreach ($allTables as $tableName) {
                $structures[$tableName] = $this->getTableSummary($tableName);
            }

            return $structures;
        }

        // With filter, return full detailed structure
        foreach ($allTables as $tableName) {
            if (! str_contains(strtolower((string) $tableName), strtolower($filter))) {
                continue;
            }

            $structures[$tableName] = $this->getTableStructure($tableName);
        }

        return $structures;
    }

    /**
     * Get a simplified summary of a table (used when no filter is provided)
     */
    protected function getTableSummary(string $tableName): array
    {
        try {
            // Get just the column count and primary key info
            $query = $this->db()->query("SHOW COLUMNS FROM `{$tableName}`");
            $columns = $query->result_array();
            $columnCount = count($columns);

            // Find primary key column(s)
            $primaryKey = [];
            foreach ($columns as $column) {
                if ($column['Key'] === 'PRI') {
                    $primaryKey[] = $column['Field'];
                }
            }

            return [
                'column_count' => $columnCount,
                'primary_key' => $primaryKey,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Failed to get summary: '.$e->getMessage(),
            ];
        }
    }

    protected function getAllTables(): array
    {
        try {
            // Use direct SQL query to list tables, similar to how EE installer does it
            $prefix = $this->db()->dbprefix;
            $query = $this->db()->query('SHOW TABLES LIKE ?', [$prefix.'%']);

            $tables = [];
            foreach ($query->result_array() as $row) {
                $tableName = reset($row); // Get the first (and only) column value
                $tables[] = $tableName;
            }

            return $tables;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getTableStructure(string $tableName): array
    {
        try {
            $columns = $this->getTableColumns($tableName);
            $indexes = $this->getTableIndexes($tableName);
            $foreignKeys = $this->getTableForeignKeys($tableName);

            return [
                'columns' => $columns,
                'indexes' => $indexes,
                'foreign_keys' => $foreignKeys,
                'triggers' => [], // Not easily accessible in EE
                'check_constraints' => [], // Not easily accessible in EE
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Failed to get structure: '.$e->getMessage(),
            ];
        }
    }

    protected function getTableColumns(string $tableName): array
    {
        try {
            // Use direct SQL query to get column information
            $query = $this->db()->query("SHOW COLUMNS FROM `{$tableName}`");
            $columnDetails = [];

            foreach ($query->result_array() as $row) {
                $fieldName = $row['Field'];
                $columnDetails[$fieldName] = [
                    'type' => $row['Type'] ?? 'unknown',
                    'nullable' => ($row['Null'] === 'YES'),
                    'default' => $row['Default'],
                    'extra' => $row['Extra'] ?? '',
                ];
            }

            return $columnDetails;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getTableIndexes(string $tableName): array
    {
        try {
            $query = $this->db()->query("SHOW INDEX FROM `{$tableName}`");
            $indexes = [];

            foreach ($query->result_array() as $row) {
                $indexName = $row['Key_name'];

                if (! isset($indexes[$indexName])) {
                    $indexes[$indexName] = [
                        'columns' => [],
                        'type' => $row['Index_type'] ?? 'BTREE',
                        'is_unique' => ($row['Non_unique'] == 0),
                        'is_primary' => ($indexName === 'PRIMARY'),
                    ];
                }

                $indexes[$indexName]['columns'][] = $row['Column_name'];
            }

            return $indexes;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getTableForeignKeys(string $tableName): array
    {
        try {
            // Try to get foreign key information
            $query = $this->db()->query('
                SELECT
                    TABLE_NAME,
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE
                    REFERENCED_TABLE_SCHEMA IS NOT NULL
                    AND TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                ORDER BY
                    CONSTRAINT_NAME,
                    ORDINAL_POSITION
            ', [$tableName]);

            $foreignKeys = [];

            foreach ($query->result_array() as $row) {
                $fkName = $row['CONSTRAINT_NAME'];

                // If this foreign key constraint doesn't exist yet, create it
                if (! isset($foreignKeys[$fkName])) {
                    $foreignKeys[$fkName] = [
                        'columns' => [],
                        'referenced_table' => $row['REFERENCED_TABLE_NAME'],
                        'referenced_columns' => [],
                    ];
                }

                // Append columns (handles multi-column foreign keys)
                $foreignKeys[$fkName]['columns'][] = $row['COLUMN_NAME'];
                $foreignKeys[$fkName]['referenced_columns'][] = $row['REFERENCED_COLUMN_NAME'];
            }

            return $foreignKeys;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getGlobalStructure(): array
    {
        return [
            'views' => [], // Not easily accessible in EE
            'stored_procedures' => [], // Not easily accessible in EE
            'functions' => [], // Not easily accessible in EE
            'sequences' => [], // Not easily accessible in EE
        ];
    }

    protected function getDatabaseEngine(): string
    {
        try {
            $query = $this->db()->query('SELECT VERSION() as version');
            $result = $query->row();

            // Extract database type from the version or connection info
            if ($this->db()->dbdriver === 'mysqli') {
                return 'MySQL';
            }

            return $this->db()->dbdriver ?? 'Unknown';
        } catch (\Throwable $e) {
            return 'Unknown';
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

    private function db()
    {
        return $this->activeDb ?: ee()->db;
    }

    private function resolveDatabaseConnection(?string $database)
    {
        ee()->load->database();

        if ($database === null || $database === '') {
            return ee()->db;
        }

        if (! preg_match('/^[a-zA-Z0-9_:-]+$/', $database)) {
            throw new \InvalidArgumentException('Invalid database connection name.');
        }

        $connection = ee()->load->database($database, true);
        if (! is_object($connection)) {
            throw new \RuntimeException("Database connection '{$database}' is not available.");
        }

        return $connection;
    }
}
