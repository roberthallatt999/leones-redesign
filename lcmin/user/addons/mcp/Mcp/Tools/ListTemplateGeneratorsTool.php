<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * List Template Generators Tool
 *
 * Lists all available template generators with their metadata including
 * options, templates, validation rules, and disabled status.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'list_template_generators',
    description: 'List all available ExpressionEngine template generators with full metadata (options, templates, validation rules, etc.)'
)]
class ListTemplateGeneratorsTool extends AbstractTool
{
    public function description(): string
    {
        return 'List all available ExpressionEngine template generators with full metadata including options, templates, validation rules, and disabled status flags.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'generator_key' => $schema->string()
                ->description('Optional: Filter to a specific generator by key (e.g., "channel:channels", "channel:fields")')
                ->examples(['channel:channels', 'channel:fields', 'channel:fieldGroups']),
            'include_templates' => $schema->boolean()
                ->description('Whether to include template definitions in the response')
                ->default(true),
        ], [])->toArray();
    }

    public function handle(array $params): array
    {
        // Suppress any output to prevent MCP server issues
        ob_start();

        try {
            // Load the session library
            ee()->load->library('session');

            $generatorKey = $params['generator_key'] ?? null;
            $includeTemplates = $params['include_templates'] ?? true;

            // Get all template generators
            $generatorsList = ee('TemplateGenerator')->registerAllTemplateGenerators();

            if ($generatorKey !== null) {
                // Filter to specific generator
                if (! isset($generatorsList[$generatorKey])) {
                    throw new \InvalidArgumentException("Generator '{$generatorKey}' not found");
                }
                $generatorsList = [$generatorKey => $generatorsList[$generatorKey]];
            }

            $result = [
                'generators' => [],
                'total_generators' => 0,
                'generated_at' => date('Y-m-d H:i:s'),
            ];

            foreach ($generatorsList as $key => $generator) {
                try {
                    $generatorData = $this->buildGeneratorData($key, $generator, $includeTemplates);
                    $result['generators'][$key] = $generatorData;
                    $result['total_generators']++;
                } catch (\Exception $e) {
                    // Include error info but continue processing other generators
                    $result['generators'][$key] = [
                        'key' => $key,
                        'name' => method_exists($generator, 'getName') ? $generator->getName() : $key,
                        'error' => 'Could not load generator: '.$e->getMessage(),
                        'templates' => [],
                        'options' => [],
                        'validation_rules' => [],
                        'is_disabled_for_cp' => false,
                        'is_disabled_for_cli' => false,
                    ];
                }
            }

            ob_end_clean();

            return $result;

        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException('Failed to list template generators: '.$e->getMessage());
        }
    }

    /**
     * Build generator data for output
     *
     * @param  mixed  $generator
     */
    private function buildGeneratorData(string $generatorKey, $generator, bool $includeTemplates): array
    {
        // Get generator instance to access its methods
        $generatorInstance = ee('TemplateGenerator')->make($generatorKey);

        $data = [
            'key' => $generatorKey,
            'name' => $generatorInstance->getName(),
            'options' => $this->formatGeneratorOptions($generatorInstance->getOptions()),
            'validation_rules' => $generatorInstance->getValidationRules(),
            'is_disabled_for_cp' => $generatorInstance->generatorDisabledForLocation('CP'),
            'is_disabled_for_cli' => $generatorInstance->generatorDisabledForLocation('CLI'),
        ];

        if ($includeTemplates) {
            $data['templates'] = $generatorInstance->getTemplates();
        }

        return $data;
    }

    /**
     * Format generator options for output
     */
    private function formatGeneratorOptions(array $options): array
    {
        $formattedOptions = [];

        foreach ($options as $optionKey => $optionData) {
            $formattedOption = [
                'key' => $optionKey,
                'type' => $optionData['type'] ?? 'text',
                'description' => isset($optionData['desc']) ? lang($optionData['desc']) : $optionKey,
                'required' => $optionData['required'] ?? false,
                'default' => $optionData['default'] ?? null,
            ];

            // Handle choices/options for select, radio, checkbox types
            if (isset($optionData['choices'])) {
                if (is_array($optionData['choices'])) {
                    $formattedOption['choices'] = $optionData['choices'];
                } else {
                    // If choices is a method name, indicate that choices are available
                    $formattedOption['choices_method'] = $optionData['choices'];
                    $formattedOption['choices'] = [];
                }
            }

            // Add any additional properties
            foreach ($optionData as $key => $value) {
                if (! in_array($key, ['type', 'desc', 'required', 'default', 'choices'])) {
                    $formattedOption[$key] = $value;
                }
            }

            $formattedOptions[$optionKey] = $formattedOption;
        }

        return $formattedOptions;
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
