<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Models\ComponentSetting;

/**
 * MCP Settings Service
 *
 * Manages MCP element settings including enabled/disabled status,
 * categories, and permissions. Phase 1 uses JSON blob storage.
 */
class SettingsService
{
    private ?PermissionEvaluatorService $permissionEvaluator = null;

    /**
     * Settings key for MCP configuration
     */
    const SETTINGS_KEY = 'mcp_settings';

    /**
     * Default settings structure
     */
    const DEFAULT_SETTINGS = [
        'categories' => [
            'developer' => [
                'name' => 'Developer Tools',
                'description' => 'Tools for developers and system administrators',
                'enabled' => true,
                'permissions' => ['role:Developer', 'role:Super Admin'],
            ],
            'content' => [
                'name' => 'Content Tools',
                'description' => 'Tools for content creation and management',
                'enabled' => true,
                'permissions' => [], // No restrictions
            ],
        ],
        'elements' => [
            // Structure: addon_type_name => ['enabled' => bool, 'category' => string, 'permissions' => array]
        ],
        'global' => [
            'enabled' => true,
            'default_category' => 'developer',
            'visibility_mode' => \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_HIDDEN,
        ],
    ];

    /**
     * Get all MCP settings
     */
    public function getSettings(): array
    {
        try {
            $tableName = ee()->db->dbprefix.'mcp_settings';

            // Check if table exists
            $tableCheck = ee()->db->query("SHOW TABLES LIKE '{$tableName}'");
            $tableExists = $tableCheck->num_rows() > 0;

            if ($tableExists) {
                // Read from database table
                $query = ee()->db->where('settings_key', self::SETTINGS_KEY)
                    ->get('mcp_settings');

                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                    $stored = $row['settings_value'];
                } else {
                    return self::DEFAULT_SETTINGS;
                }
            } else {
                // Fallback to config if table doesn't exist yet
                $stored = ee()->config->item(self::SETTINGS_KEY);

                if ($stored === false || ! is_string($stored)) {
                    return self::DEFAULT_SETTINGS;
                }
            }

            $settings = json_decode($stored, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                ee()->logger->developer('[MCP Settings] Invalid JSON in settings, using defaults: '.json_last_error_msg());

                return self::DEFAULT_SETTINGS;
            }

            // Merge with defaults to ensure all keys exist
            // For 'global' key, use array_replace to properly replace nested scalar values
            $merged = self::DEFAULT_SETTINGS;

            // Handle 'global' key specially to ensure visibility_mode is properly replaced
            if (isset($settings['global']) && is_array($settings['global'])) {
                $merged['global'] = array_replace($merged['global'] ?? [], $settings['global']);
            }

            // Handle other top-level keys
            foreach ($settings as $key => $value) {
                if ($key === 'global') {
                    continue; // Already handled above
                }
                if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                    // For other arrays, merge recursively but replace scalar values
                    $merged[$key] = array_replace_recursive($merged[$key], $value);
                } else {
                    // Replace or add the value
                    $merged[$key] = $value;
                }
            }

