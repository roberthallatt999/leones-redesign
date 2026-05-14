<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Mcp\Capability\Attribute\CompletionProvider as CompletionProviderAttribute;
use Mcp\Capability\Completion\EnumCompletionProvider;
use Mcp\Capability\Completion\ListCompletionProvider;
use Mcp\Capability\Completion\ProviderInterface;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\Annotations;
use Mcp\Schema\Prompt;
use Mcp\Schema\PromptArgument;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Request\GetPromptRequest;
use Mcp\Schema\Resource;
use Mcp\Schema\ResourceTemplate;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * MCP Registrar Service
 *
 * Provides a clean interface for registering MCP elements (tools, resources, prompts)
 * with the MCP server registry, allowing addons to register elements through
 * providers or direct calls.
 */
class Registrar
{
    private RegistryInterface $registry;

    private ?PermissionEvaluatorService $permissionEvaluator = null;

    /**
     * Create a new registrar instance
     *
     * @param  RegistryInterface  $registry  The MCP registry to register elements with
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Discover MCP elements from the filesystem
     *
     * @param  string  $basePath  Base path to scan from
     * @param  array  $scanDirs  Relative directories to scan
     * @param  array  $excludeDirs  Directories to exclude
     * @param  bool  $saveToCache  Whether to cache discovery results
     */
    public function discover(
        string $basePath,
        array $scanDirs = [],
        array $excludeDirs = [],
        bool $saveToCache = true
    ): void {
        // Discovery is handled externally by ComponentDiscoveryService in this addon.
    }

