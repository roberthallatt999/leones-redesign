<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\FieldTemplateStructureService;
use ExpressionEngine\Addons\Mcp\Services\TemplateTagSyntaxService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Explain Field Syntax Tool
 *
 * Explains expected template syntax patterns for an EE field type.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'explain_field_syntax',
    description: 'Explain template syntax expectations and common mistakes for a specific ExpressionEngine field type.'
)]
class ExplainFieldSyntaxTool extends AbstractTool
{
    public function description(): string
    {
        return 'Explain how to write template syntax for a field, including complex-field tag-pair rules, common mistakes, and optional generated scaffold.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'field_id' => $schema->integer()
                ->description('Field ID to explain (alternative to field_name)'),
            'field_name' => $schema->string()
                ->description('Field short name to explain (alternative to field_id)'),
            'site_id' => $schema->integer()
                ->description('Optional site ID override for field lookup'),
            'template' => $schema->string()
                ->description('Template variant used when generating scaffold')
                ->default('index'),
            'include_scaffold' => $schema->boolean()
                ->description('Include generated scaffold in response')
                ->default(true),
        ], [])->toArray();
    }

    public function handle(array $arguments): array
    {
        $oldSettings = $this->suppressOutput();

        try {
            $fieldId = isset($arguments['field_id']) ? (int) $arguments['field_id'] : null;
            $fieldName = isset($arguments['field_name']) ? trim((string) $arguments['field_name']) : null;
            $siteId = isset($arguments['site_id']) ? (int) $arguments['site_id'] : null;
            $template = isset($arguments['template']) ? trim((string) $arguments['template']) : 'index';
            $includeScaffold = isset($arguments['include_scaffold']) ? (bool) $arguments['include_scaffold'] : true;

            if (! $fieldId && (! $fieldName || $fieldName === '')) {
                throw new \InvalidArgumentException('Either field_id or field_name is required.');
            }

            $field = $this->getField($fieldId, $fieldName, $siteId);
            if (! $field) {
                $identifier = $fieldId ? "ID {$fieldId}" : "name '{$fieldName}'";
                throw new \RuntimeException("Field with {$identifier} not found.");
            }

            $syntaxService = new TemplateTagSyntaxService();
            $explanation = $syntaxService->explainFieldSyntax((string) $field->field_name, (string) $field->field_type);

            $response = [
                'field' => [
                    'field_id' => (int) $field->field_id,
                    'field_name' => (string) $field->field_name,
                    'field_label' => (string) $field->field_label,
                    'field_type' => (string) $field->field_type,
                    'site_id' => (int) $field->site_id,
                ],
                'syntax_style' => $explanation['syntax_style'],
                'key_rules' => $explanation['key_rules'],
                'common_mistakes' => $explanation['common_mistakes'],
                'recommended_workflow' => [
                    'Start with get_field_template_structure to get exact scaffold.',
                    'Use validate_template_tags after edits to catch tag-pair mistakes.',
                    'Use fix_template_snippet for quick safe repairs.',
                ],
                'explained_at' => date('c'),
            ];

            if ($includeScaffold) {
                try {
                    $structureService = new FieldTemplateStructureService();
                    $structure = $structureService->generateForFieldId((int) $field->field_id, $template ?: 'index', $siteId);
                    $response['scaffold'] = $structure['template_code'] ?? null;
                } catch (\Throwable $e) {
                    $response['scaffold'] = null;
                    $response['scaffold_error'] = $e->getMessage();
                }
            }

            $this->restoreOutput($oldSettings);

            return $response;
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

    private function getField(?int $fieldId, ?string $fieldName, ?int $siteId = null)
    {
        $query = ee('Model')->get('ChannelField');

        if ($fieldId) {
            $query->filter('field_id', $fieldId);
        } elseif ($fieldName) {
            $query->filter('field_name', $fieldName);
        }

        if ($siteId !== null) {
            $query->filter('site_id', $siteId);
        }

        return $query->first();
    }
}
