<?php

namespace ExpressionEngine\Addons\Mcp\ControlPanel\Routes;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

/**
 * MCP Control Panel Save Route
 *
 * Handles form submission for saving MCP component settings
 */
class Save extends AbstractRoute
{
    /**
     * Route path
     */
    protected $route_path = 'save';

    /**
     * Control panel page title
     */
    protected $cp_page_title = 'Save MCP Settings';

    /**
     * Exclude this route from the sidebar (it's a form handler, not a page)
     */
    protected $exclude_from_sidebar = true;

    /**
     * Process the route (handle form submission)
     *
     * @param  mixed  $id
     */
    public function process($id = false): AbstractRoute
    {
        // Only allow POST requests
        if (ee('Request')->method() !== 'POST') {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mcp')->compile());

            return $this;
        }

        // Get posted settings
        $postedSettings = ee('Request')->post('components');
        $globalVisibilityMode = ee('Request')->post('global_visibility_mode');

        if (! is_array($postedSettings)) {
            $postedSettings = [];
        }

        // Validate and set global visibility mode
        $settingsService = ee('mcp:SettingsService');
        // Always set visibility mode if provided (even if empty string, we'll validate)
        if ($globalVisibilityMode !== null) {
            // Validate visibility mode
            if (in_array($globalVisibilityMode, [
                \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_HIDDEN,
                \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_VISIBLE_DISABLED,
            ])) {
                $saveSuccess = $settingsService->setGlobalVisibilityMode($globalVisibilityMode);
                if (! $saveSuccess && function_exists('ee') && isset(ee()->logger)) {
                    ee()->logger->developer('[MCP Save] Failed to save global visibility mode: '.$globalVisibilityMode);
                }
            } elseif (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP Save] Invalid global visibility mode value: '.var_export($globalVisibilityMode, true));
            }
        }

        // Log what we received for debugging
        if (function_exists('ee') && isset(ee()->logger)) {
            ee()->logger->developer('[MCP Save] Received POST data with '.count($postedSettings).' addons');
        }

        // Get all components to handle unchecked checkboxes
        $componentDiscoveryService = ee('mcp:ComponentDiscoveryService');
        $allComponents = $componentDiscoveryService->discoverAllComponents();

        // Build a map of all components that should exist
        $allComponentKeys = [];
        foreach ($allComponents as $component) {
            $key = $component['addon'].'_'.$component['type'].'_'.$component['name'];
            $allComponentKeys[$key] = false; // Default to false (unchecked)
        }

        // Validate and process posted settings
        $settingsToUpdate = []; // Will store as array with enabled and visibility_mode
        foreach ($postedSettings as $addonName => $addonSettings) {
            if (! is_array($addonSettings)) {
                continue;
            }

            // Validate addon name (must be non-empty string)
            if (empty($addonName) || ! is_string($addonName)) {
                continue;
            }

            foreach ($addonSettings as $typeName => $typeSettings) {
                if (! is_array($typeSettings)) {
                    continue;
                }

                // Validate type name (must be non-empty string)
                if (empty($typeName) || ! is_string($typeName)) {
                    continue;
                }

                foreach ($typeSettings as $componentName => $enabled) {
                    // Validate component name (must be non-empty string)
                    if (empty($componentName) || ! is_string($componentName)) {
                        continue;
                    }

                    // Handle toggle values: 'y'/'n' (yes_no format) or boolean values
                    if ($enabled === 'y' || $enabled === 'Y' || $enabled === '1' || $enabled === 1 || $enabled === true) {
                        $enabled = true;
                    } elseif ($enabled === 'n' || $enabled === 'N' || $enabled === '0' || $enabled === 0 || $enabled === false) {
                        $enabled = false;
                    } else {
                        // Skip invalid values
                        continue;
                    }

                    // Build key and validate it exists in known components
                    $key = $addonName.'_'.$typeName.'_'.$componentName;
                    // Only update if this is a valid component key
                    if (isset($allComponentKeys[$key])) {
                        // Use global visibility mode for all disabled components
                        // Enabled components don't need visibility_mode set
                        if (! $enabled) {
                            $settingsToUpdate[$key] = [
                                'enabled' => false,
                                'visibility_mode' => $globalVisibilityMode ?: \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_HIDDEN,
                            ];
                        } else {
                            // For enabled components, just set enabled (visibility_mode doesn't matter)
                            $settingsToUpdate[$key] = [
                                'enabled' => true,
                            ];
                        }
                    }
                }
            }
        }

