<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;

/**
 * System Information Resource
 *
 * Provides comprehensive information about the ExpressionEngine installation,
 * PHP environment, and system configuration. This resource is useful for
 * debugging, system monitoring, and understanding the EE environment.
 */
#[EeCategory('developer')]
class SystemInfoResource extends AbstractResource
{
    public function __construct()
    {
        // error_log("[MCP] SystemInfoResource instantiated");
    }

    public function uri(): string
    {
        return 'ee://system/info';
    }

    public function name(): ?string
    {
        return 'system_info';
    }

    public function description(): ?string
    {
        return 'ExpressionEngine system information including version, PHP details, and configuration';
    }

    #[McpResource(uri: 'ee://system/info', name: 'system_info', description: 'ExpressionEngine system information including version, PHP details, and configuration')]
    public function fetch(array $params = []): mixed
    {
        // Gather comprehensive system information
        $info = [
            'expressionengine' => $this->getExpressionEngineInfo(),
            'php' => $this->getPhpInfo(),
            'server' => $this->getServerInfo(),
            'database' => $this->getDatabaseInfo(),
            'content' => $this->getContentInfo(),
            'addons' => $this->getAddonInfo(),
            'performance' => $this->getPerformanceInfo(),
        ];

        return $info;
    }

    /**
     * Get ExpressionEngine specific information
     */
    private function getExpressionEngineInfo(): array
    {
        return [
            'version' => APP_VER,
            'build' => APP_BUILD,
            'base_path' => SYSPATH,
            'site_url' => ee()->config->item('site_url'),
            'site_name' => ee()->config->item('site_name'),
            'index_page' => ee()->config->item('index_page'),
            'debug' => ee()->config->item('debug'),
            'is_system_on' => ee()->config->item('is_system_on'),
            'is_site_on' => ee()->config->item('is_site_on'),
            'new_version_check' => ee()->config->item('new_version_check'),
        ];
    }

    /**
     * Get PHP environment information
     */
    private function getPhpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'version_id' => PHP_VERSION_ID,
            'architecture' => PHP_INT_SIZE * 8 .'-bit',
            'os' => PHP_OS,
            'sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => ini_get('error_reporting'),
            'timezone' => date_default_timezone_get(),
            'extensions' => $this->getLoadedExtensions(),
        ];
    }

    /**
     * Get server/web server information
     */
    private function getServerInfo(): array
    {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'address' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'request_time' => date('c', $_SERVER['REQUEST_TIME'] ?? time()),
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? __FILE__,
        ];
    }

    /**
     * Get database information
     */
    private function getDatabaseInfo(): array
    {
        return [
            'platform' => ee()->db->platform(),
            'version' => ee()->db->version(),
            'hostname' => ee()->db->hostname,
            'database' => ee()->db->database,
            'dbprefix' => ee()->db->dbprefix,
            'char_set' => ee()->db->char_set,
            'dbcollat' => ee()->db->dbcollat,
            'port' => ee()->db->port,
            'persistent' => ee()->db->pconnect,
            'connection_status' => 'connected',
        ];
    }

    /**
     * Get content statistics
     *
     * Uses direct DB count queries to avoid loading EE's Model layer and exhausting memory.
     */
    private function getContentInfo(): array
    {
        $prefix = ee()->db->dbprefix;
        $entries = (int) ee()->db->count_all($prefix.'channel_entries');
        $channels = (int) ee()->db->count_all($prefix.'channels');
        $members = (int) ee()->db->count_all($prefix.'members');
        $sites = (int) ee()->db->count_all($prefix.'sites');

        return [
            'total_entries' => $entries,
            'total_channels' => $channels,
            'total_members' => $members,
            'total_sites' => $sites,
        ];
    }

    /**
     * Get addon information
     */
    private function getAddonInfo(): array
    {
        $addons = ee('Addon')->installed();

        return [
            'total_installed' => count($addons),
            'third_party' => array_map(function ($addon) {
                return [
                    'name' => $addon->getName(),
                    'shortname' => $addon->getPrefix(),
                    'version' => $addon->getVersion(),
                    'author' => $addon->getAuthor(),
                ];
            }, array_filter($addons, function ($addon) {
                return $addon->getAuthor() !== 'ExpressionEngine';
            })),
        ];
    }

    /**
     * Get performance and cache information
     */
    private function getPerformanceInfo(): array
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(),
                'peak' => memory_get_peak_usage(),
                'limit' => ini_get('memory_limit'),
            ],
            'execution_time' => [
                'current' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                'limit' => ini_get('max_execution_time'),
            ],
            'cache' => [
                'enabled' => ee()->config->item('cache_driver') !== 'dummy',
                'driver' => ee()->config->item('cache_driver'),
            ],
        ];
    }

    /**
     * Get list of loaded PHP extensions
     */
    private function getLoadedExtensions(): array
    {
        $extensions = get_loaded_extensions();

        // Sort alphabetically for better readability
        sort($extensions);

        return $extensions;
    }
}
