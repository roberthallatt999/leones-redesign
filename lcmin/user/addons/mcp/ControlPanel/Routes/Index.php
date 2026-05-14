<?php

namespace ExpressionEngine\Addons\Mcp\ControlPanel\Routes;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

/**
 * MCP Control Panel Index Route
 *
 * Displays and manages MCP component settings (tools, resources, prompts)
 * across all installed addons.
 */
class Index extends AbstractRoute
{
    /**
     * Route path
     */
    protected $route_path = 'index';

    /**
     * Control panel page title
     */
    protected $cp_page_title = 'MCP Components';

    /**
     * Sidebar title (used in sidebar navigation)
     */
    protected $sidebar_title = 'Components';

    /**
     * Sidebar icon
     */
    protected $sidebar_icon = 'settings';

    /**
     * Process the route (display the form)
     *
     * @param  mixed  $id
     */
    public function process($id = false): AbstractRoute
    {
        // Add breadcrumb
        $this->addBreadcrumb('index', 'MCP Components');

        // Get all discovered components
        $componentDiscoveryService = ee('mcp:ComponentDiscoveryService');
        $components = $componentDiscoveryService->discoverAllComponents();

        // Group components by addon, then by type
        $groupedComponents = $this->groupComponentsByAddonAndType($components);

        // Get current settings for each component
        $settingsService = ee('mcp:SettingsService');
        $componentSettings = $settingsService->getAllComponentSettings();
        $globalVisibilityMode = $settingsService->getGlobalVisibilityMode();

        // Calculate toggle states for bulk actions
        $toggleStates = $this->calculateToggleStates($groupedComponents, $componentSettings);

        // Calculate statistics for overview
        $statistics = $this->calculateStatistics($groupedComponents, $componentSettings);

        // Prepare data for the view
        $viewData = [
            'grouped_components' => $groupedComponents,
            'component_settings' => $componentSettings,
            'toggle_states' => $toggleStates,
            'statistics' => $statistics,
            'global_visibility_mode' => $globalVisibilityMode,
            'csrf_token' => CSRF_TOKEN,
        ];

        // Set the view body
        $this->setBody('index', $viewData);

        // Add JavaScript for toggle functionality
        $this->addToggleJavaScript();

        return $this;
    }

