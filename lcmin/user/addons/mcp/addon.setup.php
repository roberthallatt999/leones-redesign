<?php

return [
    'name' => 'Mcp',
    'description' => 'ExpressionEngine MCP Server',
    'version' => '1.0.0-beta.4',
    'author' => 'ExpressionEngine',
    'author_url' => 'https://mcp.expressionengine.com',
    'namespace' => 'ExpressionEngine\Addons\Mcp',
    'settings_exist' => true,
    'commands' => [
        'mcp:serve' => ExpressionEngine\Addons\Mcp\Commands\CommandServeCommand::class,
        'mcp:install' => ExpressionEngine\Addons\Mcp\Commands\CommandInstallCommand::class,
    ],
    'services' => [
        'McpServer' => function ($addon) {
            return new \ExpressionEngine\Addons\Mcp\Services\McpServer();
        },
        'ComponentDiscoveryService' => function ($addon) {
            return new \ExpressionEngine\Addons\Mcp\Services\ComponentDiscoveryService();
        },
        'SettingsService' => function ($addon) {
            return new \ExpressionEngine\Addons\Mcp\Services\SettingsService();
        },
        'PermissionEvaluatorService' => function ($addon) {
            return new \ExpressionEngine\Addons\Mcp\Services\PermissionEvaluatorService();
        },
        'RuntimeNotificationBridge' => function ($addon) {
            return new \ExpressionEngine\Addons\Mcp\Services\RuntimeNotificationBridge();
        },
    ],
    'models' => [
        'ComponentSetting' => 'Models\ComponentSetting',
    ],

    // // MCP element registry configuration
    // 'mcp' => [
    //     // Auto-discovery from addon root (relative dirs). Default: ['Mcp']
    //     'scan' => ['Mcp'],

    //     // Directories to exclude from discovery
    //     'exclude' => ['tests', 'vendor'],

    //     // Prefix for element names to avoid conflicts. Default: addon shortname
    //     // 'prefix' => 'mcp',

    //     // Explicit class lists (FQCN or array with overrides)
    //     'tools' => [
    //         ExpressionEngine\Addons\Mcp\Mcp\Tools\ClearCacheTool::class,
    //         ExpressionEngine\Addons\Mcp\Mcp\Tools\GetConfigTool::class,
    //     ],
    //     'resources' => [
    //         ExpressionEngine\Addons\Mcp\Mcp\Resources\SystemInfoResource::class,
    //     ],
    //     'prompts' => [],

    //     // Optional provider for conditional registration
    //     // 'provider' => null,

    //     // Optional toggle for the addon’s MCP block
    //     'enabled' => true,
    // ],

];
