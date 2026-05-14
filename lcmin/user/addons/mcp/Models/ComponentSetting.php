<?php

namespace ExpressionEngine\Addons\Mcp\Models;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Model\Model;

/**
 * Component Setting Model
 *
 * Manages MCP component enable/disable settings in the database.
 * Each row represents the setting for one component (tool/resource/prompt)
 * from one addon.
 */
class ComponentSetting extends Model
{
    /**
     * @var string Table name
     */
    protected static $_table_name = 'mcp_component_settings';

    /**
     * @var string Primary key
     */
    protected static $_primary_key = 'id';

    /**
     * @var array Typed columns
     */
    protected static $_typed_columns = [
        'id' => 'int',
        'enabled' => 'int',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    /**
     * Visibility mode constants
     */
    const VISIBILITY_HIDDEN = 'hidden';

    const VISIBILITY_VISIBLE_DISABLED = 'visible_disabled';

    /**
     * Get enabled status for a specific component
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string  $name  Component name
     * @return bool True if enabled, false if disabled, null if not set
     */
    public static function getEnabledStatus(string $addon, string $type, string $name): ?bool
    {
        try {
            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            $query = ee()->db->where('addon_name', $addon)
                ->where('component_type', $type)
                ->where('component_name', $name)
                ->get($tableName);

            if ($query->num_rows() > 0) {
                $row = $query->row_array();

                return (bool) $row['enabled'];
            }

            return null;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP ComponentSetting] Error getting enabled status: '.$e->getMessage());
            }

            return null;
        }
    }

    /**
     * Get visibility mode for a specific component
     * Falls back to global visibility mode if not set per-component
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string  $name  Component name
     * @return string Visibility mode ('hidden' or 'visible_disabled')
     */
    public static function getVisibilityMode(string $addon, string $type, string $name): string
    {
        try {
            // If column doesn't exist, return global default
            if (! self::hasVisibilityModeColumn()) {
                return self::getGlobalVisibilityMode();
            }

            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            $query = ee()->db->where('addon_name', $addon)
                ->where('component_type', $type)
                ->where('component_name', $name)
                ->get($tableName);

            if ($query->num_rows() > 0) {
                $row = $query->row_array();
                // If visibility_mode is set per-component, use it
                if (isset($row['visibility_mode']) && $row['visibility_mode'] !== null) {
                    return $row['visibility_mode'];
                }
            }

            // Fall back to global visibility mode
            return self::getGlobalVisibilityMode();
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP ComponentSetting] Error getting visibility mode: '.$e->getMessage());
            }

            return self::getGlobalVisibilityMode();
        }
    }

    /**
     * Get global visibility mode from settings
     *
     * @return string Visibility mode ('hidden' or 'visible_disabled')
     */
    private static function getGlobalVisibilityMode(): string
    {
        try {
            if (function_exists('ee')) {
                $settingsService = ee('mcp:SettingsService');
                if ($settingsService) {
                    return $settingsService->getGlobalVisibilityMode();
                }
            }
        } catch (\Throwable $e) {
            // Fall through to default
        }

        return self::VISIBILITY_HIDDEN;
    }

    /**
     * Set visibility mode for a specific component
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string  $name  Component name
     * @param  string  $visibilityMode  Visibility mode ('hidden' or 'visible_disabled')
     * @return bool Success
     */
    public static function setVisibilityMode(string $addon, string $type, string $name, string $visibilityMode): bool
    {
        try {
            // If column doesn't exist, return false (can't set visibility mode)
            if (! self::hasVisibilityModeColumn()) {
                return false;
            }

            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            // Validate visibility mode
            if (! in_array($visibilityMode, [self::VISIBILITY_HIDDEN, self::VISIBILITY_VISIBLE_DISABLED])) {
                return false;
            }

            // Check if record exists
            $query = ee()->db->where('addon_name', $addon)
                ->where('component_type', $type)
                ->where('component_name', $name)
                ->get($tableName);

            $data = [
                'visibility_mode' => $visibilityMode,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($query->num_rows() > 0) {
                // Update existing
                ee()->db->where('addon_name', $addon)
                    ->where('component_type', $type)
                    ->where('component_name', $name)
                    ->update($tableName, $data);
            } else {
                // Insert new with default enabled=true
                $data['addon_name'] = $addon;
                $data['component_type'] = $type;
                $data['component_name'] = $name;
                $data['enabled'] = 1;
                $data['created_at'] = date('Y-m-d H:i:s');
                ee()->db->insert($tableName, $data);
            }

            return true;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer("[MCP ComponentSetting] Error setting visibility mode for {$addon}:{$type}:{$name}: ".$e->getMessage());
            }

            return false;
        }
    }

    /**
     * Check if the visibility_mode column exists in the table
     */
    private static function hasVisibilityModeColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            try {
                $tableName = ee()->db->dbprefix.'mcp_component_settings';
                $query = ee()->db->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'visibility_mode'");
                $hasColumn = $query->num_rows() > 0;
            } catch (\Throwable $e) {
                $hasColumn = false;
            }
        }

        return $hasColumn;
    }

    /**
     * Set enabled status for a specific component
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string  $name  Component name
     * @param  bool  $enabled  Whether the component should be enabled
     * @param  string|null  $visibilityMode  Visibility mode ('hidden' or 'visible_disabled'), null to keep existing
     * @return bool Success
     */
    public static function setEnabledStatus(string $addon, string $type, string $name, bool $enabled, ?string $visibilityMode = null): bool
    {
        try {
            $tableName = ee()->db->dbprefix.'mcp_component_settings';
            $hasVisibilityColumn = self::hasVisibilityModeColumn();

            // Check if record exists
            $query = ee()->db->where('addon_name', $addon)
                ->where('component_type', $type)
                ->where('component_name', $name)
                ->get($tableName);

            $data = [
                'addon_name' => $addon,
                'component_type' => $type,
                'component_name' => $name,
                'enabled' => $enabled ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Only include visibility_mode if column exists and value is provided
            if ($hasVisibilityColumn && $visibilityMode !== null) {
                $data['visibility_mode'] = $visibilityMode;
            }

            if ($query->num_rows() > 0) {
                // Update existing - if visibility_mode not provided, keep existing value
                if (! $hasVisibilityColumn || $visibilityMode === null) {
                    // Don't update visibility_mode
                    unset($data['visibility_mode']);
                }
                ee()->db->where('addon_name', $addon)
                    ->where('component_type', $type)
                    ->where('component_name', $name)
                    ->update($tableName, $data);
            } else {
                // Insert new - use default visibility_mode if column exists and not provided
                if ($hasVisibilityColumn && $visibilityMode === null) {
                    $data['visibility_mode'] = self::VISIBILITY_HIDDEN;
                }
                $data['created_at'] = date('Y-m-d H:i:s');
                ee()->db->insert($tableName, $data);
            }

            return true;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer("[MCP ComponentSetting] Error setting enabled status for {$addon}:{$type}:{$name}: ".$e->getMessage());
            }

            return false;
        }
    }

    /**
     * Bulk set enabled status for all components of an addon
     *
     * @param  string  $addon  The addon name
     * @param  bool  $enabled  Whether components should be enabled
     * @return bool Success
     */
    public static function setAddonEnabled(string $addon, bool $enabled): bool
    {
        try {
            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            ee()->db->where('addon_name', $addon)
                ->update($tableName, [
                    'enabled' => $enabled ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return true;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer("[MCP ComponentSetting] Error bulk setting enabled status for addon {$addon}: ".$e->getMessage());
            }

            return false;
        }
    }

    /**
     * Get all settings as an array keyed by addon_type_name
     *
     * @return array Array of settings keyed by 'addon_type_name' => ['enabled' => bool, 'visibility_mode' => string]
     */
    public static function getAllSettings(): array
    {
        try {
            $settings = [];
            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            $query = ee()->db->get($tableName);

            $hasVisibilityColumn = self::hasVisibilityModeColumn();
            foreach ($query->result_array() as $row) {
                $key = $row['addon_name'].'_'.$row['component_type'].'_'.$row['component_name'];
                $setting = [
                    'enabled' => (bool) $row['enabled'],
                ];
                if ($hasVisibilityColumn) {
                    $setting['visibility_mode'] = $row['visibility_mode'] ?? self::VISIBILITY_HIDDEN;
                }
                $settings[$key] = $setting;
            }

            return $settings;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP ComponentSetting] Error getting all settings: '.$e->getMessage());
            }

            return [];
        }
    }

    /**
     * Get all settings for a specific addon
     *
     * @param  string  $addon  The addon name
     * @return array Array of settings keyed by 'type_name' => ['enabled' => bool, 'visibility_mode' => string]
     */
    public static function getAddonSettings(string $addon): array
    {
        try {
            $settings = [];
            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            $query = ee()->db->where('addon_name', $addon)
                ->get($tableName);

            $hasVisibilityColumn = self::hasVisibilityModeColumn();
            foreach ($query->result_array() as $row) {
                $key = $row['component_type'].'_'.$row['component_name'];
                $setting = [
                    'enabled' => (bool) $row['enabled'],
                ];
                if ($hasVisibilityColumn) {
                    $setting['visibility_mode'] = $row['visibility_mode'] ?? self::VISIBILITY_HIDDEN;
                }
                $settings[$key] = $setting;
            }

            return $settings;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer("[MCP ComponentSetting] Error getting addon settings for {$addon}: ".$e->getMessage());
            }

            return [];
        }
    }

    /**
     * Delete all settings for a specific addon
     *
     * @param  string  $addon  The addon name
     * @return bool Success
     */
    public static function deleteAddonSettings(string $addon): bool
    {
        try {
            $tableName = ee()->db->dbprefix.'mcp_component_settings';

            ee()->db->where('addon_name', $addon)
                ->delete($tableName);

            return true;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer("[MCP ComponentSetting] Error deleting addon settings for {$addon}: ".$e->getMessage());
            }

            return false;
        }
    }

    /**
     * Bulk update settings from an array
     *
     * @param  array  $settings  Array of settings keyed by 'addon_type_name' => bool|array
     *                           If array, can contain 'enabled' and optionally 'visibility_mode'
     * @return bool Success
     */
    public static function bulkUpdateSettings(array $settings): bool
    {
        try {
            $success = true;

            // Known component types (singular forms)
            $knownTypes = ['tool', 'resource', 'prompt'];

            foreach ($settings as $key => $value) {
                // Parse key: addon_type_name
                // Since addon names can contain underscores, we need to parse from the end
                // Look for known component types and split there
                $addon = null;
                $type = null;
                $name = null;

                foreach ($knownTypes as $knownType) {
                    $typePattern = '_'.$knownType.'_';
                    $lastPos = strrpos($key, $typePattern);
                    if ($lastPos !== false) {
                        $addon = substr($key, 0, $lastPos);
                        $type = $knownType;
                        $name = substr($key, $lastPos + strlen($typePattern));
                        break;
                    }
                }

                // If we didn't find a known type, skip this key
                if ($addon === null || $type === null || $name === null || empty($addon) || empty($name)) {
                    continue; // Skip invalid keys
                }

                // Handle both old format (bool) and new format (array)
                if (is_array($value)) {
                    $enabled = $value['enabled'] ?? true;
                    $visibilityMode = $value['visibility_mode'] ?? null;
                    if (! static::setEnabledStatus($addon, $type, $name, (bool) $enabled, $visibilityMode)) {
                        $success = false;
                    }
                } else {
                    // Legacy format: just boolean enabled status
                    if (! static::setEnabledStatus($addon, $type, $name, (bool) $value)) {
                        $success = false;
                    }
                }
            }

            return $success;
        } catch (\Throwable $e) {
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP ComponentSetting] Error bulk updating settings: '.$e->getMessage());
            }

            return false;
        }
    }
}
