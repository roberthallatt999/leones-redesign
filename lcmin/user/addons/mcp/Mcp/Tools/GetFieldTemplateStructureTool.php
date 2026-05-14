<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\FieldTemplateStructureService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Get Field Template Structure Tool
 *
 * Returns exact field template scaffold from EE CLI template generator.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'get_field_template_structure',
    description: 'Get exact template scaffold code for an ExpressionEngine field using the CLI template generator. Best for complex field types like Grid and Fluid.'
)]
class GetFieldTemplateStructureTool extends AbstractTool
{
    public function description(): string
    {
        return 'Generate exact template scaffold code for a specific field via EE CLI (`generate:templates channel:fields`). Use this when writing template syntax for complex fields (Grid/Fluid/Matrix/File/Relationship/RTE).';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'field_id' => $schema->integer()
                ->description('Field ID to generate structure for (alternative to field_name)'),
            'field_name' => $schema->string()
                ->description('Field short name to generate structure for (alternative to field_id)'),
            'template' => $schema->string()
                ->description('Template variant to generate (usually "index")')
                ->default('index'),
            'site_id' => $schema->integer()
                ->description('Optional site ID override'),
            'format' => $schema->enum(['code', 'full'])
                ->description('Response format: "code" for scaffold only, "full" for scaffold + metadata and guidance')
                ->default('full'),
        ], [])->toArray();
    }

    public function handle(array $arguments): array
    {
        $oldSettings = $this->suppressOutput();

        try {
            $fieldId = isset($arguments['field_id']) ? (int) $arguments['field_id'] : null;
            $fieldName = isset($arguments['field_name']) ? trim((string) $arguments['field_name']) : null;
            $template = isset($arguments['template']) ? trim((string) $arguments['template']) : 'index';
            $siteId = isset($arguments['site_id']) ? (int) $arguments['site_id'] : null;
            $format = isset($arguments['format']) ? (string) $arguments['format'] : 'full';

            if (! $fieldId && (! $fieldName || $fieldName === '')) {
                throw new \InvalidArgumentException('Either field_id or field_name is required.');
            }

            $service = new FieldTemplateStructureService();
            if ($fieldId) {
                $result = $service->generateForFieldId($fieldId, $template, $siteId);
            } else {
                $result = $service->generateForFieldName($fieldName, $template, $siteId);
            }

            $this->restoreOutput($oldSettings);

            if ($format === 'code') {
                return [
                    'field_name' => $result['field']['field_name'] ?? $fieldName,
                    'field_type' => $result['field']['field_type'] ?? null,
                    'template' => $result['template'] ?? $template,
                    'template_code' => $result['template_code'] ?? '',
                ];
            }

            return [
                'field' => $result['field'],
                'template' => $result['template'],
                'template_code' => $result['template_code'],
                'is_complex_field' => $result['is_complex_field'],
                'recommended_for' => $result['recommended_for'],
                'when_to_use' => 'Use this tool whenever field template syntax is uncertain. It returns the exact scaffold produced by EE CLI and prevents hand-written syntax errors.',
                'command' => $result['command'],
                'generated_at' => $result['generated_at'],
            ];
        } catch (\Throwable $e) {
            $this->restoreOutput($oldSettings);
            throw $e;
        }
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
