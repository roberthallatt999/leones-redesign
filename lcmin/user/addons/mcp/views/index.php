<?php
/**
 * MCP Components Settings View
 *
 * Displays and manages MCP component settings (tools, resources, prompts)
 * across all installed addons with modern ExpressionEngine styling.
 */

// Helper function to render toggle
function renderToggle($fieldName, $value, $disabled = false)
{
    $toggleView = ee('View')->make('ee:_shared/form/fields/toggle');

    return $toggleView->render([
        'field_name' => $fieldName,
        'value' => $value ? 'y' : 'n',
        'yes_no' => true,
        'disabled' => $disabled,
    ]);
}
?>
<style>
/* Ensure all toggles align to the right and are inline */
.mcp-components-view .fieldset {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

.mcp-components-view .field-instruct {
    flex: 1 !important;
    margin-right: 20px !important;
}

.mcp-components-view .field-control {
    flex-shrink: 0 !important;
    margin-left: auto !important;
}

.mcp-components-view .fieldset__extra {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 8px !important;
}

.mcp-components-view .mcp-components-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.mcp-components-view .mcp-components-table thead th {
    text-align: left;
    padding: 12px;
    border-bottom: 2px solid var(--ee-border-subtle);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--ee-text-secondary);
}

.mcp-components-view .mcp-components-table thead th:last-child {
    text-align: right;
    width: 80px;
}

.mcp-components-view .mcp-components-table tbody td {
    padding: 12px;
    border-bottom: 1px solid var(--ee-border-subtle);
    vertical-align: top;
}

.mcp-components-view .mcp-components-table tbody td:last-child {
    text-align: right;
    vertical-align: middle;
}

.mcp-components-view .global-visibility-mode {
    display: flex;
    align-items: center;
    gap: 12px;
}

.mcp-components-view .global-visibility-mode select {
    min-width: 200px;
}

.mcp-components-view .mcp-components-table tbody tr:hover {
    background-color: var(--ee-bg-subtle);
}

.mcp-components-view .component-name {
    font-weight: 600;
    color: var(--ee-text-default);
    margin-bottom: 4px;
    display: block;
}

.mcp-components-view .component-description {
    font-size: 13px;
    color: var(--ee-text-secondary);
    line-height: 1.4;
    margin-top: 4px;
    display: block;
}

.mcp-components-view .component-category {
    display: inline-block;
    margin-top: 6px;
    font-size: 11px;
    color: var(--ee-text-muted);
}

.mcp-components-view .fieldset-group[data-addon] {
    width: 100% !important;
    max-width: 100% !important;
}

.mcp-components-view .fieldset-group[data-addon] .fieldset {
    width: 100% !important;
    max-width: 100% !important;
}

.mcp-components-view .fieldset-group[data-addon] .fieldset__body {
    width: 100% !important;
    max-width: 100% !important;
}

.mcp-components-view .statistics-overview {
    background: var(--ee-bg-default);
    border: 1px solid var(--ee-border-subtle);
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.mcp-components-view .statistics-overview h4 {
    margin: 0 0 15px 0;
    color: var(--ee-text-default);
    font-size: 16px;
    font-weight: 600;
}

.mcp-components-view .statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.mcp-components-view .statistics-grid:last-child {
    margin-bottom: 0;
}

.mcp-components-view .stat-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mcp-components-view .stat-item-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--ee-text-secondary);
    font-weight: 600;
}

.mcp-components-view .stat-item-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--ee-text-default);
}

.mcp-components-view .stat-item-disabled {
    font-size: 14px;
    color: var(--ee-text-muted);
    margin-top: 2px;
}

.mcp-components-view .addon-statistics {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
    padding: 10px;
    background: var(--ee-bg-subtle);
    border-radius: 4px;
    font-size: 13px;
}

.mcp-components-view .addon-stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.mcp-components-view .addon-stat-item i {
    color: var(--ee-text-secondary);
}

.mcp-components-view .addon-stat-item strong {
    color: var(--ee-text-default);
    margin-right: 4px;
}

.mcp-components-view .addon-stat-item .disabled-count {
    color: var(--ee-text-muted);
}
</style>