    /**
     * Group components by addon and type
     *
     * @param  array  $components  Array of component data
     * @return array Grouped components
     */
    private function groupComponentsByAddonAndType(array $components): array
    {
        $grouped = [];

        foreach ($components as $component) {
            $addon = $component['addon'];
            $type = $component['type'];

            if (! isset($grouped[$addon])) {
                $grouped[$addon] = [
                    'tools' => [],
                    'resources' => [],
                    'prompts' => [],
                ];
            }

            $grouped[$addon][$type.'s'][] = $component;
        }

        // Sort addons alphabetically
        ksort($grouped);

        // Sort components within each addon and type
        foreach ($grouped as $addon => &$addonData) {
            foreach ($addonData as $type => &$typeComponents) {
                usort($typeComponents, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            }
        }

        return $grouped;
    }

    /**
     * Calculate statistics for overview display
     *
     * @param  array  $groupedComponents  Grouped components
     * @param  array  $componentSettings  Current component settings
     * @return array Statistics with totals and per-addon counts
     */
    private function calculateStatistics(array $groupedComponents, array $componentSettings): array
    {
        $stats = [
            'totals' => [
                'tools' => ['total' => 0, 'disabled' => 0],
                'resources' => ['total' => 0, 'disabled' => 0],
                'prompts' => ['total' => 0, 'disabled' => 0],
            ],
            'addons' => [],
        ];

        foreach ($groupedComponents as $addonName => $addonData) {
            $addonStats = [
                'tools' => ['total' => 0, 'disabled' => 0],
                'resources' => ['total' => 0, 'disabled' => 0],
                'prompts' => ['total' => 0, 'disabled' => 0],
            ];

            foreach (['tools', 'resources', 'prompts'] as $type) {
                if (! empty($addonData[$type])) {
                    foreach ($addonData[$type] as $component) {
                        $settingKey = $addonName.'_'.substr($type, 0, -1).'_'.$component['name'];
                        $setting = $componentSettings[$settingKey] ?? null;

                        // Handle both old format (bool) and new format (array)
                        if (is_array($setting)) {
                            $isEnabled = $setting['enabled'] ?? true;
                        } else {
                            $isEnabled = $setting ?? true;
                        }

                        // Increment totals
                        $addonStats[$type]['total']++;
                        $stats['totals'][$type]['total']++;

                        // Increment disabled counts
                        if (! $isEnabled) {
                            $addonStats[$type]['disabled']++;
                            $stats['totals'][$type]['disabled']++;
                        }
                    }
                }
            }

            $stats['addons'][$addonName] = $addonStats;
        }

        return $stats;
    }

    /**
     * Calculate toggle states for bulk actions
     *
     * @param  array  $groupedComponents  Grouped components
     * @param  array  $componentSettings  Current component settings
     * @return array Toggle states ['global' => bool|null, 'addons' => [addon => bool|null]]
     */
    private function calculateToggleStates(array $groupedComponents, array $componentSettings): array
    {
        $states = [
            'global' => null, // null = mixed, true = all enabled, false = all disabled
            'addons' => [],
        ];

        $totalEnabled = 0;
        $totalComponents = 0;

        foreach ($groupedComponents as $addonName => $addonData) {
            $addonEnabled = 0;
            $addonTotal = 0;

            foreach (['tools', 'resources', 'prompts'] as $type) {
                if (! empty($addonData[$type])) {
                    foreach ($addonData[$type] as $component) {
                        $settingKey = $addonName.'_'.substr($type, 0, -1).'_'.$component['name'];
                        $setting = $componentSettings[$settingKey] ?? null;

                        // Handle both old format (bool) and new format (array)
                        if (is_array($setting)) {
                            $isEnabled = $setting['enabled'] ?? true;
                        } else {
                            $isEnabled = $setting ?? true;
                        }

                        $addonTotal++;
                        $totalComponents++;

                        if ($isEnabled) {
                            $addonEnabled++;
                            $totalEnabled++;
                        }
                    }
                }
            }

            // Calculate addon state: null = mixed, true = all enabled, false = all disabled
            if ($addonTotal > 0) {
                if ($addonEnabled === $addonTotal) {
                    $states['addons'][$addonName] = true;
                } elseif ($addonEnabled === 0) {
                    $states['addons'][$addonName] = false;
                } else {
                    $states['addons'][$addonName] = null; // mixed
                }
            }
        }

        // Calculate global state
        if ($totalComponents > 0) {
            if ($totalEnabled === $totalComponents) {
                $states['global'] = true;
            } elseif ($totalEnabled === 0) {
                $states['global'] = false;
            } else {
                $states['global'] = null; // mixed
            }
        }

        return $states;
    }

    /**
     * Add JavaScript for toggle functionality
     */
    private function addToggleJavaScript(): void
    {
        $javascript = '
<script>
(function($) {
    \'use strict\';

    $(document).ready(function() {
        // Global toggle handler
        $(document).on(\'click\', \'button.toggle-btn[data-toggle-for="bulk_toggle_all"]\', function(e) {
            e.stopPropagation();

            // Wait a bit for the toggle to update its state
            setTimeout(function() {
                const $bulkToggle = $(\'button.toggle-btn[data-toggle-for="bulk_toggle_all"]\');
                const shouldEnable = $bulkToggle.hasClass(\'on\');

                // Set all component hidden inputs to the desired state
                $(\'input[name^="components["][name$="]"]\').each(function() {
                    const $input = $(this);
                    $input.val(shouldEnable ? \'y\' : \'n\');

                    // Also update the toggle button appearance
                    const toggleFor = $input.attr(\'name\');
                    const $toggleBtn = $(\'button.toggle-btn[data-toggle-for="\' + toggleFor.replace(/"/g, \'\\\\"\') + \'"]\');
                    if ($toggleBtn.length) {
                        if (shouldEnable) {
                            $toggleBtn.removeClass(\'off\').addClass(\'on\');
                        } else {
                            $toggleBtn.removeClass(\'on\').addClass(\'off\');
                        }
                    }
                });

                // Update state tracking
                $(\'#bulk_toggle_all_state\').val(shouldEnable ? \'on\' : \'off\');

                // Update addon toggle states
                $(\'input[id^="addon_toggle_"][id$="_state"]\').each(function() {
                    const $stateInput = $(this);
                    const addonName = $stateInput.data(\'addon\');
                    if (addonName) {
                        updateAddonToggleState(addonName);
                    }
                });
            }, 100);
        });

        // Addon toggle handlers
        $(document).on(\'click\', \'button.toggle-btn[data-toggle-for^="addon_toggle_"]\', function(e) {
            e.stopPropagation();
            const $toggle = $(this);
            const toggleId = $toggle.attr(\'data-toggle-for\');
            const $stateInput = $(\'#\' + toggleId + \'_state\');
            const addonName = $stateInput.data(\'addon\');

            if (!addonName) return;

            // Wait for toggle to update
            setTimeout(function() {
                const shouldEnable = $toggle.hasClass(\'on\');

                // Set all component hidden inputs for this addon
                const addonSelector = \'[data-addon="\' + addonName.replace(/"/g, \'\\\\"\') + \'"]\';
                $(addonSelector + \' input[name^="components["][name$="]"]\').each(function() {
                    const $input = $(this);
                    $input.val(shouldEnable ? \'y\' : \'n\');

                    // Update toggle button appearance
                    const toggleFor = $input.attr(\'name\');
                    const $toggleBtn = $(addonSelector + \' button.toggle-btn[data-toggle-for="\' + toggleFor.replace(/"/g, \'\\\\"\') + \'"]\');
                    if ($toggleBtn.length) {
                        if (shouldEnable) {
                            $toggleBtn.removeClass(\'off\').addClass(\'on\');
                        } else {
                            $toggleBtn.removeClass(\'on\').addClass(\'off\');
                        }
                    }
                });

                // Update state
                $stateInput.val(shouldEnable ? \'on\' : \'off\');

                // Update bulk toggle state
                updateBulkToggleState();
            }, 100);
        });

        // Update bulk toggle state based on individual component states
        function updateBulkToggleState() {
            const $allInputs = $(\'input[name^="components["][name$="]"]\');
            if ($allInputs.length === 0) return;

            let enabledCount = 0;
            let totalCount = 0;

            $allInputs.each(function() {
                totalCount++;
                if ($(this).val() === \'y\') {
                    enabledCount++;
                }
            });

            const $stateInput = $(\'#bulk_toggle_all_state\');
            const $bulkToggle = $(\'button.toggle-btn[data-toggle-for="bulk_toggle_all"]\');

            if ($stateInput.length && totalCount > 0) {
                if (enabledCount === totalCount) {
                    $stateInput.val(\'on\');
                    if (!$bulkToggle.hasClass(\'on\')) {
                        $bulkToggle.removeClass(\'off\').addClass(\'on\');
                    }
                } else if (enabledCount === 0) {
                    $stateInput.val(\'off\');
                    if ($bulkToggle.hasClass(\'on\')) {
                        $bulkToggle.removeClass(\'on\').addClass(\'off\');
                    }
                } else {
                    $stateInput.val(\'mixed\');
                    // For mixed state, we could add a visual indicator, but for now just leave it
                }
            }
        }

        // Update addon toggle state based on individual component states
        function updateAddonToggleState(addonName) {
            const addonSelector = \'[data-addon="\' + addonName.replace(/"/g, \'\\\\"\') + \'"]\';
            const $addonInputs = $(addonSelector + \' input[name^="components["][name$="]"]\');
            let enabledCount = 0;
            let totalCount = 0;

            $addonInputs.each(function() {
                totalCount++;
                if ($(this).val() === \'y\') {
                    enabledCount++;
                }
            });

            // Find the state input for this addon
            const $stateInput = $(\'input[id^="addon_toggle_"][id$="_state"][data-addon="\' + addonName.replace(/"/g, \'\\\\"\') + \'"]\');
            if ($stateInput.length && totalCount > 0) {
                const toggleId = $stateInput.attr(\'id\').replace(\'_state\', \'\');
                const $addonToggle = $(\'button.toggle-btn[data-toggle-for="\' + toggleId + \'"]\');

                if ($addonToggle.length) {
                    if (enabledCount === totalCount) {
                        $stateInput.val(\'on\');
                        if (!$addonToggle.hasClass(\'on\')) {
                            $addonToggle.removeClass(\'off\').addClass(\'on\');
                        }
                    } else if (enabledCount === 0) {
                        $stateInput.val(\'off\');
                        if ($addonToggle.hasClass(\'on\')) {
                            $addonToggle.removeClass(\'on\').addClass(\'off\');
                        }
                    } else {
                        $stateInput.val(\'mixed\');
                        // For mixed state, could add visual indicator
                    }
                }
            }
        }

        // Listen for changes on individual component toggles
        $(document).on(\'click\', \'button.toggle-btn[data-toggle-for^="components["][data-toggle-for$="]"]\', function() {
            // Wait for the toggle to update its hidden input
            setTimeout(function() {
                const $fieldset = $(this).closest(\'[data-addon]\');
                if ($fieldset.length) {
                    const addonName = $fieldset.attr(\'data-addon\');
                    updateAddonToggleState(addonName);
                    updateBulkToggleState();
                }
            }, 100);
        });
    });
})(jQuery);
</script>';

        ee()->cp->add_to_foot($javascript);
    }

    /**
     * Get the control panel URL for this route
     */
    private function getCpUrl(): string
    {
        return ee('CP/URL')->make('addons/settings/mcp')->compile();
    }

    /**
     * Override prepareBodyVars to include breadcrumbs
     *
     * @return array
     */
    protected function prepareBodyVars(array $variables = [])
    {
        $vars = parent::prepareBodyVars($variables);
        $vars['cp_breadcrumbs'] = $this->getBreadcrumbs() ?: [];

        return $vars;
    }
}
