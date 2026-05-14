<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Database Query Tool
 *
 * Execute a read-only SQL query against the configured database.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'database_query',
    description: 'Execute a read-only SQL query against the configured database.'
)]
class DatabaseQuery extends AbstractTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Execute a read-only SQL query against the configured database.';

    public function description(): string
    {
        return $this->description;
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'query' => $schema->string()
                ->description('The SQL query to execute. Only read-only queries are allowed (i.e. SELECT, SHOW, EXPLAIN, DESCRIBE).')
                ->required(),
            'database' => $schema->string()
                ->description('Optional database connection name to use. Defaults to the application\'s default connection.'),
        ], ['query'])->toArray();
    }

    public function handle(array $params): array
    {
        $query = trim($params['query'] ?? '');
        $database = isset($params['database']) ? trim((string) $params['database']) : null;

        if (empty($query)) {
            throw new \InvalidArgumentException('Please pass a valid query');
        }

        // Remove SQL comments to prevent bypass attempts
        $queryWithoutComments = preg_replace('/--.*$/m', '', $query);
        $queryWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $queryWithoutComments);
        $queryWithoutComments = trim($queryWithoutComments);

        // Check for multiple statements (semicolon-separated)
        if (preg_match('/;\s*(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE)/i', $queryWithoutComments)) {
            throw new \InvalidArgumentException('Multiple statements are not allowed. Only read-only queries are permitted.');
        }

        // Check for write operations in the query (case-insensitive)
        $writeKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE', 'REPLACE', 'GRANT', 'REVOKE'];
        foreach ($writeKeywords as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $queryWithoutComments)) {
                throw new \InvalidArgumentException('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
            }
        }

        $token = strtok(ltrim($queryWithoutComments), " \t\n\r");

        if (! $token) {
            throw new \InvalidArgumentException('Please pass a valid query');
        }

        $firstWord = strtoupper($token);

        // Allowed read-only commands.
        $allowList = [
            'SELECT',
            'SHOW',
            'EXPLAIN',
            'DESCRIBE',
            'DESC',
            'WITH',        // SELECT must follow Common-table expressions
            'VALUES',      // Returns literal values
            'TABLE',       // PostgresSQL shorthand for SELECT *
        ];

        $isReadOnly = in_array($firstWord, $allowList, true);

        // Additional validation for WITH … SELECT.
        if ($firstWord === 'WITH' && ! preg_match('/with\s+.*select\b/i', $queryWithoutComments)) {
            $isReadOnly = false;
        }

        if (! $isReadOnly) {
            throw new \InvalidArgumentException('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
        }

        try {
            $db = $this->resolveDatabaseConnection($database);

            // Enable exception mode for better error handling
            $originalExceptionMode = $db->db_exception ?? false;
            $db->db_exception = true;

            try {
                // Execute the query
                $result = $db->query($query);

                // Restore original exception mode
                $db->db_exception = $originalExceptionMode;

                // If result is false (shouldn't happen with db_exception=true, but safety check)
                if ($result === false) {
                    throw new \RuntimeException('Database query failed');
                }

                $rows = $result->result_array();

                // Return results in JSON format
                return $rows;

            } catch (\Exception $e) {
                // Restore original exception mode
                $db->db_exception = $originalExceptionMode;
                throw $e;
            }

        } catch (\Throwable $e) {
            throw new \RuntimeException('Query failed: '.$e->getMessage());
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
