<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Field Template Structure Service
 *
 * Runs ExpressionEngine's CLI template generator for a specific field and
 * returns the exact scaffold code.
 */
class FieldTemplateStructureService
{
    /**
     * Field types where generated structure is most valuable.
     */
    private const COMPLEX_FIELD_TYPES = [
        'grid',
        'fluid_field',
        'matrix',
        'relationship',
        'file',
        'file_grid',
        'rte',
    ];

    /**
     * Generate template code for a field by name.
     */
    public function generateForFieldName(string $fieldName, string $template = 'index', ?int $siteId = null): array
    {
        $field = $this->findFieldByName($fieldName, $siteId);
        if (! $field) {
            throw new \RuntimeException("Field '{$fieldName}' not found.");
        }

        return $this->generateForFieldModel($field, $template, $siteId);
    }

    /**
     * Generate template code for a field by ID.
     */
    public function generateForFieldId(int $fieldId, string $template = 'index', ?int $siteId = null): array
    {
        $field = $this->findFieldById($fieldId, $siteId);
        if (! $field) {
            throw new \RuntimeException("Field with ID {$fieldId} not found.");
        }

        return $this->generateForFieldModel($field, $template, $siteId);
    }

    /**
     * List fields where this scaffold is most useful.
     */
    public function listRecommendedFields(?int $siteId = null): array
    {
        $query = ee('Model')->get('ChannelField')
            ->order('field_label');

        if ($siteId !== null) {
            $query->filter('site_id', $siteId);
        }

        $fields = $query->all();

        $recommended = [];
        foreach ($fields as $field) {
            if ($this->isComplexType((string) $field->field_type)) {
                $recommended[] = $this->formatField($field);
            }
        }

        return [
            'site_id' => $siteId,
            'complex_field_types' => self::COMPLEX_FIELD_TYPES,
            'recommended_fields' => $recommended,
            'total_recommended' => count($recommended),
            'when_to_use' => 'Use this scaffold when writing template tags for complex fields (especially grid/fluid/matrix/file/relationship/rte) to avoid syntax mistakes.',
            'generated_at' => date('c'),
        ];
    }

    /**
     * Generate template code for a channel field model.
     */
    private function generateForFieldModel($field, string $template = 'index', ?int $siteId = null): array
    {
        $templateName = trim($template);
        if ($templateName === '') {
            $templateName = 'index';
        }

        $result = $this->runTemplateGeneratorCommand(
            (string) $field->field_name,
            $templateName,
            $siteId
        );

        $fieldType = (string) $field->field_type;

        return [
            'field' => $this->formatField($field),
            'template' => $templateName,
            'template_code' => $result['output'],
            'command' => $result['command'],
            'is_complex_field' => $this->isComplexType($fieldType),
            'recommended_for' => $this->isComplexType($fieldType)
                ? 'This field type is complex. Prefer generated scaffold over hand-written tag pairs.'
                : 'This field type is straightforward, but generated scaffold still guarantees correct syntax.',
            'generated_at' => date('c'),
        ];
    }

    /**
     * Run the ExpressionEngine CLI template generator command.
     */
    private function runTemplateGeneratorCommand(string $fieldName, string $template, ?int $siteId = null): array
    {
        $eecliPath = SYSPATH.'ee/eecli.php';
        if (! file_exists($eecliPath)) {
            throw new \RuntimeException("EE CLI script not found at: {$eecliPath}");
        }

        $commandParts = [
            escapeshellarg(PHP_BINARY),
            escapeshellarg($eecliPath),
            'generate:templates',
            'channel:fields',
            '--templates='.escapeshellarg($template),
            '--field='.escapeshellarg($fieldName),
            '--show',
            '--code',
        ];

        if ($siteId !== null) {
            $commandParts[] = '--site_id='.(int) $siteId;
        }

        $command = implode(' ', $commandParts).' 2>&1';

        $outputLines = [];
        $exitCode = 0;
        exec($command, $outputLines, $exitCode);

        $output = trim(implode("\n", $outputLines));
        if ($exitCode !== 0) {
            $message = $output !== ''
                ? $output
                : "Command failed with exit code {$exitCode}.";
            throw new \RuntimeException($message);
        }

        return [
            'command' => $command,
            'output' => $output,
        ];
    }

    /**
     * Find field by name.
     */
    private function findFieldByName(string $fieldName, ?int $siteId = null)
    {
        $query = ee('Model')->get('ChannelField')
            ->filter('field_name', $fieldName);

        if ($siteId !== null) {
            $query->filter('site_id', $siteId);
        }

        return $query->first();
    }

    /**
     * Find field by ID.
     */
    private function findFieldById(int $fieldId, ?int $siteId = null)
    {
        $query = ee('Model')->get('ChannelField')
            ->filter('field_id', $fieldId);

        if ($siteId !== null) {
            $query->filter('site_id', $siteId);
        }

        return $query->first();
    }

    /**
     * Format channel field model for output.
     */
    private function formatField($field): array
    {
        return [
            'field_id' => (int) $field->field_id,
            'field_name' => (string) $field->field_name,
            'field_label' => (string) $field->field_label,
            'field_type' => (string) $field->field_type,
            'site_id' => (int) $field->site_id,
        ];
    }

    /**
     * Check whether field type is considered complex.
     */
    private function isComplexType(string $fieldType): bool
    {
        return in_array($fieldType, self::COMPLEX_FIELD_TYPES, true);
    }
}
