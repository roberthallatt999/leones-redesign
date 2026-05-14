<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Addons\Mcp\Support\AbstractPrompt;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use ReflectionClass;

/**
 * Component Discovery Service
 *
 * Discovers MCP components (tools, resources, prompts) from all installed addons
 * without registering them to a server instance. This service is used by both
 * the MCP server (for registration) and the control panel (for display).
 *
 * Based on MCP PHP SDK discovery patterns:
 * - Uses attribute-based discovery (#[McpTool], #[McpResource], #[McpPrompt])
 * - Supports explicit class lists from addon.setup.php
 * - Extracts metadata via PHP reflection
 */
class ComponentDiscoveryService
{
    private const DISCOVERY_CACHE_TTL_SECONDS = 120;

    private const DISCOVERY_CACHE_DIR = 'mcp';

    private const DISCOVERY_CACHE_FILE = 'component-discovery-cache.json';

    /**
     * In-process cache keyed by discovery signature.
     *
     * @var array<string, array>
     */
    private static array $runtimeDiscoveryCache = [];

    /**
     * @var AttributeReader
     */
    private $attributeReader;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attributeReader = new AttributeReader();
    }

    /**
     * Discover MCP components by scanning directories directly
     *
     * This method scans the specified directories for MCP components without requiring
     * addon.setup.php files, similar to the MCP SDK discover() workflow.
     *
     * @param  string  $basePath  Base directory to scan from
     * @param  array  $scanDirs  Directories to scan (relative to basePath)
     * @param  array  $excludeDirs  Directories to exclude from scanning
     * @param  string|null  $addonName  Optional addon name to use as prefix
     * @return array Array of component data
     */
    public function discover(string $basePath, array $scanDirs = [], array $excludeDirs = [], ?string $addonName = null): array
    {
        $components = [];
        $seenComponents = []; // Track seen components to prevent duplicates

        // Default scan directories if none specified
        if (empty($scanDirs)) {
            $scanDirs = ['src', 'lib', 'Mcp'];
        }

        // Default exclude directories
        if (empty($excludeDirs)) {
            $excludeDirs = ['tests', 'vendor', 'node_modules'];
        }

        // If no addon name provided, use the base directory name
        if ($addonName === null) {
            $addonName = basename($basePath);
        }

        // Get all PHP files from the specified directories
        $phpFiles = $this->getPhpFilesFromDirectories($basePath, $scanDirs, $excludeDirs);

        foreach ($phpFiles as $filePath) {
            try {
                $classes = $this->getClassesFromFile($filePath);

                foreach ($classes as $className) {
                    // Discover tools
                    $toolComponents = $this->discoverToolsFromClass($addonName, $className, $addonName);
                    foreach ($toolComponents as $component) {
                        $key = $this->getComponentKey($component);
                        if (! isset($seenComponents[$key])) {
                            $seenComponents[$key] = true;
                            $components[] = $component;
                        }
                    }

                    // Discover resources
                    $resourceComponents = $this->discoverResourcesFromClass($addonName, $className, $addonName);
                    foreach ($resourceComponents as $component) {
                        $key = $this->getComponentKey($component);
                        if (! isset($seenComponents[$key])) {
                            $seenComponents[$key] = true;
                            $components[] = $component;
                        }
                    }

                    // Discover prompts
                    $promptComponents = $this->discoverPromptsFromClass($addonName, $className, $addonName);
                    foreach ($promptComponents as $component) {
                        $key = $this->getComponentKey($component);
                        if (! isset($seenComponents[$key])) {
                            $seenComponents[$key] = true;
                            $components[] = $component;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Skip files that can't be parsed
                continue;
            }
        }

        return $components;
    }

    /**
     * Discover all MCP components from all installed addons
     *
     * @return array Array of component data, each with:
     *               ['addon' => string, 'type' => 'tool'|'resource'|'prompt', 'name' => string,
     *               'description' => string, 'class' => string, 'category' => string|null,
     *               'permissions' => array, 'uri' => string|null (for resources)]
     */
    public function discoverAllComponents(): array
    {
        if (! $this->shouldUseDiscoveryCache()) {
            return $this->discoverAllComponentsWithoutCache();
        }

        $signature = $this->buildDiscoveryCacheSignature();
        if (isset(self::$runtimeDiscoveryCache[$signature])) {
            return self::$runtimeDiscoveryCache[$signature];
        }

        $cachedComponents = $this->readPersistentDiscoveryCache($signature);
        if ($cachedComponents !== null) {
            self::$runtimeDiscoveryCache[$signature] = $cachedComponents;

            return $cachedComponents;
        }

        $components = $this->discoverAllComponentsWithoutCache();
        self::$runtimeDiscoveryCache[$signature] = $components;
        $this->writePersistentDiscoveryCache($signature, $components);

        return $components;
    }

    /**
     * Discover all MCP components from all installed addons without using cache.
     */
    private function discoverAllComponentsWithoutCache(): array
    {
        $components = [];
        $seenComponents = []; // Track seen components to prevent duplicates
        $processedAddons = []; // Track processed addons to prevent duplicate addon processing

        // Get all installed addons (this includes the MCP addon itself)
        $installedAddons = ee('Addon')->installed();

        // Process each installed addon
        foreach ($installedAddons as $addon) {
            try {
                $addonPath = $addon->getPath();
                $addonName = $addon->getPrefix();

                // Skip if we've already processed this addon
                if (isset($processedAddons[$addonName])) {
                    continue;
                }
                $processedAddons[$addonName] = true;

                // Discover components from addon.setup.php and directory scanning
                $allAddonComponents = $this->discoverAddonComponents($addonName, $addonPath);

                // Deduplicate components before adding
                foreach ($allAddonComponents as $component) {
                    $key = $this->getComponentKey($component);
                    if (! isset($seenComponents[$key])) {
                        $seenComponents[$key] = true;
                        $components[] = $component;
                    }
                }
            } catch (\Throwable $e) {
                // Log but continue processing other addons
                if (function_exists('ee') && isset(ee()->logger)) {
                    ee()->logger->developer("[MCP Discovery] Error discovering components for addon {$addon->getPrefix()}: ".$e->getMessage());
                }
            }
        }

        return $components;
    }

    /**
     * Clear in-memory and file discovery cache.
     */
    public function clearDiscoveryCache(): void
    {
        self::$runtimeDiscoveryCache = [];

        $cachePath = $this->getPersistentCachePath();
        if ($cachePath !== null && is_file($cachePath)) {
            @unlink($cachePath);
        }
    }

    private function buildDiscoveryCacheSignature(): string
    {
        try {
            $installedAddons = ee('Addon')->installed();
            $manifest = [];

            foreach ($installedAddons as $addon) {
                $addonPath = (string) $addon->getPath();
                $setupFile = rtrim($addonPath, '/\\').'/addon.setup.php';

                $manifest[] = [
                    'prefix' => (string) $addon->getPrefix(),
                    'path' => $addonPath,
                    'setup_mtime' => is_file($setupFile) ? (int) filemtime($setupFile) : 0,
                ];
            }

            usort($manifest, static function (array $a, array $b): int {
                return strcmp($a['prefix'], $b['prefix']);
            });

            return sha1((string) json_encode($manifest));
        } catch (\Throwable $e) {
            return sha1('mcp-discovery-cache-fallback');
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function readPersistentDiscoveryCache(string $signature): ?array
    {
        $cachePath = $this->getPersistentCachePath();
        if ($cachePath === null || ! is_file($cachePath)) {
            return null;
        }

        $raw = @file_get_contents($cachePath);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        $cachedSignature = (string) ($decoded['signature'] ?? '');
        $cachedAt = (int) ($decoded['cached_at'] ?? 0);
        $components = $decoded['components'] ?? null;

        if ($cachedSignature !== $signature || ! is_array($components)) {
            return null;
        }

        if ($cachedAt <= 0 || (time() - $cachedAt) > self::DISCOVERY_CACHE_TTL_SECONDS) {
            return null;
        }

        return $components;
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    private function writePersistentDiscoveryCache(string $signature, array $components): void
    {
        $cachePath = $this->getPersistentCachePath();
        if ($cachePath === null) {
            return;
        }

        $payload = [
            'signature' => $signature,
            'cached_at' => time(),
            'components' => $components,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            return;
        }

        $tmpPath = $cachePath.'.tmp';
        if (@file_put_contents($tmpPath, $json, LOCK_EX) === false) {
            return;
        }

        if (! @rename($tmpPath, $cachePath)) {
            @unlink($tmpPath);
        }
    }

    private function getPersistentCachePath(): ?string
    {
        $basePath = defined('PATH_CACHE')
            ? rtrim(PATH_CACHE, '/\\')
            : rtrim(sys_get_temp_dir(), '/\\');

        if ($basePath === '') {
            return null;
        }

        $cacheDir = $basePath.DIRECTORY_SEPARATOR.self::DISCOVERY_CACHE_DIR;
        if (! is_dir($cacheDir) && ! @mkdir($cacheDir, 0775, true) && ! is_dir($cacheDir)) {
            return null;
        }

        return $cacheDir.DIRECTORY_SEPARATOR.self::DISCOVERY_CACHE_FILE;
    }

    private function shouldUseDiscoveryCache(): bool
    {
        $env = getenv('MCP_DISCOVERY_CACHE');
        if ($env !== false && in_array(strtolower((string) $env), ['0', 'false', 'off', 'no'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Discover MCP components for a specific addon
     *
     * @param  string  $addonName  The addon short name/prefix
     * @param  string  $addonPath  The addon path
     * @return array Array of component data
     */
    public function discoverAddonComponents(string $addonName, string $addonPath): array
    {
        $components = [];
        $seenComponents = []; // Track seen components to prevent duplicates within this addon
        $setupFile = $addonPath.'/addon.setup.php';

        // Check if setup file exists
        if (! file_exists($setupFile)) {
            return $components;
        }

        // Load addon setup
        $setup = require $setupFile;

        // Check if addon has MCP configuration
        // If not, use default config for auto-discovery
        $mcpConfig = $setup['mcp'] ?? [];

        // Check if MCP is explicitly disabled for this addon
        if (isset($mcpConfig['enabled']) && $mcpConfig['enabled'] === false) {
            return $components;
        }

        // Discover from explicit class lists in addon.setup.php (if mcp config exists)
        $explicitComponents = [];
        $explicitClasses = []; // Track classes that are explicitly listed
        if (! empty($mcpConfig) && (isset($mcpConfig['tools']) || isset($mcpConfig['resources']) || isset($mcpConfig['prompts']))) {
            $explicitComponents = $this->discoverFromSetupFile($addonName, $addonPath, $mcpConfig);
            foreach ($explicitComponents as $component) {
                $key = $this->getComponentKey($component);
                if (! isset($seenComponents[$key])) {
                    $seenComponents[$key] = true;
                    $components[] = $component;
                    // Track that this class is explicitly listed (without method, as class-level)
                    $explicitClasses[$component['class']] = true;
                }
            }
        }

        // Discover from directory scanning (attribute-based discovery)
        // Use defaults if mcp config doesn't exist or doesn't specify scan dirs
        $scanDirs = $mcpConfig['scan'] ?? ['Mcp'];
        $excludeDirs = $mcpConfig['exclude'] ?? ['tests', 'vendor'];

        // Only scan directory if we want to discover components not in explicit list
        // For now, we'll still scan but skip explicitly listed classes
        $discoveredComponents = $this->discoverFromDirectory($addonName, $addonPath, $scanDirs, $excludeDirs);

        foreach ($discoveredComponents as $component) {
            $key = $this->getComponentKey($component);

            // Skip if already seen (explicit list takes precedence)
            if (isset($seenComponents[$key])) {
                continue;
            }

            // If this class is explicitly listed and this is a method-level component, skip it
            // (explicit list takes precedence, and method-level attributes are part of the class)
            if (isset($explicitClasses[$component['class']]) && isset($component['method'])) {
                continue;
            }

            // Skip if this class is explicitly listed (explicit list takes precedence for class-level components too)
            if (isset($explicitClasses[$component['class']])) {
                continue;
            }

            $seenComponents[$key] = true;
            $components[] = $component;
        }

        return $components;
    }

    /**
     * Discover components from explicit class lists in addon.setup.php
     *
     * @param  string  $addonName  The addon name
     * @param  string  $addonPath  The addon path
     * @param  array  $mcpConfig  The MCP configuration from addon.setup.php
     * @return array Array of component data
     */
    private function discoverFromSetupFile(string $addonName, string $addonPath, array $mcpConfig): array
    {
        $components = [];
        // For explicitly listed components, don't use prefix unless explicitly set in config
        // This ensures attribute names are used as-is
        $prefix = isset($mcpConfig['prefix']) ? $mcpConfig['prefix'] : '';

        // Process tools
        if (isset($mcpConfig['tools']) && is_array($mcpConfig['tools'])) {
            foreach ($mcpConfig['tools'] as $toolConfig) {
                $component = $this->extractComponentFromConfig($addonName, 'tool', $toolConfig, $prefix);
                if ($component !== null) {
                    $components[] = $component;
                }
            }
        }

        // Process resources
        if (isset($mcpConfig['resources']) && is_array($mcpConfig['resources'])) {
            foreach ($mcpConfig['resources'] as $resourceConfig) {
                $component = $this->extractComponentFromConfig($addonName, 'resource', $resourceConfig, $prefix);
                if ($component !== null) {
                    $components[] = $component;
                }
            }
        }

        // Process prompts
        if (isset($mcpConfig['prompts']) && is_array($mcpConfig['prompts'])) {
            foreach ($mcpConfig['prompts'] as $promptConfig) {
                $component = $this->extractComponentFromConfig($addonName, 'prompt', $promptConfig, $prefix);
                if ($component !== null) {
                    $components[] = $component;
                }
            }
        }

        return $components;
    }

    /**
     * Extract component data from configuration (explicit class list)
     *
     * @param  string  $addonName  The addon name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @param  string|array  $config  Component configuration (class name or array with overrides)
     * @param  string  $prefix  Prefix for element names
     * @return array|null Component data or null if invalid
     */
    private function extractComponentFromConfig(string $addonName, string $type, $config, string $prefix): ?array
    {
        // Handle string class names
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        // Skip if no class specified
        if (! isset($config['class']) || ! is_string($config['class'])) {
            return null;
        }

        $className = $config['class'];

        // Validate class exists and ensure it's loaded
        if (! class_exists($className, true)) {
            return null;
        }

        // Extract metadata (this will read from #[McpTool] attribute)
        $metadata = $this->extractComponentMetadata($className, $type);

        // Also try to read attribute directly (like auto-discovery does) as a fallback
        $attrDescription = null;
        $attrName = null;
        try {
            $reflection = new \ReflectionClass($className);
            if ($type === 'tool') {
                $classAttributes = $reflection->getAttributes(\Mcp\Capability\Attribute\McpTool::class);
                if (! empty($classAttributes)) {
                    $attr = $classAttributes[0]->newInstance();
                    // Try property access first
                    if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                        $attrName = $attr->name;
                    }
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                    // Also try getArguments() as fallback
                    $attrArgs = $classAttributes[0]->getArguments();
                    if (empty($attrName) && isset($attrArgs['name'])) {
                        $attrName = $attrArgs['name'];
                    }
                    if (empty($attrDescription) && isset($attrArgs['description'])) {
                        $attrDescription = $attrArgs['description'];
                    }
                }
            } elseif ($type === 'resource') {
                $classAttributes = $reflection->getAttributes(\Mcp\Capability\Attribute\McpResource::class);
                if (! empty($classAttributes)) {
                    $attr = $classAttributes[0]->newInstance();
                    // Try property access first
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                    // Also try getArguments() as fallback
                    $attrArgs = $classAttributes[0]->getArguments();
                    if (empty($attrDescription) && isset($attrArgs['description'])) {
                        $attrDescription = $attrArgs['description'];
                    }
                }
            } elseif ($type === 'prompt') {
                $classAttributes = $reflection->getAttributes(\Mcp\Capability\Attribute\McpPrompt::class);
                if (! empty($classAttributes)) {
                    $attr = $classAttributes[0]->newInstance();
                    // Try property access first
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                    // Also try getArguments() as fallback
                    $attrArgs = $classAttributes[0]->getArguments();
                    if (empty($attrDescription) && isset($attrArgs['description'])) {
                        $attrDescription = $attrArgs['description'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore reflection errors, fall back to metadata
        }

        // Get name from config or attribute or metadata
        $nameFromConfig = $config['name'] ?? null;
        $nameFromAttribute = $attrName ?? null;
        $nameFromMetadata = $metadata['name'] ?? null;
        $name = $nameFromConfig ?? $nameFromAttribute ?? $nameFromMetadata ?? null;
        $autoPrefix = $config['auto_prefix'] ?? true;

        if ($name === null) {
            // Generate name from class name
            // Only add prefix if prefix is explicitly set (not empty)
            if (! empty($prefix)) {
                $name = $this->generatePrefixedName($className, $prefix, $type);
            } else {
                // No prefix, just generate from class name
                $name = $this->generateNameFromClass($className, $type);
            }
        } elseif ($autoPrefix !== false && ! empty($prefix) && $nameFromConfig !== null) {
            // Only apply prefix if:
            // 1. Name was explicitly provided in config array (not from metadata/attribute)
            // 2. Prefix is not empty
            // 3. Auto-prefix is enabled
            $name = $prefix.'_'.$name;
        }
        // Otherwise, name came from attribute/metadata, use it as-is (no prefix)

        // Get description from config or attribute or metadata
        // Prioritize: config > direct attribute read > metadata extraction > empty
        $description = $config['description'] ?? $attrDescription ?? $metadata['description'] ?? '';

        // Build component data
        $component = [
            'addon' => $addonName,
            'type' => $type,
            'name' => $name,
            'description' => $description,
            'class' => $className,
            'category' => $metadata['category'],
            'permissions' => $metadata['permissions'],
        ];

        // Add URI for resources
        if ($type === 'resource') {
            $component['uri'] = $config['uri'] ?? $metadata['uri'] ?? null;
            $component['mimeType'] = $config['mimeType'] ?? $metadata['mimeType'] ?? null;
        }

        return $component;
    }

    /**
     * Discover components from directory scanning (attribute-based discovery)
     *
     * @param  string  $addonName  The addon name
     * @param  string  $addonPath  The addon path
     * @param  array  $scanDirs  Directories to scan (relative to addon path)
     * @param  array  $excludeDirs  Directories to exclude
     * @return array Array of component data
     */
    private function discoverFromDirectory(string $addonName, string $addonPath, array $scanDirs, array $excludeDirs): array
    {
        $components = [];
        $prefix = $addonName;

        // Ensure addon's vendor autoloader is loaded (for MCP addon's PhpMcp dependencies)
        if ($addonName === 'mcp' && file_exists($addonPath.'/vendor/autoload.php')) {
            require_once $addonPath.'/vendor/autoload.php';
        }

        // Get all PHP files in the specified directories
        $phpFiles = $this->getPhpFilesFromDirectories($addonPath, $scanDirs, $excludeDirs);

        foreach ($phpFiles as $filePath) {
            try {
                // Include the file to ensure classes are loaded
                // Wrap in try-catch to handle missing dependencies gracefully
                try {
                    include_once $filePath;
                } catch (\Throwable $e) {
                    // If including fails, try to continue anyway - the autoloader might handle it
                    continue;
                }

                $classes = $this->getClassesFromFile($filePath);

                foreach ($classes as $className) {
                    // Discover tools
                    $toolComponents = $this->discoverToolsFromClass($addonName, $className, $prefix);
                    $components = array_merge($components, $toolComponents);

                    // Discover resources
                    $resourceComponents = $this->discoverResourcesFromClass($addonName, $className, $prefix);
                    $components = array_merge($components, $resourceComponents);

                    // Discover prompts
                    $promptComponents = $this->discoverPromptsFromClass($addonName, $className, $prefix);
                    $components = array_merge($components, $promptComponents);
                }
            } catch (\Throwable $e) {
                // Skip files that can't be parsed
                continue;
            }
        }

        return $components;
    }

    /**
     * Get all PHP files from the specified directories
     *
     * @param  string  $basePath  Base path to scan from
     * @param  array  $scanDirs  Directories to scan (relative to base path)
     * @param  array  $excludeDirs  Directories to exclude
     * @return array Array of file paths
     */
    private function getPhpFilesFromDirectories(string $basePath, array $scanDirs, array $excludeDirs): array
    {
        $files = [];

        // If no scan directories specified, scan the entire base path
        if (empty($scanDirs)) {
            $scanDirs = [''];
        }

        foreach ($scanDirs as $scanDir) {
            $scanPath = $basePath.'/'.$scanDir;

            if (! is_dir($scanPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($scanPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $relativePath = str_replace($basePath.'/', '', $filePath);

                // Check if file is in an excluded directory
                $isExcluded = false;
                foreach ($excludeDirs as $excludeDir) {
                    if (strpos($relativePath, $excludeDir.'/') === 0) {
                        $isExcluded = true;
                        break;
                    }
                }

                if (! $isExcluded) {
                    $files[] = $filePath;
                }
            }
        }

        return $files;
    }

    /**
     * Get class names from a PHP file
     *
     * @param  string  $filePath  Path to PHP file
     * @return array Array of fully qualified class names
     */
    private function getClassesFromFile(string $filePath): array
    {
        $classes = [];
        $content = file_get_contents($filePath);

        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class names
        if (preg_match_all('/\b(class|interface|trait)\s+(\w+)/', $content, $matches)) {
            foreach ($matches[2] as $className) {
                $fqcn = $namespace ? $namespace.'\\'.$className : $className;

                // Assume the class exists if we found it in the file
                // We'll handle loading issues gracefully in the calling code
                $classes[] = $fqcn;
            }
        }

        return $classes;
    }

    /**
     * Discover tools from a class (attribute-based)
     *
     * @param  string  $addonName  The addon name
     * @param  string  $className  The class name
     * @param  string  $prefix  Prefix for element names
     * @return array Array of tool component data
     */
    private function discoverToolsFromClass(string $addonName, string $className, string $prefix): array
    {
        $components = [];

        // Ensure the class is loaded
        if (! class_exists($className)) {
            // Try to trigger autoloading first
            try {
                class_exists($className, true);
            } catch (\Throwable $e) {
                // Autoloading failed, try to manually include the file
                $filePath = $this->findClassFile($className);
                if ($filePath && file_exists($filePath)) {
                    try {
                        include_once $filePath;
                    } catch (\Throwable $e) {
                        // Can't load the file, skip this class
                        return $components;
                    }
                } else {
                    return $components;
                }
            }
        }

        if (! class_exists($className)) {
            // Still couldn't load the class
            return $components;
        }
        try {
            $reflection = new ReflectionClass($className);

            // Check for class-level McpTool attribute
            $classAttributes = $reflection->getAttributes(McpTool::class);

            if (! empty($classAttributes)) {
                $attrName = null;
                $attrDescription = null;

                // Try to instantiate the attribute
                try {
                    $attr = $classAttributes[0]->newInstance();
                    // Extract from property access
                    if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                        $attrName = $attr->name;
                    }
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                } catch (\Throwable $e) {
                    // Fallback: use getArguments() when newInstance() fails (e.g., PhpMcp not loaded in third-party addon)
                    try {
                        $attrArgs = $classAttributes[0]->getArguments();
                        if (isset($attrArgs['name'])) {
                            $attrName = $attrArgs['name'];
                        }
                        if (isset($attrArgs['description'])) {
                            $attrDescription = $attrArgs['description'];
                        }
                    } catch (\Throwable $e2) {
                        // Both failed, will use metadata fallback
                    }
                }

                $metadata = $this->extractComponentMetadata($className, 'tool');

                $components[] = [
                    'addon' => $addonName,
                    'type' => 'tool',
                    'name' => $attrName ?? $metadata['name'] ?? $this->generatePrefixedName($className, $prefix, 'tool'),
                    'description' => $attrDescription ?? $metadata['description'] ?? '',
                    'class' => $className,
                    'category' => $metadata['category'],
                    'permissions' => $metadata['permissions'],
                ];
            }

            // Check for method-level McpTool attributes
            foreach ($reflection->getMethods() as $method) {
                $methodAttributes = $method->getAttributes(McpTool::class);
                if (! empty($methodAttributes)) {
                    $attrName = null;
                    $attrDescription = null;

                    // Try to instantiate the attribute
                    try {
                        $attr = $methodAttributes[0]->newInstance();
                        // Extract from property access
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $attrName = $attr->name;
                        }
                        if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                            $attrDescription = $attr->description;
                        }
                    } catch (\Throwable $e) {
                        // Fallback: use getArguments() when newInstance() fails
                        try {
                            $attrArgs = $methodAttributes[0]->getArguments();
                            if (isset($attrArgs['name'])) {
                                $attrName = $attrArgs['name'];
                            }
                            if (isset($attrArgs['description'])) {
                                $attrDescription = $attrArgs['description'];
                            }
                        } catch (\Throwable $e2) {
                            // Both failed, will use metadata fallback
                        }
                    }

                    $metadata = $this->extractComponentMetadata($className, 'tool');

                    $components[] = [
                        'addon' => $addonName,
                        'type' => 'tool',
                        'name' => $attrName ?? $method->getName(),
                        'description' => $attrDescription ?? $metadata['description'] ?? '',
                        'class' => $className,
                        'method' => $method->getName(),
                        'category' => $metadata['category'],
                        'permissions' => $metadata['permissions'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Skip classes that can't be reflected
        }

        return $components;
    }

    /**
     * Discover resources from a class (attribute-based)
     *
     * @param  string  $addonName  The addon name
     * @param  string  $className  The class name
     * @param  string  $prefix  Prefix for element names
     * @return array Array of resource component data
     */
    private function discoverResourcesFromClass(string $addonName, string $className, string $prefix): array
    {
        $components = [];

        // Ensure the class is loaded
        if (! class_exists($className)) {
            // Try to trigger autoloading first
            try {
                class_exists($className, true);
            } catch (\Throwable $e) {
                // Autoloading failed, try to manually include the file
                $filePath = $this->findClassFile($className);
                if ($filePath && file_exists($filePath)) {
                    try {
                        include_once $filePath;
                    } catch (\Throwable $e) {
                        // Can't load the file, skip this class
                        return $components;
                    }
                } else {
                    return $components;
                }
            }
        }

        if (! class_exists($className)) {
            // Still couldn't load the class
            return $components;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Check for class-level McpResource attribute
            $classAttributes = $reflection->getAttributes(McpResource::class);
            if (! empty($classAttributes)) {
                $attrName = null;
                $attrDescription = null;
                $attrUri = null;
                $attrMimeType = null;

                // Try to instantiate the attribute
                try {
                    $attr = $classAttributes[0]->newInstance();
                    // Extract from property access
                    if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                        $attrName = $attr->name;
                    }
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                    if (property_exists($attr, 'uri') && $attr->uri !== null && $attr->uri !== '') {
                        $attrUri = $attr->uri;
                    }
                    if (property_exists($attr, 'mimeType') && $attr->mimeType !== null && $attr->mimeType !== '') {
                        $attrMimeType = $attr->mimeType;
                    }
                } catch (\Throwable $e) {
                    // Fallback: use getArguments() when newInstance() fails (e.g., PhpMcp not loaded in third-party addon)
                    try {
                        $attrArgs = $classAttributes[0]->getArguments();
                        if (isset($attrArgs['name'])) {
                            $attrName = $attrArgs['name'];
                        }
                        if (isset($attrArgs['description'])) {
                            $attrDescription = $attrArgs['description'];
                        }
                        if (isset($attrArgs['uri'])) {
                            $attrUri = $attrArgs['uri'];
                        }
                        if (isset($attrArgs['mimeType'])) {
                            $attrMimeType = $attrArgs['mimeType'];
                        }
                    } catch (\Throwable $e2) {
                        // Both failed, will use metadata fallback
                    }
                }

                $metadata = $this->extractComponentMetadata($className, 'resource');

                $components[] = [
                    'addon' => $addonName,
                    'type' => 'resource',
                    'name' => $attrName ?? $metadata['name'] ?? $this->generatePrefixedName($className, $prefix, 'resource'),
                    'description' => $attrDescription ?? $metadata['description'] ?? '',
                    'class' => $className,
                    'uri' => $attrUri ?? $metadata['uri'] ?? null,
                    'mimeType' => $attrMimeType ?? $metadata['mimeType'] ?? null,
                    'category' => $metadata['category'],
                    'permissions' => $metadata['permissions'],
                ];
            }

            // Check for McpResourceTemplate attributes
            $templateAttributes = $reflection->getAttributes(McpResourceTemplate::class);
            if (! empty($templateAttributes)) {
                $attrName = null;
                $attrDescription = null;
                $attrUriTemplate = null;
                $attrMimeType = null;

                // Try to instantiate the attribute
                try {
                    $attr = $templateAttributes[0]->newInstance();
                    // Extract from property access
                    if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                        $attrName = $attr->name;
                    }
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                    if (property_exists($attr, 'uriTemplate') && $attr->uriTemplate !== null && $attr->uriTemplate !== '') {
                        $attrUriTemplate = $attr->uriTemplate;
                    }
                    if (property_exists($attr, 'mimeType') && $attr->mimeType !== null && $attr->mimeType !== '') {
                        $attrMimeType = $attr->mimeType;
                    }
                } catch (\Throwable $e) {
                    // Fallback: use getArguments() when newInstance() fails
                    try {
                        $attrArgs = $templateAttributes[0]->getArguments();
                        if (isset($attrArgs['name'])) {
                            $attrName = $attrArgs['name'];
                        }
                        if (isset($attrArgs['description'])) {
                            $attrDescription = $attrArgs['description'];
                        }
                        if (isset($attrArgs['uriTemplate'])) {
                            $attrUriTemplate = $attrArgs['uriTemplate'];
                        }
                        if (isset($attrArgs['mimeType'])) {
                            $attrMimeType = $attrArgs['mimeType'];
                        }
                    } catch (\Throwable $e2) {
                        // Both failed, will use metadata fallback
                    }
                }

                $metadata = $this->extractComponentMetadata($className, 'resource');

                $components[] = [
                    'addon' => $addonName,
                    'type' => 'resource',
                    'name' => $attrName ?? $metadata['name'] ?? $this->generatePrefixedName($className, $prefix, 'resource'),
                    'description' => $attrDescription ?? $metadata['description'] ?? '',
                    'class' => $className,
                    'uri' => $attrUriTemplate ?? $metadata['uri'] ?? null,
                    'mimeType' => $attrMimeType ?? $metadata['mimeType'] ?? null,
                    'isTemplate' => true,
                    'category' => $metadata['category'],
                    'permissions' => $metadata['permissions'],
                ];
            }

            // Check for method-level attributes
            foreach ($reflection->getMethods() as $method) {
                $methodAttributes = $method->getAttributes(McpResource::class);
                foreach ($methodAttributes as $methodAttr) {
                    $attrName = null;
                    $attrDescription = null;
                    $attrUri = null;
                    $attrMimeType = null;

                    // Try to instantiate the attribute
                    try {
                        $attr = $methodAttr->newInstance();
                        // Extract from property access
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $attrName = $attr->name;
                        }
                        if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                            $attrDescription = $attr->description;
                        }
                        if (property_exists($attr, 'uri') && $attr->uri !== null && $attr->uri !== '') {
                            $attrUri = $attr->uri;
                        }
                        if (property_exists($attr, 'mimeType') && $attr->mimeType !== null && $attr->mimeType !== '') {
                            $attrMimeType = $attr->mimeType;
                        }
                    } catch (\Throwable $e) {
                        // Fallback: use getArguments() when newInstance() fails
                        try {
                            $attrArgs = $methodAttr->getArguments();
                            if (isset($attrArgs['name'])) {
                                $attrName = $attrArgs['name'];
                            }
                            if (isset($attrArgs['description'])) {
                                $attrDescription = $attrArgs['description'];
                            }
                            if (isset($attrArgs['uri'])) {
                                $attrUri = $attrArgs['uri'];
                            }
                            if (isset($attrArgs['mimeType'])) {
                                $attrMimeType = $attrArgs['mimeType'];
                            }
                        } catch (\Throwable $e2) {
                            // Both failed, will use metadata fallback
                        }
                    }

                    $metadata = $this->extractComponentMetadata($className, 'resource');

                    $components[] = [
                        'addon' => $addonName,
                        'type' => 'resource',
                        'name' => $attrName ?? $method->getName(),
                        'description' => $attrDescription ?? $metadata['description'] ?? '',
                        'class' => $className,
                        'method' => $method->getName(),
                        'uri' => $attrUri ?? null,
                        'mimeType' => $attrMimeType ?? null,
                        'category' => $metadata['category'],
                        'permissions' => $metadata['permissions'],
                    ];
                }

                $templateMethodAttributes = $method->getAttributes(McpResourceTemplate::class);
                foreach ($templateMethodAttributes as $methodAttr) {
                    $attrName = null;
                    $attrDescription = null;
                    $attrUriTemplate = null;
                    $attrMimeType = null;

                    // Try to instantiate the attribute
                    try {
                        $attr = $methodAttr->newInstance();
                        // Extract from property access
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $attrName = $attr->name;
                        }
                        if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                            $attrDescription = $attr->description;
                        }
                        if (property_exists($attr, 'uriTemplate') && $attr->uriTemplate !== null && $attr->uriTemplate !== '') {
                            $attrUriTemplate = $attr->uriTemplate;
                        }
                        if (property_exists($attr, 'mimeType') && $attr->mimeType !== null && $attr->mimeType !== '') {
                            $attrMimeType = $attr->mimeType;
                        }
                    } catch (\Throwable $e) {
                        // Fallback: use getArguments() when newInstance() fails
                        try {
                            $attrArgs = $methodAttr->getArguments();
                            if (isset($attrArgs['name'])) {
                                $attrName = $attrArgs['name'];
                            }
                            if (isset($attrArgs['description'])) {
                                $attrDescription = $attrArgs['description'];
                            }
                            if (isset($attrArgs['uriTemplate'])) {
                                $attrUriTemplate = $attrArgs['uriTemplate'];
                            }
                            if (isset($attrArgs['mimeType'])) {
                                $attrMimeType = $attrArgs['mimeType'];
                            }
                        } catch (\Throwable $e2) {
                            // Both failed, will use metadata fallback
                        }
                    }

                    $metadata = $this->extractComponentMetadata($className, 'resource');

                    $components[] = [
                        'addon' => $addonName,
                        'type' => 'resource',
                        'name' => $attrName ?? $method->getName(),
                        'description' => $attrDescription ?? $metadata['description'] ?? '',
                        'class' => $className,
                        'method' => $method->getName(),
                        'uri' => $attrUriTemplate ?? null,
                        'mimeType' => $attrMimeType ?? null,
                        'isTemplate' => true,
                        'category' => $metadata['category'],
                        'permissions' => $metadata['permissions'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Skip classes that can't be reflected
        }

        return $components;
    }

    /**
     * Discover prompts from a class (attribute-based)
     *
     * @param  string  $addonName  The addon name
     * @param  string  $className  The class name
     * @param  string  $prefix  Prefix for element names
     * @return array Array of prompt component data
     */
    private function discoverPromptsFromClass(string $addonName, string $className, string $prefix): array
    {
        $components = [];

        // Ensure the class is loaded
        if (! class_exists($className)) {
            // Try to trigger autoloading first
            try {
                class_exists($className, true);
            } catch (\Throwable $e) {
                // Autoloading failed, try to manually include the file
                $filePath = $this->findClassFile($className);
                if ($filePath && file_exists($filePath)) {
                    try {
                        include_once $filePath;
                    } catch (\Throwable $e) {
                        // Can't load the file, skip this class
                        return $components;
                    }
                } else {
                    return $components;
                }
            }
        }

        if (! class_exists($className)) {
            // Still couldn't load the class
            return $components;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Check for class-level McpPrompt attribute
            $classAttributes = $reflection->getAttributes(McpPrompt::class);
            if (! empty($classAttributes)) {
                $attrName = null;
                $attrDescription = null;

                // Try to instantiate the attribute
                try {
                    $attr = $classAttributes[0]->newInstance();
                    // Extract from property access
                    if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                        $attrName = $attr->name;
                    }
                    if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                        $attrDescription = $attr->description;
                    }
                } catch (\Throwable $e) {
                    // Fallback: use getArguments() when newInstance() fails
                    try {
                        $attrArgs = $classAttributes[0]->getArguments();
                        if (isset($attrArgs['name'])) {
                            $attrName = $attrArgs['name'];
                        }
                        if (isset($attrArgs['description'])) {
                            $attrDescription = $attrArgs['description'];
                        }
                    } catch (\Throwable $e2) {
                        // Both failed, will use metadata fallback
                    }
                }

                $metadata = $this->extractComponentMetadata($className, 'prompt');

                $components[] = [
                    'addon' => $addonName,
                    'type' => 'prompt',
                    'name' => $attrName ?? $metadata['name'] ?? $this->generatePrefixedName($className, $prefix, 'prompt'),
                    'description' => $attrDescription ?? $metadata['description'] ?? '',
                    'class' => $className,
                    'category' => $metadata['category'],
                    'permissions' => $metadata['permissions'],
                ];
            }

            // Check for method-level McpPrompt attributes
            foreach ($reflection->getMethods() as $method) {
                $methodAttributes = $method->getAttributes(McpPrompt::class);
                if (! empty($methodAttributes)) {
                    $attrName = null;
                    $attrDescription = null;

                    // Try to instantiate the attribute
                    try {
                        $attr = $methodAttributes[0]->newInstance();
                        // Extract from property access
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $attrName = $attr->name;
                        }
                        if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                            $attrDescription = $attr->description;
                        }
                    } catch (\Throwable $e) {
                        // Fallback: use getArguments() when newInstance() fails
                        try {
                            $attrArgs = $methodAttributes[0]->getArguments();
                            if (isset($attrArgs['name'])) {
                                $attrName = $attrArgs['name'];
                            }
                            if (isset($attrArgs['description'])) {
                                $attrDescription = $attrArgs['description'];
                            }
                        } catch (\Throwable $e2) {
                            // Both failed, will use metadata fallback
                        }
                    }

                    $metadata = $this->extractComponentMetadata($className, 'prompt');

                    $components[] = [
                        'addon' => $addonName,
                        'type' => 'prompt',
                        'name' => $attrName ?? $method->getName(),
                        'description' => $attrDescription ?? $metadata['description'] ?? '',
                        'class' => $className,
                        'method' => $method->getName(),
                        'category' => $metadata['category'],
                        'permissions' => $metadata['permissions'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Skip classes that can't be reflected
        }

        return $components;
    }

    /**
     * Extract component metadata from a class
     *
     * @param  string  $className  The class name
     * @param  string  $type  Component type (tool|resource|prompt)
     * @return array Metadata array with name, description, category, permissions, uri (for resources)
     */
    public function extractComponentMetadata(string $className, string $type): array
    {
        $metadata = [
            'name' => null,
            'description' => null,
            'category' => null,
            'permissions' => [],
            'uri' => null,
            'mimeType' => null,
        ];

        try {
            if (! class_exists($className)) {
                return $metadata;
            }

            $reflection = new ReflectionClass($className);

            // Get EE-specific metadata
            $eeMetadata = $this->attributeReader->getElementMetadata($className);
            $metadata['category'] = $eeMetadata['category'];
            $metadata['permissions'] = $eeMetadata['permissions'];

            // Check for McpTool/McpResource/McpPrompt attribute first to get name/description
            // Wrap in try-catch in case attribute classes aren't available (e.g., PhpMcp not loaded)
            try {
                if ($type === 'tool' && class_exists(McpTool::class)) {
                    $classAttributes = $reflection->getAttributes(McpTool::class);
                    if (! empty($classAttributes)) {
                        $attr = $classAttributes[0]->newInstance();
                        // Try property access first (for PhpMcp attributes)
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $metadata['name'] = $attr->name;
                        }
                        if (property_exists($attr, 'description')) {
                            $descValue = $attr->description;
                            if ($descValue !== null && $descValue !== '') {
                                $metadata['description'] = $descValue;
                            }
                        }
                        // Also try getArguments() as fallback (for some attribute implementations)
                        $attrArgs = $classAttributes[0]->getArguments();
                        if (empty($metadata['name']) && isset($attrArgs['name'])) {
                            $metadata['name'] = $attrArgs['name'];
                        }
                        if (empty($metadata['description']) && isset($attrArgs['description'])) {
                            $metadata['description'] = $attrArgs['description'];
                        }
                    }
                } elseif ($type === 'resource' && class_exists(\Mcp\Capability\Attribute\McpResource::class)) {
                    $classAttributes = $reflection->getAttributes(\Mcp\Capability\Attribute\McpResource::class);
                    if (! empty($classAttributes)) {
                        $attr = $classAttributes[0]->newInstance();
                        // Try property access first (for PhpMcp attributes)
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $metadata['name'] = $attr->name;
                        }
                        if (property_exists($attr, 'description') && $attr->description !== null && $attr->description !== '') {
                            $metadata['description'] = $attr->description;
                        }
                        if (property_exists($attr, 'uri') && $attr->uri !== null && $attr->uri !== '') {
                            $metadata['uri'] = $attr->uri;
                        }
                        if (property_exists($attr, 'mimeType') && $attr->mimeType !== null && $attr->mimeType !== '') {
                            $metadata['mimeType'] = $attr->mimeType;
                        }
                        // Also try getArguments() as fallback (for some attribute implementations)
                        $attrArgs = $classAttributes[0]->getArguments();
                        if (empty($metadata['name']) && isset($attrArgs['name'])) {
                            $metadata['name'] = $attrArgs['name'];
                        }
                        if (empty($metadata['description']) && isset($attrArgs['description'])) {
                            $metadata['description'] = $attrArgs['description'];
                        }
                        if (empty($metadata['uri']) && isset($attrArgs['uri'])) {
                            $metadata['uri'] = $attrArgs['uri'];
                        }
                        if (empty($metadata['mimeType']) && isset($attrArgs['mimeType'])) {
                            $metadata['mimeType'] = $attrArgs['mimeType'];
                        }
                    }
                } elseif ($type === 'prompt' && class_exists(\Mcp\Capability\Attribute\McpPrompt::class)) {
                    $classAttributes = $reflection->getAttributes(\Mcp\Capability\Attribute\McpPrompt::class);
                    if (! empty($classAttributes)) {
                        $attr = $classAttributes[0]->newInstance();
                        if (property_exists($attr, 'name') && $attr->name !== null && $attr->name !== '') {
                            $metadata['name'] = $attr->name;
                        }
                        if (property_exists($attr, 'description') && $attr->description !== null) {
                            $metadata['description'] = $attr->description;
                        }
                    }
                }
            } catch (\Throwable $attrException) {
                // Attribute classes may not be available, continue to instance method check
                // This is expected for third-party addons that don't have PhpMcp loaded
            }

            // Try to get name and description from instance methods (for AbstractTool/AbstractResource)
            // Only if not already set from attribute
            if ($reflection->isSubclassOf(AbstractTool::class) && $type === 'tool') {
                try {
                    $instance = new $className();
                    // Only use instance method if metadata doesn't have it set
                    if (empty($metadata['name']) && method_exists($instance, 'name')) {
                        $metadata['name'] = $instance->name();
                    }
                    // Check if description is empty or not set (not just empty string check)
                    if ((! isset($metadata['description']) || $metadata['description'] === '') && method_exists($instance, 'description')) {
                        $metadata['description'] = $instance->description();
                    }
                } catch (\Throwable $e) {
                    // Can't instantiate, skip
                }
            } elseif ($reflection->isSubclassOf(AbstractResource::class) && $type === 'resource') {
                try {
                    $instance = new $className();
                    if (empty($metadata['name']) && method_exists($instance, 'name')) {
                        $metadata['name'] = $instance->name();
                    }
                    if (empty($metadata['description']) && method_exists($instance, 'description')) {
                        $metadata['description'] = $instance->description();
                    }
                    if (empty($metadata['uri']) && method_exists($instance, 'uri')) {
                        $metadata['uri'] = $instance->uri();
                    }
                    if (empty($metadata['mimeType']) && method_exists($instance, 'mimeType')) {
                        $metadata['mimeType'] = $instance->mimeType();
                    }
                } catch (\Throwable $e) {
                    // Can't instantiate, skip
                }
            } elseif ($reflection->isSubclassOf(AbstractPrompt::class) && $type === 'prompt') {
                try {
                    $instance = new $className();
                    if (empty($metadata['name']) && method_exists($instance, 'name')) {
                        $metadata['name'] = $instance->name();
                    }
                    if (empty($metadata['description']) && method_exists($instance, 'description')) {
                        $metadata['description'] = $instance->description();
                    }
                } catch (\Throwable $e) {
                    // Can't instantiate, skip
                }
            }

            // Fall back to docblock for description if not found
            if (empty($metadata['description'])) {
                $docComment = $reflection->getDocComment();
                if ($docComment) {
                    // Extract description from docblock
                    if (preg_match('/\*\s+(.+)/', $docComment, $matches)) {
                        $metadata['description'] = trim($matches[1]);
                    }
                }
            }

            // Generate name from class name if not found
            if (empty($metadata['name'])) {
                $metadata['name'] = $this->generateNameFromClass($className, $type);
            }

        } catch (\Throwable $e) {
            // Return default metadata on error
        }

        return $metadata;
    }

    /**
     * Generate a name from a class name
     *
     * @param  string  $className  The class name
     * @param  string  $type  Component type
     * @return string Generated name
     */
    private function generateNameFromClass(string $className, string $type): string
    {
        $shortClassName = basename(str_replace('\\', '/', $className));
        $baseName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortClassName));
        $baseName = preg_replace('/_(tool|resource|prompt)$/i', '', $baseName);

        return $baseName;
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
        $baseName = $this->generateNameFromClass($className, $type);

        return $prefix.'_'.$baseName;
    }

    /**
     * Find the file path for a given class name
     *
     * @param  string  $className  The fully qualified class name
     * @return string|null The file path or null if not found
     */
    private function findClassFile(string $className): ?string
    {
        // Convert class name to file path
        // ExpressionEngine\Addons\Mcp\Mcp\Tools\DatabaseQuery -> ExpressionEngine/Addons/Mcp/Mcp/Tools/DatabaseQuery.php
        $relativePath = str_replace('\\', '/', $className).'.php';

        // Determine base path - user addons are in system/user/addons/, not system/ee/
        $basePath = defined('SYSPATH') ? dirname(SYSPATH) : 'system';
        if (! defined('PATH_THIRD')) {
            define('PATH_THIRD', $basePath.'/user/addons/');
        }

        // Try common base paths
        $possiblePaths = [
            SYSPATH.'ee/'.$relativePath,
            PATH_THIRD.str_replace('ExpressionEngine/Addons/', '', $relativePath),
            SYSPATH.'ee/ExpressionEngine/'.$relativePath,
        ];

        // Special handling for addon classes (ExpressionEngine\Addons\AddonName\...)
        if (preg_match('/^ExpressionEngine\\\\Addons\\\\([^\\\\]+)\\\\(.+)$/', $className, $matches)) {
            $addonName = strtolower($matches[1]);
            $addonRelativePath = $matches[2];
            $filePath = str_replace('\\', '/', $addonRelativePath).'.php';
            $possiblePaths[] = PATH_THIRD.$addonName.'/'.$filePath;
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Generate a unique key for a component to identify duplicates
     *
     * Uses class name (and method if present) as the primary identifier since
     * the same class can be discovered via explicit list and directory scanning
     * with different names.
     *
     * @param  array  $component  Component data array
     * @return string Unique key for the component
     */
    private function getComponentKey(array $component): string
    {
        // Use class as primary identifier (same class = same component, regardless of name)
        // Include method if present to distinguish method-level components
        $key = $component['addon'].'|'.$component['type'].'|'.$component['class'];
        if (isset($component['method'])) {
            $key .= '|'.$component['method'];
        }

        return $key;
    }
}
