<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * Get Field Template Tags Tool
 *
 * Generates template tag code for ExpressionEngine channel fields.
 * Uses the template generator system to produce proper template tags.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'get_field_template_tags',
    description: 'Get basic/fallback template tag code for ExpressionEngine channel fields. Prefer get_field_template_structure for exact CLI-generated scaffold output.'
)]
class GetFieldTemplateTagsTool extends AbstractTool
{
    public function description(): string
    {
        return 'Generate basic fallback template tag code for ExpressionEngine channel fields. Prefer get_field_template_structure when you need exact CLI-generated field scaffold (especially for complex field types).';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'field_id' => $schema->integer()
                ->description('The field ID to get template tags for'),
            'field_name' => $schema->string()
                ->description('The field name to get template tags for (alternative to field_id)'),
            'format' => $schema->enum(['code', 'full'])
                ->description('Output format: "code" returns just the template tag code, "full" returns detailed information including field metadata')
                ->default('code'),
        ], [])->toArray();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function isIdempotent(): bool
    {
        return true;
    }

    public function handle(array $params): array
    {
        // Suppress all output to prevent MCP server from failing
        ob_start();

        try {
            $fieldId = $params['field_id'] ?? null;
            $fieldName = $params['field_name'] ?? null;
            $format = $params['format'] ?? 'code';

            // Validate that at least one identifier is provided
            if (! $fieldId && ! $fieldName) {
                throw new \InvalidArgumentException('Either field_id or field_name must be provided.');
            }

            // Get the field
            $field = $this->getField($fieldId, $fieldName);
            if (! $field) {
                $identifier = $fieldId ? "ID {$fieldId}" : "name '{$fieldName}'";
                throw new \RuntimeException("Field with {$identifier} not found.");
            }

            // Generate basic field variables (skip template generator for now due to fieldtype loading issues)
            $fieldVariables = [
                'field_name' => $field->field_name,
                'field_label' => $field->field_label,
                'field_type' => $field->field_type,
                'is_tag_pair' => $this->isTagPair($field->field_type),
                'docs_url' => null,
                'note' => 'Basic template tag generated due to fieldtype loading issues',
            ];

            // Generate template tag code
            $templateTag = $this->generateTemplateTag($fieldVariables);

            // Clean up any output
            ob_end_clean();

            if ($format === 'full') {
                return [
                    'field_id' => (int) $field->field_id,
                    'field_name' => $field->field_name,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'template_tag' => $templateTag,
                    'is_tag_pair' => $fieldVariables['is_tag_pair'] ?? false,
                    'docs_url' => $fieldVariables['docs_url'] ?? null,
                    'field_variables' => $fieldVariables,
                ];
            }

            return [
                'field_id' => (int) $field->field_id,
                'field_name' => $field->field_name,
                'field_label' => $field->field_label,
                'field_type' => $field->field_type,
                'template_tag' => $templateTag,
                'is_tag_pair' => $fieldVariables['is_tag_pair'] ?? false,
                'docs_url' => $fieldVariables['docs_url'] ?? null,
            ];
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException('An unexpected error occurred while generating template tags.');
        }
    }

    /**
     * Get field by ID or name
     *
     * @param  int|null  $fieldId
     * @param  string|null  $fieldName
     * @return \ExpressionEngine\Model\Channel\ChannelField|null
     */
    private function getField($fieldId, $fieldName)
    {
        $query = ee('Model')->get('ChannelField');

        if ($fieldId) {
            $query->filter('field_id', $fieldId);
        } elseif ($fieldName) {
            $query->filter('field_name', $fieldName);
        }

        return $query->first();
    }

    /**
     * Check if a field type typically uses tag pairs
     */
    private function isTagPair(string $fieldType): bool
    {
        // Common field types that use tag pairs
        $tagPairTypes = [
            'grid',
            'fluid_field',
            'relationship',
            'file',
            'rte',
            'matrix', // if installed
            'playa', // if installed
        ];

        return in_array($fieldType, $tagPairTypes);
    }

    /**
     * Generate template tag code from field variables
     */
    private function generateTemplateTag(array $fieldVariables): string
    {
        $fieldName = $fieldVariables['field_name'];
        $isTagPair = $fieldVariables['is_tag_pair'] ?? false;
        $fieldLabel = $fieldVariables['field_label'] ?? $fieldName;
        $fieldType = $fieldVariables['field_type'] ?? 'text';

        if ($isTagPair) {
            $tagPair = "{{$fieldName}}\n";
            $tagPair .= "    {!-- Template tag pair for {$fieldLabel} ({$fieldType}) --}\n";
            $tagPair .= "    {!-- Available variables depend on the field type --}\n";
            $tagPair .= "    {!-- Check ExpressionEngine documentation for {$fieldType} field variables --}\n";
            $tagPair .= "{/{$fieldName}}";

            return $tagPair;
        } else {
            return "{{$fieldName}} {!-- {$fieldLabel} ({$fieldType}) --}";
        }
    }
}
