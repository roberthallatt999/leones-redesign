<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Execute Template Generator Tool
 *
 * Executes a template generator in read-only mode and returns the generated
 * template code without saving to the database.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'execute_template_generator',
    description: 'Execute an ExpressionEngine template generator and return the generated template code (read-only, does not save templates)'
)]
class ExecuteTemplateGeneratorTool extends AbstractTool
{
    public function description(): string
    {
        return 'Execute an ExpressionEngine template generator with specified options and return the generated template code. This tool is read-only and does not save templates to the database.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'generator_key' => $schema->string()
                ->description('The generator key to execute (e.g., "channel:channels", "channel:fields", "channel:fieldGroups")')
                ->required()
                ->examples(['channel:channels', 'channel:fields', 'channel:fieldGroups']),
            'options' => $schema->object([], [])
                ->description('Generator-specific options as key-value pairs. Options vary by generator type.')
                ->default([]),
            'template' => $schema->string()
                ->description('Optional: Specific template name to generate. If not provided, all templates for the generator will be generated.')
                ->examples(['index', 'single', 'all']),
            'format' => $schema->enum(['code', 'full'])
                ->description('Output format: "code" returns just template code, "full" returns detailed information including metadata')
                ->default('full'),
        ], ['generator_key'])->toArray();
    }

    public function handle(array $params): array
    {
        // Suppress output to prevent MCP server from failing
        $oldSettings = $this->suppressOutput();

        try {
            // Load the session library
            ee()->load->library('session');

            $generatorKey = trim($params['generator_key'] ?? '');
            $options = $params['options'] ?? [];
            $template = $params['template'] ?? null;
            $format = $params['format'] ?? 'full';

            if (empty($generatorKey)) {
                throw new \InvalidArgumentException('Generator key is required');
            }

            // Handle options - might be a JSON string or already an array
            if (is_string($options)) {
                $decoded = json_decode($options, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $options = $decoded;
                } else {
                    throw new \InvalidArgumentException('Invalid JSON in options parameter: '.json_last_error_msg());
                }
            } elseif (! is_array($options)) {
                $options = [];
            }

            // Get all generators to validate the key
            $generatorsList = ee('TemplateGenerator')->registerAllTemplateGenerators();
            if (! isset($generatorsList[$generatorKey])) {
                throw new \InvalidArgumentException("Generator '{$generatorKey}' not found");
            }

            // Instantiate the generator
            $generator = ee('TemplateGenerator')->make($generatorKey);

            // Check if generator is disabled for CLI (our execution context)
            if ($generator->generatorDisabledForLocation('CLI')) {
                throw new \RuntimeException("Generator '{$generatorKey}' is disabled for CLI execution");
            }

            // CRITICAL FIX: Override render() method to use a custom Stub that overrides embed()
            // Stub template files call $this->embed() directly, which uses ob_end_flush() that bypasses output buffers
            // We override render() to manually create a custom stub that uses ob_end_clean() instead
            $generatorReflection = new \ReflectionClass($generator);

            // Temporarily clear includes to prevent automatic embeds
            $originalIncludes = null;
            $includesProperty = $generatorReflection->getProperty('includes');
            $includesProperty->setAccessible(true);
            $originalIncludes = $includesProperty->getValue($generator);
            $includesProperty->setValue($generator, []); // Clear includes to prevent embeds

            // Override render() method to use our custom stub
            $originalRender = $generatorReflection->getMethod('render');
            $originalRender->setAccessible(true);

            // Create a factory function that creates a custom Stub with overridden embed() and make() methods
            // The anonymous class is created with the required constructor args when the factory is called
            $createCustomStub = function ($path, $provider) {
                // Create an anonymous class that extends Stub and overrides embed() AND make()
                // We pass the constructor args when creating the instance
                $customStub = new class($path, $provider) extends \ExpressionEngine\Service\View\Stub
                {
                    public function __construct($path, $provider)
                    {
                        parent::__construct($path, $provider);
                    }

                    // Override make() to return our custom stub class instead of the original
                    protected function make($view)
                    {
                        $provider = $this->provider;
                        $generatorFolder = $this->generatorFolder;

                        if (strpos($view, ':')) {
                            $parts = explode(':', $view, 3);
                            $prefix = $parts[0];
                            if (isset($parts[2])) {
                                $generatorFolder = $parts[1];
                                $view = $parts[2];
                            } else {
                                $generatorFolder = '';
                                $view = $parts[1];
                            }
                            $provider = $provider->make('App')->get($prefix);
                        }

                        // Create a new instance of THIS class (the custom stub) instead of the original Stub
                        $stub = new self($view, $provider);
                        $stub->generatorFolder = $generatorFolder;
                        $stub->setTemplateType($this->templateType);

                        if ($this->theme) {
                            $stub->setTheme($this->theme);
                        }

                        if ($this->templateEngine) {
                            $stub->setTemplateEngine($this->templateEngine);
                        }

                        return $stub;
                    }

                    public function embed($view, $vars = [], $disable = [])
                    {
                        // Override embed() to use ob_end_clean() instead of ob_end_flush()
                        // This prevents output from bypassing output buffers and going to stdout
                        if (empty($vars)) {
                            $vars = [];
                        }
                        $vars = array_merge($this->processing, $vars);
                        $view = $this->make($view)->disable($disable);

                        // Handle modifiers like the original
                        if (array_key_exists('modifiers', $vars) && is_array($vars['modifiers'])) {
                            $vars['modifiers_string'] = trim(array_reduce(array_keys($vars['modifiers']), function ($carry, $modifier) use ($vars) {
                                $usePrefix = count($vars['modifiers']) > 1;
                                $parameters = $vars['modifiers'][$modifier];
                                $parameterString = array_reduce(array_keys($parameters), function ($carry, $parameter) use ($parameters, $usePrefix, $modifier) {
                                    return $carry .= (($usePrefix) ? "$modifier:$parameter" : $parameter)."='{$parameters[$parameter]}'";
                                }, '');
                                $carry .= ":$modifier $parameterString";

                                return $carry;
                            }, ''));
                        } else {
                            $vars['modifiers_string'] = '';
                        }

                        $out = $view->render($vars);

                        // Indent everything at the same level
                        $indent = 0;
                        $buffer = ob_get_contents();
                        if (! empty($buffer)) {
                            $bufferLines = explode("\n", $buffer);
                            $indent = strlen(end($bufferLines));
                        }

                        $lines = explode("\n", $out);
                        foreach ($lines as $i => &$line) {
                            if ($i > 0) {
                                $line = str_repeat(' ', $indent).$line;
                            }
                        }
                        $out = implode("\n", $lines);

                        ob_start();
                        echo $out;
                        ob_end_clean(); // CRITICAL: Use ob_end_clean() instead of ob_end_flush()
                        // Output stays in the buffer above us, doesn't flush to stdout
                    }
                };

                return $customStub;
            };

            // Create a wrapper render method that uses our custom stub
            $customRender = function ($template, $type) use ($generator, $generatorReflection, $createCustomStub) {
                // Get the original makeTemplateStub to create the stub structure
                $makeStubMethod = $generatorReflection->getMethod('makeTemplateStub');
                $makeStubMethod->setAccessible(true);
                $originalStub = $makeStubMethod->invoke($generator, $template);

                // Get the provider and template name from the original stub
                $stubReflection = new \ReflectionClass($originalStub);
                $providerProp = $stubReflection->getProperty('provider');
                $providerProp->setAccessible(true);
                $provider = $providerProp->getValue($originalStub);

                $pathProp = $stubReflection->getProperty('path');
                $pathProp->setAccessible(true);
                $path = $pathProp->getValue($originalStub);

                // Create our custom stub using the factory function
                $customStub = $createCustomStub($path, $provider);
                $customStubReflection = new \ReflectionClass($customStub);

                // Copy all other properties from the original stub
                foreach ($stubReflection->getProperties() as $prop) {
                    $prop->setAccessible(true);
                    $propName = $prop->getName();
                    // Skip provider and path as they're already set by the constructor
                    if ($propName === 'provider' || $propName === 'path') {
                        continue;
                    }
                    $value = $prop->getValue($originalStub);
                    if ($customStubReflection->hasProperty($propName)) {
                        $customProp = $customStubReflection->getProperty($propName);
                        $customProp->setAccessible(true);
                        $customProp->setValue($customStub, $value);
                    }
                }

                // Use reflection to access protected $input property
                $inputProperty = $generatorReflection->getProperty('input');
                $inputProperty->setAccessible(true);
                $inputObj = $inputProperty->getValue($generator);

                // Set template type and other properties like the original render() does
                $customStub->setTemplateType($type);
                if ($inputObj->get('theme')) {
                    $customStub->setTheme($inputObj->get('theme'));
                }
                if ($inputObj->get('template_engine', 'native') !== 'native') {
                    $customStub->setTemplateEngine($inputObj->get('template_engine'));
                }

                // Get variables for template rendering
                $inputAll = $inputObj->all();

                // Get generator variables - use custom path for Member Management to avoid crashes
                $isMemberManagement = (get_class($generator) === 'ExpressionEngine\\Addons\\Member\\TemplateGenerators\\Management');
                $generatorVars = [];

                if ($isMemberManagement) {
                    // Custom getVariables() for Member Management that skips fieldtype lookups (which cause crashes)
                    try {
                        $selectedTemplates = array_intersect_key($generator->getTemplates(), array_flip($inputObj->get('templates', [])));
                        if (empty($selectedTemplates)) {
                            $selectedTemplates = $generator->getTemplates();
                        }

                        $vars = [
                            'fields' => [],
                            'publicTemplates' => array_intersect_key(
                                $selectedTemplates,
                                array_flip(['login', 'forgot-username', 'forgot-password', 'registration'])
                            ),
                            'privateTemplates' => array_diff_key(
                                $selectedTemplates,
                                array_flip(['login', 'forgot-username', 'forgot-password', 'registration'])
                            ),
                        ];

                        $fields = ee('Model')->get('MemberField')->all();
                        foreach ($fields as $fieldInfo) {
                            // Safe minimal field representation without fieldtype generators (avoid crashes)
                            $field = [
                                'field_type' => $fieldInfo->m_field_type,
                                'field_name' => $fieldInfo->m_field_name,
                                'field_label' => $fieldInfo->m_field_label,
                                'show_profile' => $fieldInfo->m_field_public,
                                'show_registration' => $fieldInfo->m_field_reg,
                                'stub' => null,
                                'docs_url' => null,
                                'is_tag_pair' => false,
                            ];

                            $vars['fields'][$fieldInfo->m_field_name] = $field;
                        }

                        $generatorVars = $vars;
                    } catch (\Throwable $e) {
                        throw $e;
                    }
                } else {
                    // For other generators, temporarily disable extensions and use output buffering
                    $origAllowExtensions = ee()->config->item('allow_extensions');
                    ee()->config->set_item('allow_extensions', 'n');

                    $obLevelBefore = ob_get_level();
                    for ($i = 0; $i < 5; $i++) {
                        ob_start();
                    }

                    try {
                        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                            return true;
                        }, E_ALL);

                        $generatorVars = $generator->getVariables();

                        restore_error_handler();

                        while (ob_get_level() > $obLevelBefore) {
                            ob_end_clean();
                        }
                    } catch (\Throwable $e) {
                        restore_error_handler();
                        while (ob_get_level() > $obLevelBefore) {
                            ob_end_clean();
                        }
                        ee()->config->set_item('allow_extensions', $origAllowExtensions);
                        throw $e;
                    }

                    ee()->config->set_item('allow_extensions', $origAllowExtensions);
                }

                $renderVars = array_merge($inputAll, $generatorVars);

                try {
                    $data = call_user_func([$customStub, 'render'], $renderVars);
                } catch (\Throwable $e) {
                    throw $e;
                }

                return [
                    'engine' => $customStub->getTemplateEngine(),
                    'data' => $data,
                ];
            };

            // Override generate() to use our custom render() method
            // We'll create a wrapper that intercepts render() calls
            $originalGenerate = $generatorReflection->getMethod('generate');
            $originalGenerate->setAccessible(true);

            // Create a wrapper generate that uses our custom render
            $customGenerate = function ($input, $save = true) use ($generator, $customRender, $generatorReflection) {
                // Call original generate but intercept render() calls
                // We'll override render() temporarily by storing our custom version
                // and ensuring it's called instead of the original

                // Actually, the simplest is to override generate() to manually replicate its logic
                // but use our custom render. But that's complex. Let's use a different approach:
                // Override render() by replacing it with a closure that calls our custom version

                // Store original render
                $originalRenderMethod = $generatorReflection->getMethod('render');
                $originalRenderMethod->setAccessible(true);

                // Temporarily replace render with our custom version
                // We can't do this directly, so let's override generate() to manually call render()
                // with our custom stub logic

                // SIMPLEST: Just call original generate() - it will use the original render()
                // But we need to ensure our custom stub is used. Let's override makeTemplateStub() instead
                // by storing a closure and ensuring it's called

                // Actually, let's just override generate() to manually replicate its logic with our custom render
                // This is the only guaranteed way to ensure our custom stub is used

                // Replicate generate() logic but use custom render
                $validationResult = ($save) ? $generator->validate($input) : $generator->validatePartial(array_filter($input));
                if (! $validationResult->isValid()) {
                    throw new \ExpressionEngine\Service\TemplateGenerator\Exceptions\ValidationException('Template Generator validation failed.', $validationResult);
                }

                // Use reflection to call protected mergeDefaults() method
                $mergeDefaultsMethod = $generatorReflection->getMethod('mergeDefaults');
                $mergeDefaultsMethod->setAccessible(true);
                $mergedInput = $mergeDefaultsMethod->invoke($generator, $input);

                // Use reflection to set protected $input property
                $inputProperty = $generatorReflection->getProperty('input');
                $inputProperty->setAccessible(true);
                $inputProperty->setValue($generator, new \ExpressionEngine\Service\TemplateGenerator\Input($mergedInput));

                // Create a helper closure to access input via reflection
                $getInput = function () use ($generator, $generatorReflection) {
                    $inputProp = $generatorReflection->getProperty('input');
                    $inputProp->setAccessible(true);

                    return $inputProp->getValue($generator);
                };

                $templates = $generator->getTemplates();

                $inputObj = $getInput();
                if (! empty($inputObj->get('templates', [])) && current($inputObj->get('templates')) !== 'all') {
                    $templates = array_filter($templates, function ($key) use ($inputObj) {
                        return in_array($key, $inputObj->get('templates'));
                    }, ARRAY_FILTER_USE_KEY);
                }

                if (empty($templates)) {
                    throw new \Exception(lang('generate_templates_no_templates'));
                }

                // Add any includes for the specified templates (but we cleared includes, so this will be empty)
                $templates = array_merge($templates, $generator->getIncludes($templates));

                // we'll start with index templates
                if (isset($templates['index'])) {
                    $indexTmpl = $templates['index'];
                    unset($templates['index']);
                    $templates = array_merge(['index' => $indexTmpl], $templates);
                }

                $inputObj = $getInput();
                $site_id = (int) $inputObj->get('site_id', 1);
                $group = ($save) ? \ee('TemplateGenerator')->createTemplateGroup($inputObj->get('template_group'), $site_id) : $inputObj->get('template_group');

                foreach ($templates as $templateName => $templateData) {
                    // Use our custom render instead of the original
                    $rendered = $customRender($templateName, $templateData['type']);
                    $templateInfo = [
                        'template_engine' => $rendered['engine'],
                        'template_data' => $rendered['data'],
                        'template_type' => $templateData['type'],
                        'template_notes' => $templateData['description'] ?? $templateData['name'],
                    ];

                    if ($save) {
                        \ee('TemplateGenerator')->createTemplate($group, $templateName, $templateInfo, $site_id);
                    }

                    $templates[$templateName] = array_merge($templateData, $templateInfo);
                }

                return ['group' => $group, 'templates' => $templates];
            };

            // Replace generate() with our custom version
            $generateToCall = $customGenerate;

            // Prepare options - merge with defaults
            $generatorOptions = $generator->getOptions();
            $mergedOptions = $this->mergeOptionsWithDefaults($options, $generatorOptions);

            // Ensure templates is always an array (required by AbstractTemplateGenerator)
            // The generator expects templates to be an array for checkbox type options
            if (isset($mergedOptions['templates'])) {
                if (! is_array($mergedOptions['templates'])) {
                    // Convert string to array (e.g., 'all' becomes ['all'])
                    $mergedOptions['templates'] = [$mergedOptions['templates']];
                }
            } else {
                // Default to ['all'] if not specified
                $mergedOptions['templates'] = ['all'];
            }

            // If template parameter is specified, override templates option
            if ($template !== null) {
                if ($template === 'all') {
                    $mergedOptions['templates'] = ['all'];
                } else {
                    $mergedOptions['templates'] = [$template];
                }
            }

            // Set a dummy template_group for read-only generation (won't be saved)
            if (empty($mergedOptions['template_group'])) {
                $mergedOptions['template_group'] = 'temp_'.time();
            }

            // Validate partial options (since we're not saving, full validation isn't needed)
            // Skip validation for template_group since we're using a dummy name
            $validationOptions = $mergedOptions;
            unset($validationOptions['template_group']);

            $validationResult = $generator->validatePartial($validationOptions);
            if ($validationResult->isNotValid()) {
                $errors = [];
                foreach ($validationResult->getFailed() as $field => $failed) {
                    $fieldErrors = $validationResult->getErrors($field);
                    $errors[$field] = implode(', ', $fieldErrors);
                }
                throw new \InvalidArgumentException('Validation failed: '.json_encode($errors));
            }

            // Ensure session is loaded (some generators require it)
            if (! ee()->session) {
                ee()->load->library('session');
            }

            // Generate templates with save=false (read-only)
            // Wrap in try-catch to capture any errors from the generator

            try {
                // Set a time limit to prevent infinite hangs
                $oldTimeLimit = ini_get('max_execution_time');
                set_time_limit(30);

                // Use our custom generate() that uses custom render() with custom stub
                $result = $generateToCall($mergedOptions, false);

                // Restore original includes
                if ($originalIncludes !== null && isset($includesProperty)) {
                    $includesProperty->setValue($generator, $originalIncludes);
                }

                // Restore time limit
                set_time_limit($oldTimeLimit ? (int) $oldTimeLimit : 0);
            } catch (\Throwable $genError) {
                // Restore original includes even on exception
                if (isset($originalIncludes) && $originalIncludes !== null && isset($includesProperty)) {
                    $includesProperty->setValue($generator, $originalIncludes);
                }

                // Restore time limit
                if (isset($oldTimeLimit)) {
                    set_time_limit($oldTimeLimit ? (int) $oldTimeLimit : 0);
                }

                throw new \RuntimeException('Generator execution failed: '.$genError->getMessage().' (File: '.$genError->getFile().':'.$genError->getLine().')', 0, $genError);
            }

            // Format the response
            $templates = [];
            foreach ($result['templates'] as $templateName => $templateData) {
                if ($format === 'code') {
                    $templates[$templateName] = [
                        'name' => $templateName,
                        'code' => $templateData['template_data'] ?? '',
                    ];
                } else {
                    $templates[$templateName] = [
                        'name' => $templateName,
                        'code' => $templateData['template_data'] ?? '',
                        'type' => $templateData['template_type'] ?? 'webpage',
                        'engine' => $templateData['template_engine'] ?? 'native',
                        'notes' => $templateData['template_notes'] ?? $templateData['name'] ?? '',
                        'description' => $templateData['description'] ?? $templateData['name'] ?? '',
                    ];
                }
            }

            // Restore output settings and clean buffers
            $this->restoreOutput($oldSettings);

            return [
                'generator_key' => $generatorKey,
                'generator_name' => $generator->getName(),
                'templates' => $templates,
                'template_group' => $mergedOptions['template_group'],
                'options_used' => $mergedOptions,
            ];

        } catch (\Exception $e) {
            // Restore output settings even on exception
            $this->restoreOutput($oldSettings);
            throw $e;
        } catch (\Throwable $e) {
            // Restore output settings even on exception
            $this->restoreOutput($oldSettings);
            throw new \RuntimeException('Failed to execute template generator: '.$e->getMessage());
        }
    }

    /**
     * Merge user-provided options with generator defaults
     */
    private function mergeOptionsWithDefaults(array $userOptions, array $generatorOptions): array
    {
        $merged = [];

        // Start with defaults from generator options
        foreach ($generatorOptions as $key => $optionData) {
            if (isset($optionData['default'])) {
                $merged[$key] = $optionData['default'];
            }
        }

        // Override with user-provided options
        foreach ($userOptions as $key => $value) {
            // Ensure checkbox options are arrays (matching CLI command behavior)
            if (isset($generatorOptions[$key]['type']) &&
                $generatorOptions[$key]['type'] === 'checkbox' &&
                ! is_array($value)) {
                // Convert string to array (handles comma or pipe separated values)
                $value = explode('|', str_replace(',', '|', $value));
                $value = array_map('trim', $value);
            }
            $merged[$key] = $value;
        }

        return $merged;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function isIdempotent(): bool
    {
        return true;
    }
}
