<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\FieldTemplateStructureService;
use ExpressionEngine\Addons\Mcp\Services\TemplateTagSyntaxService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Validate Template Tags Tool
 *
 * Validates EE field template snippets and returns actionable syntax guidance.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'validate_template_tags',
    description: 'Validate ExpressionEngine field template snippet syntax and detect common tag-pair mistakes for complex field types.'
)]
class ValidateTemplateTagsTool extends AbstractTool
{
    public function description(): string
    {
        return 'Validate ExpressionEngine field template snippets, with targeted checks for complex field tag-pair syntax (Grid/Fluid/Relationship/File/RTE/etc.).';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'snippet' => $schema->string()
                ->description('Template snippet to validate')
                ->required(),
            'field_id' => $schema->integer()
                ->description('Field ID to validate against (alternative to field_name)'),
            'field_name' => $schema->string()
                ->description('Field short name to validate against (alternative to field_id)'),
            'site_id' => $schema->integer()
                ->description('Optional site ID override for field lookup'),
            'template' => $schema->string()
                ->description('Template variant used when generating scaffold')
                ->default('index'),
            'include_scaffold' => $schema->boolean()
                ->description('Include generated scaffold in response for side-by-side comparison')
                ->default(true),
        ], ['snippet'])->toArray();
    }

    public function handle(array $arguments): array
    {
        $oldSettings = $this->suppressOutput();

        try {
            $snippet = (string) ($arguments['snippet'] ?? '');
            $fieldId = isset($arguments['field_id']) ? (int) $arguments['field_id'] : null;
            $fieldName = isset($arguments['field_name']) ? trim((string) $arguments['field_name']) : null;
            $siteId = isset($arguments['site_id']) ? (int) $arguments['site_id'] : null;
            $template = isset($arguments['template']) ? trim((string) $arguments['template']) : 'index';
            $includeScaffold = isset($arguments['include_scaffold']) ? (bool) $arguments['include_scaffold'] : true;

            if (trim($snippet) === '') {
                throw new \InvalidArgumentException('snippet is required.');
            }

            if (! $fieldId && (! $fieldName || $fieldName === '')) {
                throw new \InvalidArgumentException('Either field_id or field_name is required.');
            }

            $field = $this->getField($fieldId, $fieldName, $siteId);
            if (! $field) {
                $identifier = $fieldId ? "ID {$fieldId}" : "name '{$fieldName}'";
                throw new \RuntimeException("Field with {$identifier} not found.");
            }

            $syntaxService = new TemplateTagSyntaxService();
            $expectTagPair = $syntaxService->isComplexFieldType((string) $field->field_type);

            $analysis = $syntaxService->analyzeSnippet($snippet, (string) $field->field_name, $expectTagPair);

            $scaffold = null;
            $scaffoldError = null;
            if ($includeScaffold) {
                try {
                    $structureService = new FieldTemplateStructureService();
                    $result = $structureService->generateForFieldId((int) $field->field_id, $template ?: 'index', $siteId);
                    $scaffold = $result['template_code'] ?? null;
                } catch (\Throwable $e) {
                    $scaffoldError = $e->getMessage();
                }
            }

            if (! $analysis['valid'] && is_string($scaffold) && trim($scaffold) !== '') {
                $analysis['suggestions'][] = 'Use the generated scaffold to correct syntax mismatches.';
                $analysis['suggestions'] = array_values(array_unique($analysis['suggestions']));
            }

            $response = [
                'valid' => $analysis['valid'],
                'errors' => $analysis['errors'],
                'warnings' => $analysis['warnings'],
                'suggestions' => $analysis['suggestions'],
                'metrics' => $analysis['metrics'],
                'field' => [
                    'field_id' => (int) $field->field_id,
                    'field_name' => (string) $field->field_name,
                    'field_label' => (string) $field->field_label,
                    'field_type' => (string) $field->field_type,
                    'site_id' => (int) $field->site_id,
                ],
                'expected_syntax' => $expectTagPair ? 'tag_pair_preferred' : 'single_tag_preferred',
                'validated_at' => date('c'),
            ];

            if ($includeScaffold) {
                $response['scaffold'] = $scaffold;
                if ($scaffoldError !== null) {
                    $response['scaffold_error'] = $scaffoldError;
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
