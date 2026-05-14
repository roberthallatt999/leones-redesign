<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Backup Database Tool
 *
 * Provides functionality to backup the ExpressionEngine database.
 * This tool creates SQL backup files with configurable paths, filenames, and backup speed.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'backup_database',
    description: 'Backup the ExpressionEngine database to an SQL file'
)]
class BackupDatabaseTool extends AbstractTool
{
    public function description(): string
    {
        return 'Backup the ExpressionEngine database to an SQL file. Supports configurable paths, filenames, and backup speed (1-10, where lower is slower but safer for large databases).';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'relative_path' => $schema->string()
                ->description('Path relative to cache folder where backup is stored (e.g., "backups/2025")')
                ->default(null),
            'absolute_path' => $schema->string()
                ->description('Absolute directory path to store backup (overrides relative_path if both are provided)')
                ->default(null),
            'file_name' => $schema->string()
                ->description('Name of the SQL file to be saved (default: database_name_timestamp.sql)')
                ->default(null),
            'speed' => $schema->integer(1, 10)
                ->description('Backup speed from 1-10 (lower is slower but safer for large databases; default: 5)')
                ->default(5),
        ], [])->toArray();
    }

    public function isDestructive(): bool
    {
        return true;
    }

    public function handle(array $params): array
    {
        // Get parameters with defaults
        $relativePath = $params['relative_path'] ?? null;
        $absolutePath = $params['absolute_path'] ?? null;
        $fileName = $params['file_name'] ?? null;
        $speed = isset($params['speed']) ? (int) $params['speed'] : 5;

        // Validate speed
        if ($speed < 1 || $speed > 10) {
            throw new \InvalidArgumentException("Speed must be between 1 and 10, got: {$speed}");
        }

        // Validate that at least one path type is provided if both are null
        // Actually, both can be null - we'll use default PATH_CACHE

        try {
            // Generate default filename if not provided
            if (empty($fileName)) {
                $date = ee()->localize->format_date('%Y-%m-%d_%Hh%im%ss%T');
                $databaseName = ee()->db->database;
                $fileName = $databaseName.'_'.$date.'.sql';
            }

            // Determine backup path
            $path = PATH_CACHE;
            if (! empty($absolutePath)) {
                $path = $absolutePath;
            } elseif (! empty($relativePath)) {
                $path = PATH_CACHE.$relativePath;
            }

            // Normalize paths
            $path = reduce_double_slashes($path);
            $filePath = reduce_double_slashes($path.'/'.$fileName);

            // Ensure directory exists
            if (! ee('Filesystem')->exists($path)) {
                // Try to create the directory
                if (! mkdir($path, 0755, true)) {
                    throw new \RuntimeException("Could not create directory: {$path}");
                }
            }

            // Check if file already exists
            if (ee('Filesystem')->exists($filePath)) {
                throw new \RuntimeException("Backup file already exists: {$filePath}. Please choose a different filename or delete the existing file.");
            }

            // Calculate wait time based on speed (lower speed = longer wait between operations)
            // Speed 10 = 0ms wait, Speed 1 = 90000ms wait (90 seconds)
            $waitTime = (10 - $speed) * 10000;

            // Create backup service instance
            $backup = ee('Database/Backup', $filePath);

            // Start the backup process
            $backup->startFile();
            $backup->writeDropAndCreateStatements();

            // Backup table data conservatively (in chunks to avoid memory issues)
            $tableName = null;
            $offset = 0;
            $returned = true;
            $tablesBackedUp = 0;

            do {
                $returned = $backup->writeTableInsertsConservatively($tableName, $offset);

                if ($returned !== false) {
                    $tablesBackedUp++;
                    $tableName = $returned['table_name'];
                    $offset = $returned['offset'];

                    // Add wait time between operations to reduce database load
                    if ($waitTime > 0) {
                        usleep($waitTime);
                    }
                }
            } while ($returned !== false);

            // Finalize the backup file
            $backup->endFile();

            // Get file size
            $fileSize = filesize($filePath);
            $fileSizeFormatted = $this->formatBytes($fileSize);

            return [
                'success' => true,
                'message' => 'Database backup completed successfully',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'file_size_formatted' => $fileSizeFormatted,
                'speed' => $speed,
                'tables_backed_up' => $tablesBackedUp,
                'timestamp' => date('c'),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to backup database: '.$e->getMessage(),
                'error' => $e->getMessage(),
                'file_path' => $filePath ?? null,
                'timestamp' => date('c'),
            ];
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