            return $merged;
        } catch (\Throwable $e) {
            // Fallback to defaults on error
            return self::DEFAULT_SETTINGS;
        }
    }

    /**
     * Save MCP settings
     */
    public function saveSettings(array $settings): bool
    {
        $json = json_encode($settings, JSON_PRETTY_PRINT);

        if ($json === false) {
            ee()->logger->developer('[MCP Settings] Failed to encode settings to JSON');

            return false;
        }

        try {
            $tableName = ee()->db->dbprefix.'mcp_settings';

            // Check if table exists
            $tableCheck = ee()->db->query("SHOW TABLES LIKE '{$tableName}'");
            $tableExists = $tableCheck->num_rows() > 0;

            if ($tableExists) {
                // Save to database table
                $query = ee()->db->where('settings_key', self::SETTINGS_KEY)
                    ->get('mcp_settings');

                if ($query->num_rows() > 0) {
                    // Update existing row
                    ee()->db->where('settings_key', self::SETTINGS_KEY)
                        ->update('mcp_settings', [
                            'settings_value' => $json,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    // Insert new row
                    ee()->db->insert('mcp_settings', [
                        'settings_key' => self::SETTINGS_KEY,
                        'settings_value' => $json,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } else {
                // Fallback to config if table doesn't exist yet
                ee()->config->_update_config('mcp', [self::SETTINGS_KEY => $json]);
            }

            return true;
        } catch (\Throwable $e) {
            ee()->logger->developer('[MCP Settings] Error saving settings: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get element settings
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  The element type (tool|resource|prompt)
     * @param  string  $name  The element name
     * @return array|null Element settings or null if not configured
     */
    public function getElementSettings(string $addon, string $type, string $name): ?array
    {
        $settings = $this->getSettings();
        $key = "{$addon}_{$type}_{$name}";

        return $settings['elements'][$key] ?? null;
    }

    /**
     * Set element settings
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  The element type (tool|resource|prompt)
     * @param  string  $name  The element name
     * @param  array  $elementSettings  Element settings
     */
    public function setElementSettings(string $addon, string $type, string $name, array $elementSettings): bool
    {
        $settings = $this->getSettings();
        $key = "{$addon}_{$type}_{$name}";

        $settings['elements'][$key] = $elementSettings;

        return $this->saveSettings($settings);
    }

    /**
     * Get category settings
     *
     * @param  string  $category  The category name
     * @return array|null Category settings or null if not found
     */
    public function getCategorySettings(string $category): ?array
    {
        $settings = $this->getSettings();

        return $settings['categories'][$category] ?? null;
    }

    /**
     * Set category settings
     *
     * @param  string  $category  The category name
     * @param  array  $categorySettings  Category settings
     */
    public function setCategorySettings(string $category, array $categorySettings): bool
    {
        $settings = $this->getSettings();
        $settings['categories'][$category] = $categorySettings;

        return $this->saveSettings($settings);
    }

    /**
     * Check if an element should be enabled based on settings
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  The element type
     * @param  string  $name  The element name
     * @param  string|null  $category  The element's category
     * @param  array  $permissions  The element's permissions
     */
    public function shouldEnableElement(
        string $addon,
        string $type,
        string $name,
        ?string $category = null,
        array $permissions = []
    ): bool {
        // Check global MCP enablement
        $settings = $this->getSettings();
        if (($settings['global']['enabled'] ?? true) === false) {
            return false;
        }

        // Enforce EePermissions metadata before exposing elements.
        if (! $this->getPermissionEvaluator()->isAllowed($permissions)) {
            return false;
        }

        // First check database table for component-specific settings
        $dbEnabled = ComponentSetting::getEnabledStatus($addon, $type, $name);
        if ($dbEnabled !== null) {
            return $dbEnabled;
        }

        // Fall back to config-based element-specific settings
        $elementSettings = $this->getElementSettings($addon, $type, $name);
        if ($elementSettings !== null) {
            $result = $elementSettings['enabled'] ?? true;

            return $result;
        }

        // Check category settings
        if ($category !== null) {
            $categorySettings = $this->getCategorySettings($category);
            if ($categorySettings !== null) {
                $result = $categorySettings['enabled'] ?? true;

                return $result;
            }
        }

        // Default to enabled
        return true;
    }

    /**
     * Check if an element should be visible but disabled (returns error when called)
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  The element type
     * @param  string  $name  The element name
     * @return bool True if element should be visible but disabled
     */
    public function shouldShowButDisable(
        string $addon,
        string $type,
        string $name
    ): bool {
        // Check database table for component-specific settings
        $dbEnabled = ComponentSetting::getEnabledStatus($addon, $type, $name);

        // Get visibility mode (will fall back to global if not set per-component)
        $visibilityMode = ComponentSetting::getVisibilityMode($addon, $type, $name);

        // Only show but disable if:
        // 1. Component is disabled in database
        // 2. Visibility mode is 'visible_disabled' (from global or per-component)
        if ($dbEnabled === false && $visibilityMode === ComponentSetting::VISIBILITY_VISIBLE_DISABLED) {
            return true;
        }

        return false;
    }

    /**
     * Check if an element should be registered (either enabled or visible but disabled)
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  The element type
     * @param  string  $name  The element name
     * @param  string|null  $category  The element's category
     * @param  array  $permissions  The element's permissions
     * @return bool True if element should be registered
     */
    public function shouldRegisterElement(
        string $addon,
        string $type,
        string $name,
        ?string $category = null,
        array $permissions = []
    ): bool {
        if (! $this->getPermissionEvaluator()->isAllowed($permissions)) {
            return false;
        }

        // Register if enabled OR if visible but disabled
        return $this->shouldEnableElement($addon, $type, $name, $category, $permissions) ||
               $this->shouldShowButDisable($addon, $type, $name);
    }

    private function getPermissionEvaluator(): PermissionEvaluatorService
    {
        if ($this->permissionEvaluator instanceof PermissionEvaluatorService) {
            return $this->permissionEvaluator;
        }

        if (function_exists('ee')) {
            try {
                $service = ee('mcp:PermissionEvaluatorService');
                if ($service instanceof PermissionEvaluatorService) {
                    $this->permissionEvaluator = $service;

                    return $this->permissionEvaluator;
                }
            } catch (\Throwable $e) {
                // Fallback to local instance.
            }
        }

        $this->permissionEvaluator = new PermissionEvaluatorService();

        return $this->permissionEvaluator;
    }

    /**
     * Get all registered elements from settings
     *
     * @return array Array of element configurations
     */
    public function getRegisteredElements(): array
    {
        $settings = $this->getSettings();

        return $settings['elements'] ?? [];
    }

    /**
     * Get all categories
     *
     * @return array Array of category configurations
     */
    public function getCategories(): array
    {
        $settings = $this->getSettings();

        return $settings['categories'] ?? [];
    }

    /**
     * Add or update a category
     *
     * @param  string  $name  Category name
     * @param  string  $displayName  Display name
     * @param  string  $description  Description
     * @param  array  $permissions  Permission requirements
     */
    public function addCategory(string $name, string $displayName, string $description = '', array $permissions = []): bool
    {
        $settings = $this->getSettings();
        $settings['categories'][$name] = [
            'name' => $displayName,
            'description' => $description,
            'enabled' => true,
            'permissions' => $permissions,
        ];

        return $this->saveSettings($settings);
    }

    /**
     * Remove a category
     *
     * @param  string  $name  Category name
     */
    public function removeCategory(string $name): bool
    {
        $settings = $this->getSettings();

        if (! isset($settings['categories'][$name])) {
            return true; // Already removed
        }

        unset($settings['categories'][$name]);

        return $this->saveSettings($settings);
    }

    /**
     * Get component enabled status from database
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string  $name  Component name
     * @return bool|null True if enabled, false if disabled, null if not set
     */
    public function getComponentEnabled(string $addon, string $type, string $name): ?bool
    {
        return ComponentSetting::getEnabledStatus($addon, $type, $name);
    }

    /**
     * Set component enabled status in database
     *
     * @param  string  $addon  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string  $name  Component name
     * @param  bool  $enabled  Whether the component should be enabled
     * @return bool Success
     */
    public function setComponentEnabled(string $addon, string $type, string $name, bool $enabled): bool
    {
        return ComponentSetting::setEnabledStatus($addon, $type, $name, $enabled);
    }

    /**
     * Set all components for an addon to enabled/disabled
     *
     * @param  string  $addon  The addon name
     * @param  bool  $enabled  Whether components should be enabled
     * @return bool Success
     */
    public function setAddonEnabled(string $addon, bool $enabled): bool
    {
        return ComponentSetting::setAddonEnabled($addon, $enabled);
    }

    /**
     * Get all component settings from database
     *
     * @return array Array of settings keyed by 'addon_type_name' => bool
     */
    public function getAllComponentSettings(): array
    {
        return ComponentSetting::getAllSettings();
    }

    /**
     * Get component settings for a specific addon
     *
     * @param  string  $addon  The addon name
     * @return array Array of settings keyed by 'type_name' => bool
     */
    public function getAddonComponentSettings(string $addon): array
    {
        return ComponentSetting::getAddonSettings($addon);
    }

    /**
     * Bulk update component settings
     *
     * @param  array  $settings  Array of settings keyed by 'addon_type_name' => bool
     * @return bool Success
     */
    public function bulkUpdateComponentSettings(array $settings): bool
    {
        return ComponentSetting::bulkUpdateSettings($settings);
    }

    /**
     * Delete all component settings for an addon (e.g., when addon is uninstalled)
     *
     * @param  string  $addon  The addon name
     * @return bool Success
     */
    public function deleteAddonComponentSettings(string $addon): bool
    {
        return ComponentSetting::deleteAddonSettings($addon);
    }

    /**
     * Get global visibility mode setting
     *
     * @return string Visibility mode ('hidden' or 'visible_disabled')
     */
    public function getGlobalVisibilityMode(): string
    {
        $settings = $this->getSettings();
        $mode = $settings['global']['visibility_mode'] ?? ComponentSetting::VISIBILITY_HIDDEN;

        return $mode;
    }

    /**
     * Set global visibility mode setting
     *
     * @param  string  $visibilityMode  Visibility mode ('hidden' or 'visible_disabled')
     * @return bool Success
     */
    public function setGlobalVisibilityMode(string $visibilityMode): bool
    {
        // Validate visibility mode
        if (! in_array($visibilityMode, [
            ComponentSetting::VISIBILITY_HIDDEN,
            ComponentSetting::VISIBILITY_VISIBLE_DISABLED,
        ])) {
            return false;
        }

        $settings = $this->getSettings();
        // Ensure global key exists and is an array
        if (! isset($settings['global']) || ! is_array($settings['global'])) {
            $settings['global'] = [];
        }
        $settings['global']['visibility_mode'] = $visibilityMode;

        return $this->saveSettings($settings);
    }
}