<div class="panel mcp-components-view">
    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title">
                <span class="icon--settings" role="presentation"></span>
                <?= lang('mcp_components_title') ?>
            </h3>
            <div class="title-bar__extra-tools">
                <a class="button button--primary" href="<?= ee('CP/URL')->make('addons/settings/mcp')->compile() ?>">
                    <i class="fas fa-sync"></i>
                    <?= lang('refresh') ?>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <?php if (empty($grouped_components)) { ?>
            <div class="app-notice app-notice--inline app-notice--important">
                <div class="app-notice__tag">
                    <span class="app-notice__icon"></span>
                </div>
                <div class="app-notice__content">
                    <p><strong><?= lang('no_components_found') ?></strong></p>
                    <p><?= lang('no_components_found_desc') ?></p>
                </div>
            </div>
        <?php } else { ?>
            <!-- Display deferred alerts (success/error messages) -->
            <div class="app-notice-wrap"><?= ee('CP/Alert')->get('shared-form') ?></div>

            <div class="app-notice app-notice--inline">
                <div class="app-notice__content">
                    <p><?= lang('mcp_components_desc') ?></p>
                </div>
            </div>

            <!-- Statistics Overview -->
            <?php if (isset($statistics)) { ?>
                <div class="statistics-overview">
                    <h4>
                        <i class="fas fa-chart-bar" style="margin-right: 8px;"></i>
                        Overview
                    </h4>
                    <div class="statistics-grid">
                        <div class="stat-item">
                            <span class="stat-item-label">Tools</span>
                            <span class="stat-item-value"><?= number_format($statistics['totals']['tools']['total']) ?></span>
                            <?php if ($statistics['totals']['tools']['disabled'] > 0) { ?>
                                <span class="stat-item-disabled">
                                    <?= number_format($statistics['totals']['tools']['disabled']) ?> disabled
                                </span>
                            <?php } ?>
                        </div>
                        <div class="stat-item">
                            <span class="stat-item-label">Resources</span>
                            <span class="stat-item-value"><?= number_format($statistics['totals']['resources']['total']) ?></span>
                            <?php if ($statistics['totals']['resources']['disabled'] > 0) { ?>
                                <span class="stat-item-disabled">
                                    <?= number_format($statistics['totals']['resources']['disabled']) ?> disabled
                                </span>
                            <?php } ?>
                        </div>
                        <div class="stat-item">
                            <span class="stat-item-label">Prompts</span>
                            <span class="stat-item-value"><?= number_format($statistics['totals']['prompts']['total']) ?></span>
                            <?php if ($statistics['totals']['prompts']['disabled'] > 0) { ?>
                                <span class="stat-item-disabled">
                                    <?= number_format($statistics['totals']['prompts']['disabled']) ?> disabled
                                </span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?= form_open(ee('CP/URL')->make('addons/settings/mcp/save')->compile(), 'POST', ['csrf_token' => $csrf_token, 'class' => 'settings']) ?>

            <!-- Global Visibility Mode -->
            <div class="fieldset-group" style="margin-bottom: 30px;">
                <fieldset class="fieldset" style="display: flex; align-items: center; justify-content: space-between;">
                    <div class="field-instruct" style="flex: 1; margin-right: 20px;">
                        <label for="global_visibility_mode"><?= lang('visibility_mode') ?></label>
                        <em><?= lang('visibility_mode_desc') ?></em>
                    </div>
                    <div class="field-control global-visibility-mode" style="flex-shrink: 0;">
                        <select name="global_visibility_mode" id="global_visibility_mode">
                            <option value="<?= \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_HIDDEN ?>" 
                                    <?= $global_visibility_mode === \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_HIDDEN ? 'selected' : '' ?>>
                                <?= lang('visibility_hidden') ?> - <?= lang('visibility_hidden_desc') ?>
                            </option>
                            <option value="<?= \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_VISIBLE_DISABLED ?>" 
                                    <?= $global_visibility_mode === \ExpressionEngine\Addons\Mcp\Models\ComponentSetting::VISIBILITY_VISIBLE_DISABLED ? 'selected' : '' ?>>
                                <?= lang('visibility_visible_disabled') ?> - <?= lang('visibility_visible_disabled_desc') ?>
                            </option>
                        </select>
                    </div>
                </fieldset>
            </div>

            <!-- Global Actions -->
            <div class="fieldset-group" style="margin-bottom: 30px;">
                <fieldset class="fieldset" style="display: flex; align-items: center; justify-content: space-between;">
                    <div class="field-instruct" style="flex: 1; margin-right: 20px;">
                        <label for="bulk_toggle_all"><?= lang('bulk_actions') ?></label>
                        <em><?= lang('bulk_actions_desc') ?></em>
                    </div>
                    <div class="field-control" style="flex-shrink: 0;">
                        <?php
                        $globalState = $toggle_states['global'] ?? false;
            // For mixed state (null), default to false but allow toggle
            $globalToggleValue = $globalState === null ? false : $globalState;
            ?>
                        <?= renderToggle('bulk_toggle_all', $globalToggleValue) ?>
                        <input type="hidden" id="bulk_toggle_all_state" value="<?= $globalState === null ? 'mixed' : ($globalState ? 'on' : 'off') ?>">
                    </div>
                </fieldset>
            </div>

            <!-- Components by Addon -->
            <?php foreach ($grouped_components as $addonName => $addonData) { ?>
                <div class="fieldset-group" style="margin-bottom: 40px;" data-addon="<?= htmlspecialchars($addonName) ?>">
                    <fieldset class="fieldset">

                        <div class="fieldset__body">
                            <?php
                $hasComponents = false;
                foreach (['tools', 'resources', 'prompts'] as $type) {
                    if (! empty($addonData[$type])) {
                        $hasComponents = true;
                        break;
                    }
                }

                if (! $hasComponents) { ?>
                                <div class="app-notice app-notice--inline">
                                    <div class="app-notice__content">
                                        <p><em><?= lang('no_components_in_addon') ?></em></p>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <!-- Addon Statistics -->
                                <?php if (isset($statistics['addons'][$addonName])) { ?>
                                    <?php
                        $addonStats = $statistics['addons'][$addonName];
                                    $hasStats = $addonStats['tools']['total'] > 0 || $addonStats['resources']['total'] > 0 || $addonStats['prompts']['total'] > 0;
                                    ?>
                                    <?php if ($hasStats) { ?>
                                        <div class="addon-statistics">
                                            <?php if ($addonStats['tools']['total'] > 0) { ?>
                                                <div class="addon-stat-item">
                                                    <i class="fas fa-wrench"></i>
                                                    <strong><?= number_format($addonStats['tools']['total']) ?></strong> tool<?= $addonStats['tools']['total'] !== 1 ? 's' : '' ?>
                                                    <?php if ($addonStats['tools']['disabled'] > 0) { ?>
                                                        <span class="disabled-count">(<?= number_format($addonStats['tools']['disabled']) ?> disabled)</span>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                            <?php if ($addonStats['resources']['total'] > 0) { ?>
                                                <div class="addon-stat-item">
                                                    <i class="fas fa-database"></i>
                                                    <strong><?= number_format($addonStats['resources']['total']) ?></strong> resource<?= $addonStats['resources']['total'] !== 1 ? 's' : '' ?>
                                                    <?php if ($addonStats['resources']['disabled'] > 0) { ?>
                                                        <span class="disabled-count">(<?= number_format($addonStats['resources']['disabled']) ?> disabled)</span>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                            <?php if ($addonStats['prompts']['total'] > 0) { ?>
                                                <div class="addon-stat-item">
                                                    <i class="fas fa-comment-dots"></i>
                                                    <strong><?= number_format($addonStats['prompts']['total']) ?></strong> prompt<?= $addonStats['prompts']['total'] !== 1 ? 's' : '' ?>
                                                    <?php if ($addonStats['prompts']['disabled'] > 0) { ?>
                                                        <span class="disabled-count">(<?= number_format($addonStats['prompts']['disabled']) ?> disabled)</span>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>

                                <!-- Addon name and enable toggle above the table -->
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--ee-border-subtle);">
                                    <h4 style="margin: 0; color: var(--ee-text-default);">
                                        <i class="fas fa-puzzle-piece" style="margin-right: 8px;"></i>
                                        <?= htmlspecialchars($addonName) ?> Components
                                    </h4>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <?php
                                        $addonState = $toggle_states['addons'][$addonName] ?? false;
                                // For mixed state (null), default to false but allow toggle
                                $addonToggleValue = $addonState === null ? false : $addonState;
                                $addonToggleId = 'addon_toggle_'.md5($addonName);
                                ?>
                                        <label for="<?= $addonToggleId ?>" style="margin: 0; font-size: 12px; color: var(--ee-text-secondary);">
                                            <?= lang('enable_addon') ?>
                                        </label>
                                        <?= renderToggle($addonToggleId, $addonToggleValue) ?>
                                        <input type="hidden" id="<?= $addonToggleId ?>_state" value="<?= $addonState === null ? 'mixed' : ($addonState ? 'on' : 'off') ?>" data-addon="<?= htmlspecialchars($addonName) ?>">
                                    </div>
                                </div>
                                <?php renderComponentsTables($addonName, $addonData, $component_settings); ?>
                            <?php } ?>
                        </div>
                    </fieldset>
                </div>
            <?php } ?>

            <!-- Save Button -->
            <div class="fieldset-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--ee-border-subtle);">
                <fieldset class="fieldset form-ctrls">
                    <div class="field-control">
                        <?= cp_form_submit('btn_save_settings', 'btn_saving') ?>
                    </div>
                </fieldset>
            </div>

            <?= form_close() ?>
        <?php } ?>
    </div>
</div>

<?php
/**
 * Render components in separate tables by type (tools, resources, prompts)
 */
function renderComponentsTables($addonName, $addonData, $componentSettings)
{
    $typeConfigs = [
        'tools' => [
            'icon' => 'fas fa-wrench',
            'label' => 'Tools',
            'lang_key' => 'tools',
        ],
        'resources' => [
            'icon' => 'fas fa-database',
            'label' => 'Resources',
            'lang_key' => 'resources',
        ],
        'prompts' => [
            'icon' => 'fas fa-comment-dots',
            'label' => 'Prompts',
            'lang_key' => 'prompts',
        ],
    ];

    // Render a separate table for each component type
    foreach ($typeConfigs as $type => $config) {
        if (! empty($addonData[$type])) {
            ?>
            <div style="margin-bottom: 30px;">
                <h5 style="margin: 0 0 15px 0; color: var(--ee-text-default); font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="<?= $config['icon'] ?>" style="color: var(--ee-text-secondary);"></i>
                    <?= htmlspecialchars($config['label']) ?>
                    <span style="font-weight: 400; color: var(--ee-text-muted); font-size: 12px;">
                        (<?= count($addonData[$type]) ?>)
                    </span>
                </h5>
                <table class="mcp-components-table">
                    <thead>
                        <tr>
                            <th><?= lang('name') ?></th>
                            <th><?= lang('description') ?></th>
                            <th><?= lang('category') ?></th>
                            <th><?= lang('enabled') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($addonData[$type] as $component) { ?>
                            <?php
                            $settingKey = $addonName.'_'.substr($type, 0, -1).'_'.$component['name'];
                            $rawSetting = $componentSettings[$settingKey] ?? null;
                            // Handle both old format (bool) and new format (array)
                            if (is_array($rawSetting)) {
                                $isEnabled = $rawSetting['enabled'] ?? true;
                            } else {
                                $isEnabled = $rawSetting ?? true;
                            }
                            $fieldName = 'components['.htmlspecialchars($addonName).']['.substr($type, 0, -1).']['.htmlspecialchars($component['name']).']';
                            ?>
                            <tr>
                                <td>
                                    <span class="component-name"><?= htmlspecialchars($component['name']) ?></span>
                                </td>
                                <td>
                                    <?php if (! empty($component['description'])) { ?>
                                        <span class="component-description"><?= htmlspecialchars($component['description']) ?></span>
                                    <?php } else { ?>
                                        <span class="component-description" style="color: var(--ee-text-muted); font-style: italic;">—</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (! empty($component['category'])) { ?>
                                        <span class="component-category">
                                            <i class="fas fa-tag" style="margin-right: 4px;"></i>
                                            <?= htmlspecialchars($component['category']) ?>
                                        </span>
                                    <?php } else { ?>
                                        <span style="color: var(--ee-text-muted); font-style: italic;">—</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?= renderToggle($fieldName, $isEnabled) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }
}
?>