        // Handle components that weren't in POST (unchecked checkboxes) - use global visibility mode
        foreach ($allComponentKeys as $key => $value) {
            if (! isset($settingsToUpdate[$key])) {
                // Use global visibility mode for unchecked (disabled) components
                $settingsToUpdate[$key] = [
                    'enabled' => false,
                    'visibility_mode' => $globalVisibilityMode ?: \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_HIDDEN,
                ];
            }
        }

        // Bulk update settings (settingsService already initialized above)

        // Ensure mcp_settings table exists (create if needed)
        $settingsTableName = ee()->db->dbprefix.'mcp_settings';
        $settingsTableCheck = ee()->db->query("SHOW TABLES LIKE '{$settingsTableName}'");
        $settingsTableExists = $settingsTableCheck->num_rows() > 0;

        if (! $settingsTableExists) {
            // Create the table
            ee()->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'settings_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                    'comment' => 'Settings key (e.g., "mcp_settings")',
                ],
                'settings_value' => [
                    'type' => 'TEXT',
                    'null' => false,
                    'comment' => 'JSON-encoded settings value',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            // Add primary key
            ee()->dbforge->add_key('id', true);

            // Add unique index on settings_key
            ee()->dbforge->add_key('settings_key', true);

            // Create the table
            ee()->dbforge->create_table('mcp_settings');

            // Add default timestamps
            ee()->db->query("ALTER TABLE `{$settingsTableName}` MODIFY `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            ee()->db->query("ALTER TABLE `{$settingsTableName}` MODIFY `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP Save] Created mcp_settings table');
            }
        }

        // Check if table exists and create it if it doesn't
        $tableName = ee()->db->dbprefix.'mcp_component_settings';
        $tableCheck = ee()->db->query("SHOW TABLES LIKE '{$tableName}'");
        $tableExists = $tableCheck->num_rows() > 0;

        if (! $tableExists) {
            // Create the table
            ee()->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'addon_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                    'comment' => 'The addon short name',
                ],
                'component_type' => [
                    'type' => 'ENUM',
                    'constraint' => '"tool","resource","prompt"',
                    'null' => false,
                    'comment' => 'Type of MCP component',
                ],
                'component_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                    'comment' => 'Name of the component (from attribute or generated)',
                ],
                'enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 1,
                    'null' => false,
                    'comment' => 'Whether this component is enabled (1 = enabled, 0 = disabled)',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            // Add primary key
            ee()->dbforge->add_key('id', true);

            // Add unique index on (addon_name, component_type, component_name)
            ee()->dbforge->add_key(['addon_name', 'component_type', 'component_name'], true);

            // Add indexes for common queries
            ee()->dbforge->add_key('addon_name');
            ee()->dbforge->add_key('component_type');
            ee()->dbforge->add_key('enabled');

            // Create the table
            ee()->dbforge->create_table('mcp_component_settings');

            // Add default timestamps
            ee()->db->query("ALTER TABLE `{$tableName}` MODIFY `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            ee()->db->query("ALTER TABLE `{$tableName}` MODIFY `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP Save] Created mcp_component_settings table');
            }
        }

        // Log what we're about to save for debugging
        if (function_exists('ee') && isset(ee()->logger)) {
            ee()->logger->developer('[MCP Save] About to save '.count($settingsToUpdate).' component settings');
        }

        $success = $settingsService->bulkUpdateComponentSettings($settingsToUpdate);

        // Set flash message and redirect
        if ($success) {
            try {
                $runtimeBridge = ee('mcp:RuntimeNotificationBridge');
                if ($runtimeBridge && method_exists($runtimeBridge, 'emitAllListChanged')) {
                    $runtimeBridge->emitAllListChanged();
                }
            } catch (\Throwable $e) {
                if (function_exists('ee') && isset(ee()->logger)) {
                    ee()->logger->developer('[MCP Save] Failed to queue list-changed notifications: '.$e->getMessage());
                }
            }

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('mcp_settings_saved'))
                ->defer();
        } else {
            // Log the failure for debugging
            if (function_exists('ee') && isset(ee()->logger)) {
                ee()->logger->developer('[MCP Save] Failed to save component settings. Settings count: '.count($settingsToUpdate));
            }

            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('mcp_settings_save_failed'))
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mcp')->compile());

        return $this;
    }
}