    /**
     * Register a tool with the MCP server
     *
     * @param  callable|class-string  $handler  Tool handler (callable or invokable class)
     * @param  string|null  $name  Tool name (optional, auto-detected from attributes)
     * @param  string|null  $description  Tool description
     * @param  array|null  $annotations  MCP annotations
     * @param  bool  $wrapWithErrorHandler  If true, wrap handler to return disabled error
     */
    public function withTool(
        callable|string $handler,
        ?string $name = null,
        ?string $description = null,
        ?array $annotations = null,
        bool $wrapWithErrorHandler = false,
        ?array $permissions = null
    ): void {
        // If handler is a class name, check if it extends AbstractTool
        if (is_string($handler) && class_exists($handler)) {
            try {
                $reflection = new \ReflectionClass($handler);

                // Check if it extends AbstractTool
                if ($reflection->isSubclassOf(\ExpressionEngine\Addons\Mcp\Support\AbstractTool::class)) {
                    // Get name and description before converting handler
                    $className = $handler;

                    // Get name from McpTool attribute or instance method (only if not already provided)
                    if ($name === null) {
                        $mcpToolAttrs = $reflection->getAttributes(\Mcp\Capability\Attribute\McpTool::class);
                        if (! empty($mcpToolAttrs)) {
                            $mcpToolAttr = $mcpToolAttrs[0]->newInstance();
                            $name = $mcpToolAttr->name ?? null;
                        }

                        // If still no name, try to get from instance
                        if ($name === null) {
                            $instance = new $className();
                            if (method_exists($instance, 'name')) {
                                $name = $instance->name();
                            }
                        }
                    }

                    // Get description from instance method if not provided
                    if ($description === null) {
                        $instance = new $className();
                        if (method_exists($instance, 'description')) {
                            $description = $instance->description();
                        }
                    }

                    // AbstractTool classes use handle() method, so register as [class, 'handle']
                    // This allows the server to properly invoke the handle method
                    $handler = [$className, 'handle'];
                } else {
                    // Try to read McpTool attribute for non-AbstractTool classes
                    if ($name === null) {
                        $mcpToolAttrs = $reflection->getAttributes(\Mcp\Capability\Attribute\McpTool::class);
                        if (! empty($mcpToolAttrs)) {
                            $mcpToolAttr = $mcpToolAttrs[0]->newInstance();
                            $name = $mcpToolAttr->name ?? null;
                            if ($description === null) {
                                $description = $mcpToolAttr->description ?? null;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                @error_log("[MCP Registrar] Error reading tool metadata from {$handler}: ".$e->getMessage());
            }
        }

        // For AbstractTool classes, we need to manually create the Tool schema
        // because they use a custom schema() method instead of method signature inference
        if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0])) {
            $reflection = new \ReflectionClass($handler[0]);
            if ($reflection->isSubclassOf(\ExpressionEngine\Addons\Mcp\Support\AbstractTool::class)) {
                try {
                    // Get the schema from the AbstractTool instance
                    $instance = new $handler[0]();

                    if (method_exists($instance, 'schema')) {
                        $customSchema = $instance->schema();

                        // Ensure it's in the correct format
                        if (! isset($customSchema['type'])) {
                            $customSchema = ['type' => 'object', 'properties' => $customSchema];
                        }

                        // Recursively convert SchemaBuilder objects to arrays
                        // This is needed because SchemaBuilder->toArray() doesn't recursively convert nested builders
                        $customSchema = $this->convertSchemaBuildersToArrays($customSchema);

                        // Ensure properties is always an object (associative array), not a numeric array
                        // JSON Schema requires properties to be an object, not an array
                        // Also convert empty arrays to stdClass() so they serialize as {} not []
                        // Do this AFTER recursive conversion to avoid issues with stdClass in array operations
                        $customSchema = $this->normalizeJsonSchemaProperties($customSchema);

                        // Build ToolAnnotations from the AbstractTool instance hints,
                        // merging with any explicitly provided annotations.
                        $annotationsObj = $this->buildToolAnnotationsFromInstance($instance, $annotations);
                        $tool = new Tool(
                            $name ?? 'tool',
                            $customSchema,
                            $description ?? 'Tool description',
                            $annotationsObj
                        );

                        // Create a wrapper closure that uses request context injection
                        // and forwards the full MCP arguments map to AbstractTool::handle().
                        $className = $handler[0];
                        if ($wrapWithErrorHandler) {
                            $wrapper = function (...$args) {
                                throw new \RuntimeException('This tool is disabled in ExpressionEngine settings. Please inform the user that this tool has been disabled in the ExpressionEngine control panel settings.');
                            };
                        } else {
                            $wrapper = function (RequestContext $context) use ($className) {
                                $instance = new $className();

                                return $instance->handle($this->extractArgumentsFromContext($context));
                            };
                        }

                        $wrapper = $this->withRuntimeGuards(
                            $wrapper,
                            'tool',
                            $name ?? 'tool',
                            $permissions,
                            $wrapWithErrorHandler
                        );

                        // Register via registry with the wrapper closure
                        // Pass $isManual = false to allow automatic discovery/inference
                        $this->registry->registerTool($tool, $wrapper, false);

                        return;
                    }
                } catch (\Throwable $e) {
                    @error_log("[MCP Registrar] Error registering AbstractTool {$handler[0]}: ".$e->getMessage());
                }
            }
        }

        // For other handlers, create Tool schema and register via registry
        try {
            $annotationsObj = $this->toToolAnnotations($annotations);
            $tool = new Tool(
                $name ?? 'tool',
                ['type' => 'object', 'properties' => new \stdClass()],
                $description ?? 'Tool description',
                $annotationsObj
            );

            // Wrap handler with error if needed
            if ($wrapWithErrorHandler) {
                $handler = function (...$args) {
                    throw new \RuntimeException('This tool is disabled in ExpressionEngine settings. Please inform the user that this tool has been disabled in the ExpressionEngine control panel settings.');
                };
            }

            $guardedHandler = $this->withRuntimeGuards(
                $handler,
                'tool',
                $name ?? 'tool',
                $permissions,
                $wrapWithErrorHandler
            );

            $this->registry->registerTool($tool, $guardedHandler, false);
        } catch (\Throwable $e) {
            @error_log("[MCP Registrar] Error registering tool {$name}: ".$e->getMessage());
        }
    }

    /**
     * Register a resource with the MCP server
     *
     * @param  callable|class-string  $handler  Resource handler
     * @param  string  $uri  Resource URI
     * @param  string|null  $mimeType  MIME type
     * @param  string|null  $name  Resource name
     * @param  string|null  $description  Resource description
     * @param  array|null  $annotations  MCP annotations
     * @param  bool  $wrapWithErrorHandler  If true, wrap handler to return disabled error
     */
    public function withResource(
        callable|array|string $handler,
        string $uri,
        ?string $mimeType = null,
        ?string $name = null,
        ?string $description = null,
        ?array $annotations = null,
        bool $wrapWithErrorHandler = false,
        ?array $permissions = null
    ): void {
        // If handler is a class name, check if it extends AbstractResource
        if (is_string($handler) && class_exists($handler)) {
            try {
                $reflection = new \ReflectionClass($handler);

                // Check if it extends AbstractResource
                if ($reflection->isSubclassOf(\ExpressionEngine\Addons\Mcp\Support\AbstractResource::class)) {
                    // Get name, description, and mimeType from instance if not provided
                    $className = $handler;
                    $instance = new $className();

                    if ($name === null && method_exists($instance, 'name')) {
                        $name = $instance->name();
                    }

                    if ($description === null && method_exists($instance, 'description')) {
                        $description = $instance->description();
                    }

                    if ($mimeType === null && method_exists($instance, 'mimeType')) {
                        $mimeType = $instance->mimeType();
                    }

                    // AbstractResource classes use fetch() method, so create a wrapper closure
                    // that instantiates the class and calls fetch()
                    if ($wrapWithErrorHandler) {
                        $wrapper = function (array $params = []) {
                            throw new \RuntimeException('This resource is disabled in ExpressionEngine settings. Please inform the user that this resource has been disabled in the ExpressionEngine control panel settings.');
                        };
                    } else {
                        $wrapper = function (array $params = []) use ($className) {
                            // Discard any stray output so only JSON goes to stdio
                            while (ob_get_level() > 0) {
                                ob_end_clean();
                            }
                            ob_start();
                            try {
                                $instance = new $className();
                                $result = $instance->fetch($params);
                                ob_end_clean();

                                return $result;
                            } catch (\Throwable $e) {
                                ob_end_clean();
                                throw $e;
                            }
                        };
                    }

                    if (empty($uri)) {
                        throw new \InvalidArgumentException('Resource URI is required');
                    }
                    $wrapper = $this->withRuntimeGuards(
                        $wrapper,
                        'resource',
                        $name ?? 'resource',
                        $permissions,
                        $wrapWithErrorHandler
                    );
                    $annotationsObj = $this->toAnnotations($annotations);
                    $resource = new Resource($uri, $name ?? 'resource', $description, $mimeType, $annotationsObj);
                    $this->registry->registerResource($resource, $wrapper);

                    return;
                }
            } catch (\Throwable $e) {
                @error_log("[MCP Registrar] Error reading resource metadata from {$handler}: ".$e->getMessage());
            }
        }

        // Convert array callable to closure if needed
        if (is_array($handler) && is_callable($handler)) {
            if ($wrapWithErrorHandler) {
                $handler = function (...$args) {
                    throw new \RuntimeException('This resource is disabled in ExpressionEngine settings. Please inform the user that this resource has been disabled in the ExpressionEngine control panel settings.');
                };
            } else {
                $handler = function (...$args) use ($handler) {
                    return call_user_func_array($handler, $args);
                };
            }
        } elseif ($wrapWithErrorHandler) {
            $handler = function (...$args) {
                throw new \RuntimeException('This resource is disabled in ExpressionEngine settings. Please inform the user that this resource has been disabled in the ExpressionEngine control panel settings.');
            };
        }

        if (empty($uri)) {
            throw new \InvalidArgumentException('Resource URI is required');
        }
        $handler = $this->withRuntimeGuards(
            $handler,
            'resource',
            $name ?? 'resource',
            $permissions,
            $wrapWithErrorHandler
        );
        $annotationsObj = $this->toAnnotations($annotations);
        $resource = new Resource($uri, $name ?? 'resource', $description, $mimeType, $annotationsObj);
        $this->registry->registerResource($resource, $handler);
    }

    /**
     * Register a resource template with the MCP server
     *
     * @param  callable|class-string  $handler  Resource template handler
     * @param  string  $uriTemplate  URI template pattern
     * @param  string|null  $mimeType  MIME type
     * @param  string|null  $name  Resource name
     * @param  string|null  $description  Resource description
     * @param  array|null  $annotations  MCP annotations
     * @param  bool  $wrapWithErrorHandler  If true, wrap handler to return disabled error
     */
    public function withResourceTemplate(
        callable|array|string $handler,
        string $uriTemplate,
        ?string $mimeType = null,
        ?string $name = null,
        ?string $description = null,
        ?array $annotations = null,
        bool $wrapWithErrorHandler = false,
        ?array $permissions = null
    ): void {
        $completionProviders = $this->extractCompletionProviders($handler);

        // Handle AbstractResource classes - they use method-level attributes, so handler is already [class, method]
        if (is_array($handler) && count($handler) === 2) {
            [$className, $methodName] = $handler;

            // Wrap handler with error if needed
            if ($wrapWithErrorHandler) {
                $handler = function (...$args) {
                    throw new \RuntimeException('This resource is disabled in ExpressionEngine settings. Please inform the user that this resource has been disabled in the ExpressionEngine control panel settings.');
                };
            }

            $annotationsObj = $this->toAnnotations($annotations);
            $template = new ResourceTemplate(
                $uriTemplate,
                $name ?? $methodName,
                $description,
                $mimeType,
                $annotationsObj
            );

            $guardedHandler = $this->withRuntimeGuards(
                $handler,
                'resource',
                $name ?? $methodName,
                $permissions,
                $wrapWithErrorHandler
            );

            $this->registry->registerResourceTemplate($template, $guardedHandler, $completionProviders, false);

            return;
        }

        // For other handlers (closures, invokable classes, etc.)
        // Convert array callable to closure if needed
        if (is_array($handler) && is_callable($handler)) {
            if ($wrapWithErrorHandler) {
                $handler = function (...$args) {
                    throw new \RuntimeException('This resource is disabled in ExpressionEngine settings. Please inform the user that this resource has been disabled in the ExpressionEngine control panel settings.');
                };
            } else {
                $handler = function (...$args) use ($handler) {
                    return call_user_func_array($handler, $args);
                };
            }
        } elseif ($wrapWithErrorHandler) {
            $handler = function (...$args) {
                throw new \RuntimeException('This resource is disabled in ExpressionEngine settings. Please inform the user that this resource has been disabled in the ExpressionEngine control panel settings.');
            };
        }

        $annotationsObj = $this->toAnnotations($annotations);
        $template = new ResourceTemplate(
            $uriTemplate,
            $name ?? 'resource_template',
            $description,
            $mimeType,
            $annotationsObj
        );

        $guardedHandler = $this->withRuntimeGuards(
            $handler,
            'resource',
            $name ?? 'resource_template',
            $permissions,
            $wrapWithErrorHandler
        );

        $this->registry->registerResourceTemplate($template, $guardedHandler, $completionProviders, false);
    }

    /**
     * Register a prompt with the MCP server
     *
     * @param  callable|class-string  $handler  Prompt handler
     * @param  string|null  $name  Prompt name (optional, auto-detected from attributes)
     * @param  string|null  $description  Prompt description
     * @param  array|null  $annotations  MCP annotations
     * @param  bool  $wrapWithErrorHandler  If true, wrap handler to return disabled error
     */
    public function withPrompt(
        callable|string $handler,
        ?string $name = null,
        ?string $description = null,
        ?array $annotations = null,
        bool $wrapWithErrorHandler = false,
        ?array $permissions = null
    ): void {
        $handlerCompletionProviders = $this->extractCompletionProviders($handler);

        // Handle AbstractPrompt classes specially
        if (is_string($handler) && class_exists($handler)) {
            $reflection = new \ReflectionClass($handler);
            if ($reflection->isSubclassOf(\ExpressionEngine\Addons\Mcp\Support\AbstractPrompt::class)) {
                try {
                    $instance = new $handler();

                    // Get name from instance if not provided
                    if ($name === null && method_exists($instance, 'name')) {
                        $name = $instance->name();
                    }

                    // Get description from instance if not provided
                    if ($description === null && method_exists($instance, 'description')) {
                        $description = $instance->description();
                    }

                    // Get arguments schema from instance
                    $arguments = [];
                    $argumentsData = [];
                    if (method_exists($instance, 'arguments')) {
                        $argumentsData = $instance->arguments();
                        if (is_array($argumentsData)) {
                            // Convert arguments array to PromptArgument objects
                            foreach ($argumentsData as $argName => $argDef) {
                                $argDescription = $argDef['description'] ?? null;
                                $argRequired = isset($argDef['required']) && $argDef['required'] === true;

                                $arguments[] = new PromptArgument(
                                    name: $argName,
                                    description: $argDescription,
                                    required: $argRequired ? true : null // null means not required (default)
                                );
                            }
                        }
                    }

                    // Create Prompt schema
                    $prompt = new Prompt(
                        name: $name ?? 'prompt',
                        description: $description,
                        arguments: ! empty($arguments) ? $arguments : null
                    );

                    // Create wrapper closure that calls handle() with full
                    // prompt arguments extracted from request context.
                    $className = $handler;
                    if ($wrapWithErrorHandler) {
                        $wrapper = function (...$args) {
                            throw new \RuntimeException('This prompt is disabled in ExpressionEngine settings. Please inform the user that this prompt has been disabled in the ExpressionEngine control panel settings.');
                        };
                    } else {
                        $wrapper = function (RequestContext $context) use ($className) {
                            $instance = new $className();

                            return $instance->handle($this->extractArgumentsFromContext($context));
                        };
                    }

                    $guardedHandler = $this->withRuntimeGuards(
                        $wrapper,
                        'prompt',
                        $name ?? 'prompt',
                        $permissions,
                        $wrapWithErrorHandler
                    );

                    $completionProviders = array_merge(
                        $this->completionProvidersFromPromptArgumentDefinitions($argumentsData),
                        $handlerCompletionProviders
                    );

                    // Register via registry
                    $this->registry->registerPrompt($prompt, $guardedHandler, $completionProviders, true);

                    return;
                } catch (\Throwable $e) {
                    @error_log("[MCP Registrar] Error registering AbstractPrompt {$handler}: ".$e->getMessage());
                }
            }
        }

        // For other handlers, wrap with error if needed
        if ($wrapWithErrorHandler) {
            $handler = function (...$args) {
                throw new \RuntimeException('This prompt is disabled in ExpressionEngine settings. Please inform the user that this prompt has been disabled in the ExpressionEngine control panel settings.');
            };
        }

        // Create Prompt schema and register via registry
        try {
            $prompt = new Prompt(
                name: $name ?? 'prompt',
                description: $description,
                arguments: null
            );

            $guardedHandler = $this->withRuntimeGuards(
                $handler,
                'prompt',
                $name ?? 'prompt',
                $permissions,
                $wrapWithErrorHandler
            );

            $this->registry->registerPrompt($prompt, $guardedHandler, $handlerCompletionProviders, true);
        } catch (\Throwable $e) {
            @error_log("[MCP Registrar] Error registering prompt {$name}: ".$e->getMessage());
        }
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    private function toToolAnnotations(?array $annotations): ?ToolAnnotations
    {
        if ($annotations === null) {
            return null;
        }

        return ToolAnnotations::fromArray($annotations);
    }

    /**
     * Build ToolAnnotations from an AbstractTool instance's hint methods,
     * merged with any explicitly provided annotations array.
     *
     * Explicit annotations take precedence over instance-derived hints.
     *
     * @param  object  $instance  An AbstractTool instance
     * @param  array|null  $annotations  Explicitly provided annotation overrides
     */
    private function buildToolAnnotationsFromInstance(object $instance, ?array $annotations): ?ToolAnnotations
    {
        $hints = $annotations ?? [];

        if (! isset($hints['readOnlyHint']) && method_exists($instance, 'isReadOnly')) {
            $hints['readOnlyHint'] = $instance->isReadOnly();
        }

        if (! isset($hints['idempotentHint']) && method_exists($instance, 'isIdempotent')) {
            $hints['idempotentHint'] = $instance->isIdempotent();
        }

        if (! isset($hints['destructiveHint']) && method_exists($instance, 'isDestructive')) {
            $hints['destructiveHint'] = $instance->isDestructive();
        }

        if (! isset($hints['openWorldHint']) && method_exists($instance, 'isOpenWorld')) {
            $hints['openWorldHint'] = $instance->isOpenWorld();
        }

        if (empty($hints)) {
            return null;
        }

        return ToolAnnotations::fromArray($hints);
    }

    private function toAnnotations(?array $annotations): ?Annotations
    {
        if ($annotations === null) {
            return null;
        }

        return Annotations::fromArray($annotations);
    }

    /**
     * @return array<string, ProviderInterface|string>
     */
    private function extractCompletionProviders(callable|array|string $handler): array
    {
        $reflection = $this->reflectHandler($handler);
        if (! $reflection instanceof \ReflectionMethod && ! $reflection instanceof \ReflectionFunction) {
            return [];
        }

        $completionProviders = [];
        foreach ($reflection->getParameters() as $param) {
            $reflectionType = $param->getType();
            if ($reflectionType instanceof \ReflectionNamedType && ! $reflectionType->isBuiltin()) {
                continue;
            }

            $completionAttributes = $param->getAttributes(
                CompletionProviderAttribute::class,
                \ReflectionAttribute::IS_INSTANCEOF,
            );

            if (empty($completionAttributes)) {
                continue;
            }

            try {
                $attributeInstance = $completionAttributes[0]->newInstance();

                if ($attributeInstance->provider) {
                    $completionProviders[$param->getName()] = $attributeInstance->provider;
                } elseif ($attributeInstance->providerClass) {
                    $completionProviders[$param->getName()] = $attributeInstance->providerClass;
                } elseif ($attributeInstance->values) {
                    $completionProviders[$param->getName()] = new ListCompletionProvider(
                        array_map(static fn ($value) => (string) $value, $attributeInstance->values)
                    );
                } elseif ($attributeInstance->enum) {
                    $completionProviders[$param->getName()] = new EnumCompletionProvider($attributeInstance->enum);
                }
            } catch (\Throwable $e) {
                try {
                    $attributeArgs = $completionAttributes[0]->getArguments();

                    if (isset($attributeArgs['provider'])) {
                        $completionProviders[$param->getName()] = $attributeArgs['provider'];
                    } elseif (isset($attributeArgs['providerClass']) && is_string($attributeArgs['providerClass'])) {
                        $completionProviders[$param->getName()] = $attributeArgs['providerClass'];
                    } elseif (isset($attributeArgs['values']) && is_array($attributeArgs['values'])) {
                        $completionProviders[$param->getName()] = new ListCompletionProvider(
                            array_map(static fn ($value) => (string) $value, $attributeArgs['values'])
                        );
                    } elseif (isset($attributeArgs['enum']) && is_string($attributeArgs['enum'])) {
                        $completionProviders[$param->getName()] = new EnumCompletionProvider($attributeArgs['enum']);
                    }
                } catch (\Throwable $inner) {
                    @error_log('[MCP Registrar] Failed to parse completion provider: '.$inner->getMessage());
                }
            }
        }

        return $completionProviders;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, ProviderInterface|string>
     */
    private function completionProvidersFromPromptArgumentDefinitions(array $arguments): array
    {
        $completionProviders = [];

        foreach ($arguments as $argumentName => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (isset($definition['completionProvider']) && ($definition['completionProvider'] instanceof ProviderInterface || is_string($definition['completionProvider']))) {
                $completionProviders[$argumentName] = $definition['completionProvider'];

                continue;
            }

            if (! isset($definition['enum']) || ! is_array($definition['enum']) || empty($definition['enum'])) {
                continue;
            }

            $enumValues = [];
            foreach ($definition['enum'] as $value) {
                if (is_scalar($value)) {
                    $enumValues[] = (string) $value;
                }
            }

            if (! empty($enumValues)) {
                $completionProviders[$argumentName] = new ListCompletionProvider($enumValues);
            }
        }

        return $completionProviders;
    }

    private function reflectHandler(callable|array|string $handler): \ReflectionMethod|\ReflectionFunction|null
    {
        try {
            if ($handler instanceof \Closure) {
                return new \ReflectionFunction($handler);
            }

            if (is_array($handler) && count($handler) === 2) {
                return new \ReflectionMethod($handler[0], (string) $handler[1]);
            }

            if (is_string($handler) && str_contains($handler, '::')) {
                [$className, $methodName] = explode('::', $handler, 2);
                if ($className !== '' && $methodName !== '') {
                    return new \ReflectionMethod($className, $methodName);
                }
            }

            if (is_string($handler) && class_exists($handler) && method_exists($handler, '__invoke')) {
                return new \ReflectionMethod($handler, '__invoke');
            }

            if (is_string($handler) && function_exists($handler)) {
                return new \ReflectionFunction($handler);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Extract MCP arguments from request context for tool/prompt wrappers.
     *
     * @return array<string, mixed>
     */
    private function extractArgumentsFromContext(RequestContext $context): array
    {
        $request = $context->getRequest();

        if ($request instanceof CallToolRequest) {
            return $request->arguments;
        }

        if ($request instanceof GetPromptRequest) {
            return $request->arguments ?? [];
        }

        return [];
    }

    /**
     * @param  array<int, string>|null  $permissions
     */
    private function withRuntimeGuards(
        callable|array|string $handler,
        string $type,
        string $name,
        ?array $permissions,
        bool $wrapWithErrorHandler
    ): callable|array|string {
        // Disabled wrappers already throw the canonical disabled message.
        if ($wrapWithErrorHandler || empty($permissions)) {
            return $handler;
        }

        // If we cannot invoke through call_user_func_array, keep original handler.
        if (! is_array($handler) && ! is_callable($handler)) {
            return $handler;
        }

        $permissionEvaluator = $this->getPermissionEvaluator();

        return function (...$args) use ($handler, $type, $name, $permissionEvaluator, $permissions) {
            if (! $permissionEvaluator->isAllowed($permissions)) {
                throw new \RuntimeException(
                    "You do not have permission to use this {$type} ({$name}) based on EePermissions constraints."
                );
            }

            return call_user_func_array($handler, $args);
        };
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
     * Normalize JSON Schema properties to ensure they're valid objects
     * Converts empty arrays to stdClass() and removes null properties
     */
    private function normalizeJsonSchemaProperties(array $schema): array
    {
        if (isset($schema['properties'])) {
            if ($schema['properties'] === null) {
                // Remove null properties - JSON Schema doesn't allow null for properties
                unset($schema['properties']);
            } elseif (is_array($schema['properties'])) {
                // Check if it's a numeric array (invalid) vs associative array (valid)
                $keys = array_keys($schema['properties']);
                if (! empty($schema['properties']) && $keys === range(0, count($schema['properties']) - 1)) {
                    // It's a numeric array - this is invalid for JSON Schema properties
                    @error_log('[MCP Registrar] Warning: properties is a numeric array, this is invalid');
                    $schema['properties'] = new \stdClass();
                } elseif (empty($schema['properties'])) {
                    // Empty array must be converted to stdClass() so it serializes as {} not []
                    // This matches what Tool::fromArray() does
                    $schema['properties'] = new \stdClass();
                } else {
                    // Recursively normalize nested properties (for nested objects)
                    foreach ($schema['properties'] as $propKey => $propValue) {
                        if (is_array($propValue) && isset($propValue['type']) && $propValue['type'] === 'object') {
                            $schema['properties'][$propKey] = $this->normalizeJsonSchemaProperties($propValue);
                        }
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * Recursively convert SchemaBuilder objects to arrays
     *
     * @param  mixed  $data
     * @return mixed
     */
    private function convertSchemaBuildersToArrays($data)
    {
        if (is_object($data)) {
            // Check if it's a SchemaBuilder
            if ($data instanceof \ExpressionEngine\Addons\Mcp\Support\SchemaBuilder) {
                $array = $data->toArray();

                // Recursively convert nested SchemaBuilder objects
                return $this->convertSchemaBuildersToArrays($array);
            }
            // For other objects, try to convert to array if possible
            if (method_exists($data, 'toArray')) {
                return $this->convertSchemaBuildersToArrays($data->toArray());
            }

            return $data;
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $convertedValue = $this->convertSchemaBuildersToArrays($value);

                $result[$key] = $convertedValue;
            }

            return $result;
        }

        return $data;
    }
}
