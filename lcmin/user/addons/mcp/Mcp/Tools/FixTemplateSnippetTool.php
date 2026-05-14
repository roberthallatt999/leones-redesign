<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Services\FieldTemplateStructureService;
use ExpressionEngine\Addons\Mcp\Services\TemplateTagSyntaxService;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Fix Template Snippet Tool
 *
 * Applies safe repair heuristics to field template snippets.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'fix_template_snippet',
    description: 'Fix ExpressionEngine field template snippets using safe repairs or exact scaffold replacement.'
)]
class FixTemplateSnippetTool extends AbstractTool
{
    public function description(): string
    {
        return 'Fix field template snippet syntax using conservative repairs by default, with an option to replace with generated scaffold for exact EE syntax.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'snippet' => $schema->string()
                ->description('Template snippet to fix')
                ->required(),
            'field_id' => $schema->integer()
                ->description('Field ID to fix against (alternative to field_name)'),
            'field_name' => $schema->string()
                ->description('Field short name to fix against (alternative to field_id)'),
            'site_id' => $schema->integer()
                ->description('Optional site ID override for field lookup'),
            'template' => $schema->string()
                ->description('Template variant used when generating scaffold')
                ->default('index'),
            'mode' => $schema->enum(['safe', 'replace_with_scaffold'])
                ->description('Fixing mode: "safe" applies minimal repairs; "replace_with_scaffold" replaces snippet with generated scaffold')
                ->default('safe'),
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
            $mode = isset($arguments['mode']) ? (string) $arguments['mode'] : 'safe';

            if (! $fieldId && (! $fieldName || $fieldName === '')) {
                throw new \InvalidArgumentException('Either field_id or field_name is required.');
            }

            if (! in_array($mode, ['safe', 'replace_with_scaffold'], true)) {
                throw new \InvalidArgumentException('mode must be one of: safe, replace_with_scaffold.');
            }

            $field = $this->getField($fieldId, $fieldName, $siteId);
            if (! $field) {
                $identifier = $fieldId ? "ID {$fieldId}" : "name '{$fieldName}'";
                throw new \RuntimeException("Field with {$identifier} not found.");
            }

            $syntaxService = new TemplateTagSyntaxService();
            $fieldShortName = (string) $field->field_name;
            $fieldType = (string) $field->field_type;
            $expectTagPair = $syntaxService->isComplexFieldType($fieldType);

            $scaffold = $this->generateScaffoldOrFallback((int) $field->field_id, $fieldShortName, $expectTagPair, $template, $siteId);

            $validationBefore = $syntaxService->analyzeSnippet($snippet, $fieldShortName, $expectTagPair);
            $fixed = $syntaxService->fixSnippet($snippet, $fieldShortName, $expectTagPair, $scaffold, $mode);
            $validationAfter = $syntaxService->analyzeSnippet($fixed['fixed_snippet'], $fieldShortName, $expectTagPair);

            $this->restoreOutput($oldSettings);

            return [
                'field' => [
                    'field_id' => (int) $field->field_id,
                    'field_name' => $fieldShortName,
                    'field_label' => (string) $field->field_label,
                    'field_type' => $fieldType,
                    'site_id' => (int) $field->site_id,
                ],
                'mode' => $mode,
                'original_snippet' => $snippet,
                'fixed_snippet' => $fixed['fixed_snippet'],
                'applied_fixes' => $fixed['applied_fixes'],
                'used_scaffold' => $fixed['used_scaffold'],
                'validation_before' => $validationBefore,
                'validation_after' => $validationAfter,
                'fixed_at' => date('c'),
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

    private function generateScaffoldOrFallback(
        int $fieldId,
        string $fieldName,
        bool $expectTagPair,
        string $template,
        ?int $siteId
    ): string {
        try {
            $structureService = new FieldTemplateStructureService();
            $result = $structureService->generateForFieldId($fieldId, $template ?: 'index', $siteId);
            $templateCode = trim((string) ($result['template_code'] ?? ''));
            if ($templateCode !== '') {
                return $templateCode;
            }
        } catch (\Throwable $e) {
            // Fall back to minimal scaffold.
        }

        if ($expectTagPair) {
            return '{'.$fieldName.'}'.PHP_EOL
                .'    {!-- Add generated field output here --}'.PHP_EOL
                .'{/'.$fieldName.'}';
        }

        return '{'.$fieldName.'}';
    }
}
