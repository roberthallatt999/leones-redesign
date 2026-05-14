<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Contracts\Provider as McpProvider;
use Mcp\Capability\Registry;
use Mcp\Capability\Registry\Container as BasicContainer;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;

class McpServer
{
    private ?Server $server = null;

    /**
     * Start the MCP server
     *
     * @param  int  $port  Port to run the server on
     * @param  string  $host  Host to bind to
     * @return void
     */
    public function start($port = 8080, $host = 'localhost')
    {
        // Prevent any PHP errors/warnings/fatals from being sent to stdout.
        // The MCP transport uses stdio; non-JSON output corrupts the protocol.
        @ini_set('display_errors', '0');
        @ini_set('html_errors', '0');
        if (function_exists('ini_set')) {
            @ini_set('log_errors', '1');
        }

        // EE's Model layer can use a lot of memory; raise limit to avoid fatal exhaustion.
        $current = ini_get('memory_limit');
        if ($current !== '-1') {
            $num = (int) $current;
            $unit = strtoupper(substr(trim($current), -1));
            $bytes = $unit === 'G' ? $num * 1024 * 1024 * 1024 : ($unit === 'M' ? $num * 1024 * 1024 : (int) $current);
            if ($bytes > 0 && $bytes < 256 * 1024 * 1024) {
                @ini_set('memory_limit', '256M');
            }
        }

        $this->log("Starting MCP server on {$host}:{$port}");

        // Load the addon autoloader for MCP SDK classes
        require_once PATH_THIRD.'mcp/vendor/autoload.php';

        try {
            // Keep stdio + EE CLI bootstrap behavior; only swap the underlying SDK.
            $runtimeBridge = ee('mcp:RuntimeNotificationBridge');
            if (! $runtimeBridge instanceof RuntimeNotificationBridge) {
                $runtimeBridge = new RuntimeNotificationBridge();
            }

            $eventDispatcher = new ListChangeEventDispatcher($runtimeBridge);
            $container = new BasicContainer();
            $registry = new Registry($eventDispatcher);
            $registrar = new Registrar($registry);
            $sessionStore = new FileSessionStore($runtimeBridge->getSessionDirectory(), 3600);
            $serverVersion = $this->resolveServerVersion();

            // Aggregate MCP elements from all installed addons before server build.
            $this->aggregateAddonMcpElements($registrar);

            $this->server = Server::builder()
                ->setServerInfo('ExpressionEngine MCP Server', $serverVersion)
                ->setContainer($container)
                ->setRegistry($registry)
                ->setEventDispatcher($eventDispatcher)
                ->setSession($sessionStore)
                ->setCapabilities(new ServerCapabilities(
                    toolsListChanged: true,
                    completions: true,
                    resourcesSubscribe: false,
                    resourcesListChanged: true,
                    promptsListChanged: true
                ))
                ->build();

            $this->log('MCP server initialized successfully');

            $transport = new StdioTransport();
            $this->log('Starting stdio transport');
            $this->server->run($transport);
            $this->log('Server listen completed');
        } catch (\Throwable $e) {
            $this->log('Error starting server: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Stop the MCP server
     *
     * @return void
     */
    public function stop()
    {
        $this->log('Stopping MCP server');
        if ($this->server) {
            // Server stopping logic if needed
        }
    }

    /**
     * Check if server is running
     *
     * @return bool
     */
    public function isRunning()
    {
        // Basic check - can be extended with actual process checking
        return $this->server !== null;
    }

    /**
     * Aggregate MCP elements from all installed addons
     *
     * @param  Registrar  $registrar  The registrar instance
     */
    private function aggregateAddonMcpElements(Registrar $registrar): void
    {
        // Ensure addon autoloaders are set up before discovery
        try {
            ee('App')->setupAddons(PATH_THIRD);
        } catch (\Throwable $e) {
            $this->log('Warning during addon setup: '.$e->getMessage());
        }

        $settingsService = new SettingsService();
        $discoveryService = new ComponentDiscoveryService();

        // Discover all components from all addons (both from addon.setup.php and direct directory scanning)
        $allComponents = $discoveryService->discoverAllComponents();
        $this->log('Discovered '.count($allComponents).' MCP components from all addons');

        // Register each component if it should be registered (enabled or visible but disabled)
        foreach ($allComponents as $component) {
            try {
                $addonName = $component['addon'];
                $type = $component['type'];
                $name = $component['name'];

                // Check if this component should be registered
                $shouldRegister = $settingsService->shouldRegisterElement(
                    $addonName,
                    $type,
                    $name,
                    $component['category'] ?? null,
                    $component['permissions'] ?? []
                );

                if (! $shouldRegister) {
                    $skipMsg = "Skipping disabled component: {$addonName}:{$type}:{$name}";
                    $this->log($skipMsg);
                    if (getenv('MCP_SERVER_DEBUG') === '1' || (defined('MCP_SERVER_DEBUG') && MCP_SERVER_DEBUG)) {
                        @error_log('[MCP Server] '.$skipMsg);
                    }

                    continue;
                }

                // Check if component should be visible but disabled
                $isVisibleButDisabled = $settingsService->shouldShowButDisable($addonName, $type, $name);

                // Register the component based on its type
                // If visible but disabled, wrap with error handler
                try {
                    $this->registerDiscoveredComponent($registrar, $component, $isVisibleButDisabled);

                    $statusMsg = $isVisibleButDisabled ? 'Registered (visible but disabled)' : 'Registered';
                    $this->log("{$statusMsg} component: {$addonName}:{$type}:{$name}");
                } catch (\Throwable $regError) {
                    throw $regError; // Re-throw to be caught by outer try-catch
                }

            } catch (\Throwable $e) {
                $errorMsg = "Error registering component {$component['addon']}:{$component['type']}:{$component['name']}: ".$e->getMessage();
                $this->log($errorMsg);
                if (getenv('MCP_SERVER_DEBUG') === '1' || (defined('MCP_SERVER_DEBUG') && MCP_SERVER_DEBUG)) {
                    @error_log('[MCP Server] '.$errorMsg);
                    @error_log('[MCP Server] Stack trace: '.$e->getTraceAsString());
                }
            }
        }

        // Also process provider-based registration for backward compatibility
        $this->processProviderRegistration($registrar, $settingsService);
    }

    /**
     * Register a component discovered by ComponentDiscoveryService
     *
     * @param  Registrar  $registrar  The registrar instance
     * @param  array  $component  Component data from discovery service
     * @param  bool  $wrapWithErrorHandler  If true, wrap handler to return disabled error
     */
    private function registerDiscoveredComponent(Registrar $registrar, array $component, bool $wrapWithErrorHandler = false): void
    {
        $className = $component['class'];
        $type = $component['type'];
        $name = $component['name'];
        $description = $component['description'] ?? '';
        $permissions = $component['permissions'] ?? [];

        // If component is disabled, append notice to description
        if ($wrapWithErrorHandler) {
            $disabledNotice = "\n\n⚠️ This ".$type.' has been disabled in the ExpressionEngine Control Panel settings.';
            $description = $description.$disabledNotice;
        }

        // Handle method-specific registration for components with specific methods
        if (isset($component['method'])) {
            // For components discovered via method attributes
            $methodName = $component['method'];

            switch ($type) {
                case 'tool':
                    $registrar->withTool([$className, $methodName], $name, $description, null, $wrapWithErrorHandler, $permissions);
                    break;
                case 'resource':
                    $uri = $component['uri'] ?? null;
                    $mimeType = $component['mimeType'] ?? null;
                    // Check if this is a template resource
                    if (! empty($component['isTemplate']) && $component['isTemplate']) {
                        $uriTemplate = $component['uri'] ?? null;
                        if ($uriTemplate) {
                            $registrar->withResourceTemplate([$className, $methodName], $uriTemplate, $mimeType, $name, $description, null, $wrapWithErrorHandler, $permissions);
                        }
                    } else {
                        $registrar->withResource([$className, $methodName], $uri, $mimeType, $name, $description, null, $wrapWithErrorHandler, $permissions);
                    }
                    break;
                case 'prompt':
                    $registrar->withPrompt([$className, $methodName], $name, $description, null, $wrapWithErrorHandler, $permissions);
                    break;
            }
        } else {
            // For components discovered via class attributes or explicit config
            switch ($type) {
                case 'tool':
                    $registrar->withTool($className, $name, $description, null, $wrapWithErrorHandler, $permissions);
                    break;
                case 'resource':
                    $uri = $component['uri'] ?? null;
                    $mimeType = $component['mimeType'] ?? null;
                    // Check if this is a template resource
                    if (! empty($component['isTemplate']) && $component['isTemplate']) {
                        $uriTemplate = $component['uri'] ?? null;
                        if ($uriTemplate) {
                            $registrar->withResourceTemplate($className, $uriTemplate, $mimeType, $name, $description, null, $wrapWithErrorHandler, $permissions);
                        }
                    } else {
                        $registrar->withResource($className, $uri, $mimeType, $name, $description, null, $wrapWithErrorHandler, $permissions);
                    }
                    break;
                case 'prompt':
                    $registrar->withPrompt($className, $name, $description, null, $wrapWithErrorHandler, $permissions);
                    break;
            }
        }
    }

    /**
     * Process provider-based registration for backward compatibility
     *
     * @param  Registrar  $registrar  The registrar instance
     * @param  SettingsService  $settingsService  The settings service
     */
    private function processProviderRegistration(Registrar $registrar, SettingsService $settingsService): void
    {
        // Get all installed addons for provider processing
        $installedAddons = ee('Addon')->installed();

        foreach ($installedAddons as $addon) {
            try {
                $addonPath = $addon->getPath();
                $setupFile = $addonPath.'/addon.setup.php';

                // Check if setup file exists
                if (! file_exists($setupFile)) {
                    continue;
                }

                // Load addon setup
                $setup = require $setupFile;

                // Check if addon has MCP configuration
                if (! isset($setup['mcp']) || ! is_array($setup['mcp'])) {
                    continue;
                }

                $mcpConfig = $setup['mcp'];

                // Check if MCP is enabled for this addon
                if (isset($mcpConfig['enabled']) && $mcpConfig['enabled'] === false) {
                    continue;
                }

                // Run provider if configured (for backward compatibility)
                if (isset($mcpConfig['provider']) && is_string($mcpConfig['provider'])) {
                    $this->runMcpProvider($registrar, $addonPath, $mcpConfig['provider']);
                }

            } catch (\Throwable $e) {
                $this->log("Error processing provider registration for addon {$addon->getPrefix()}: ".$e->getMessage());
            }
        }
    }

    /**
     * Register a single MCP element
     *
     * @param  Registrar  $registrar  The registrar instance
     * @param  AttributeReader  $attributeReader  The attribute reader
     * @param  SettingsService  $settingsService  The settings service
     * @param  string  $type  Element type (tool|resource|prompt)
     * @param  string|array  $config  Element configuration
     * @param  string  $prefix  Prefix for element names
     * @param  string  $addonName  The addon name
     */
    private function registerElement(
        Registrar $registrar,
        AttributeReader $attributeReader,
        SettingsService $settingsService,
        string $type,
        $config,
        string $prefix,
        string $addonName
    ): void {
        // Handle string class names
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        // Skip if no class specified
        if (! isset($config['class']) || ! is_string($config['class'])) {
            return;
        }

        $className = $config['class'];

        try {
            // Basic validation - class should exist
            if (! class_exists($className)) {
                $this->log("MCP {$type} class not found: {$className}");

                return;
            }

            // Read EE-specific attributes
            $metadata = $attributeReader->getElementMetadata($className);

            // Apply prefix to name if not explicitly set and auto_prefix is not disabled
            $name = $config['name'] ?? null;
            $autoPrefix = $config['auto_prefix'] ?? true; // Default to true

            if ($name === null) {
                // Try to read name from McpTool attribute for tools
                if ($type === 'tool') {
                    try {
                        $reflection = new \ReflectionClass($className);
                        $attributes = $reflection->getAttributes(\Mcp\Capability\Attribute\McpTool::class);
                        if (! empty($attributes)) {
                            $mcpToolAttr = $attributes[0]->newInstance();
                            $name = $mcpToolAttr->name ?? null;
                        }
                    } catch (\Throwable $e) {
                        // Ignore - will fall back to generated name
                    }
                }

                // If still no name, generate prefixed name from class name
                if ($name === null && $autoPrefix !== false) {
                    $name = $this->generatePrefixedName($className, $prefix, $type);
                } elseif ($name !== null && $autoPrefix !== false && $prefix) {
                    // Apply prefix to name from attribute if auto_prefix is enabled
                    $name = $prefix.'_'.$name;
                }
            }

            // Check if element should be enabled based on settings
            $elementNameForSettings = $name ?? $this->generatePrefixedName($className, $prefix, $type);
            $shouldEnable = $settingsService->shouldEnableElement(
                $addonName,
                $type,
                $elementNameForSettings,
                $metadata['category'],
                $metadata['permissions']
            );

            if (! $shouldEnable) {
                return;
            }

            // Register based on type
            switch ($type) {
                case 'tool':
                    // Get description from tool class if not provided in config
                    $description = $config['description'] ?? null;
                    if ($description === null && class_exists($className)) {
                        try {
                            // Try to read from McpTool attribute first
                            $reflection = new \ReflectionClass($className);
                            $mcpToolAttrs = $reflection->getAttributes(\Mcp\Capability\Attribute\McpTool::class);
                            if (! empty($mcpToolAttrs)) {
                                $mcpToolAttr = $mcpToolAttrs[0]->newInstance();
                                $description = $mcpToolAttr->description ?? null;
                            }

                            // If still no description and extends AbstractTool, get from instance
                            if ($description === null && $reflection->isSubclassOf(\ExpressionEngine\Addons\Mcp\Support\AbstractTool::class)) {
                                $instance = new $className();
                                if (method_exists($instance, 'description')) {
                                    $description = $instance->description();
                                }
                            }
                        } catch (\Throwable $e) {
                            // Ignore - description is optional
                        }
                    }

                    $registrar->withTool(
                        $className,
                        $name,
                        $description,
                        $config['annotations'] ?? null,
                        false,
                        $metadata['permissions']
                    );
                    break;

                case 'resource':
                    // For explicit registration, try to get URI from the class if not specified
                    $uri = $config['uri'] ?? null;
                    if ($uri === null && class_exists($className)) {
                        try {
                            $instance = new $className();
                            if (method_exists($instance, 'uri')) {
                                $uri = $instance->uri();
                            }
                        } catch (\Throwable $e) {
                            // URI is required for resources
                            return;
                        }
                    }

                    $mimeType = $config['mimeType'] ?? null;
                    if ($mimeType === null && class_exists($className)) {
                        try {
                            $instance = new $className();
                            if (method_exists($instance, 'mimeType')) {
                                $mimeType = $instance->mimeType();
                            }
                        } catch (\Throwable $e) {
                            // Ignore - mimeType is optional
                        }
                    }

                    $description = $config['description'] ?? null;
                    if ($description === null && class_exists($className)) {
                        try {
                            $instance = new $className();
                            if (method_exists($instance, 'description')) {
                                $description = $instance->description();
                            }
                        } catch (\Throwable $e) {
                            // Ignore - description is optional
                        }
                    }

                    $registrar->withResource(
                        $className,
                        $uri,
                        $mimeType,
                        $name,
                        $description,
                        $config['annotations'] ?? null,
                        false,
                        $metadata['permissions']
                    );
                    break;

                case 'prompt':
                    $registrar->withPrompt(
                        $className,
                        $name,
                        $config['description'] ?? null,
                        $config['annotations'] ?? null,
                        false,
                        $metadata['permissions']
                    );
                    break;
            }

        } catch (\Throwable $e) {
            $this->log("Error registering MCP {$type} {$className}: ".$e->getMessage());
        }
    }

    /**
     * Generate a prefixed name for an MCP element
     *
     * @param  string  $className  The class name
     * @param  string  $prefix  The prefix to apply
     * @param  string  $type  The element type
     * @return string The prefixed name
     */
    private function generatePrefixedName(string $className, string $prefix, string $type): string
    {
        // Extract class name from FQCN
        $shortClassName = basename(str_replace('\\', '/', $className));

        // Convert to snake_case and remove common suffixes
        $baseName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortClassName));
        $baseName = preg_replace('/_(tool|resource|prompt)$/i', '', $baseName);

        // Apply prefix
        return $prefix.'_'.$baseName;
    }

    /**
     * Run an addon's MCP provider
     *
     * @param  Registrar  $registrar  The registrar instance
     * @param  string  $addonPath  Path to the addon
     * @param  string  $providerClass  Provider class name
     */
    private function runMcpProvider(Registrar $registrar, string $addonPath, string $providerClass): void
    {
        try {
            // Validate provider class
            if (! class_exists($providerClass)) {
                $this->log("MCP provider class not found: {$providerClass}");

                return;
            }

            // Check if it implements the Provider interface
            if (! is_subclass_of($providerClass, McpProvider::class)) {
                $this->log("MCP provider class does not implement Provider interface: {$providerClass}");

                return;
            }

            // Instantiate and run provider
            $provider = new $providerClass();
            $provider->register($registrar);

            $this->log("Executed MCP provider: {$providerClass}");

        } catch (\Throwable $e) {
            $this->log("Error running MCP provider {$providerClass}: ".$e->getMessage());
        }
    }

    /**
     * Emit list changed notifications for tools/resources/prompts.
     *
     * Call this when MCP settings change to notify connected clients
     * that available MCP elements have changed.
     */
    public function emitListChanged(): void
    {
        $bridge = ee('mcp:RuntimeNotificationBridge');
        if (! $bridge instanceof RuntimeNotificationBridge) {
            $bridge = new RuntimeNotificationBridge();
        }

        $updatedSessions = $bridge->emitAllListChanged();
        $this->log("Queued list-changed notifications for {$updatedSessions} active session(s)");
    }

    /**
     * Get the settings service instance
     */
    public function getSettingsService(): SettingsService
    {
        return new SettingsService();
    }

    /**
     * Update settings and emit notifications if needed
     *
     * @param  array  $settings  New settings
     * @return bool Success
     */
    public function updateSettings(array $settings): bool
    {
        $settingsService = $this->getSettingsService();
        $oldSettings = $settingsService->getSettings();

        $success = $settingsService->saveSettings($settings);

        if ($success) {
            // Check if settings changes might affect available elements
            $newSettings = $settingsService->getSettings();
            if ($this->settingsAffectAvailability($oldSettings, $newSettings)) {
                $this->emitListChanged();
            }
        }

        return $success;
    }

    /**
     * Check if settings changes might affect element availability
     */
    private function settingsAffectAvailability(array $oldSettings, array $newSettings): bool
    {
        // Check global enabled flag
        if (($oldSettings['global']['enabled'] ?? true) !== ($newSettings['global']['enabled'] ?? true)) {
            return true;
        }

        // Check category enabled flags
        $oldCategories = $oldSettings['categories'] ?? [];
        $newCategories = $newSettings['categories'] ?? [];

        foreach ($oldCategories as $name => $oldCat) {
            $newCat = $newCategories[$name] ?? null;
            if (($oldCat['enabled'] ?? true) !== ($newCat['enabled'] ?? true)) {
                return true;
            }
        }

        foreach ($newCategories as $name => $newCat) {
            if (! isset($oldCategories[$name])) {
                // New category might enable/disable elements
                return true;
            }
        }

        // Check element-specific settings
        $oldElements = $oldSettings['elements'] ?? [];
        $newElements = $newSettings['elements'] ?? [];

        foreach ($oldElements as $key => $oldElem) {
            $newElem = $newElements[$key] ?? null;
            if (($oldElem['enabled'] ?? true) !== ($newElem['enabled'] ?? true)) {
                return true;
            }
        }

        // New element settings might change availability
        return count($oldElements) !== count($newElements);
    }

    private function resolveServerVersion(): string
    {
        $defaultVersion = '0.0.0';
        $setupPath = rtrim((string) PATH_THIRD, '/\\').'/mcp/addon.setup.php';

        if (! is_file($setupPath)) {
            return $defaultVersion;
        }

        try {
            $setup = require $setupPath;
            if (is_array($setup) && isset($setup['version']) && is_string($setup['version']) && trim($setup['version']) !== '') {
                return trim($setup['version']);
            }
        } catch (\Throwable $e) {
            $this->log('Failed to resolve server version from addon.setup.php: '.$e->getMessage());
        }

        return $defaultVersion;
    }

    /**
     * Log server messages
     *
     * In stdio mode, error_log() writes to stderr; Cursor shows that as [error].
     * Only write to stderr when MCP_SERVER_DEBUG=1 so the boot cycle stays quiet.
     *
     * @param  string  $message
     * @param  bool  $verbose  Use EE logger when true (if available)
     * @return void
     */
    private function log($message, $verbose = false)
    {
        $fullMessage = "[MCP Server] {$message}";
        if (getenv('MCP_SERVER_DEBUG') === '1' || (defined('MCP_SERVER_DEBUG') && MCP_SERVER_DEBUG)) {
            @error_log($fullMessage);
        }
        if ($verbose && function_exists('ee') && isset(ee()->logger)) {
            ee()->load->library('logger');
            ee()->logger->developer($fullMessage);
        }
    }
}
