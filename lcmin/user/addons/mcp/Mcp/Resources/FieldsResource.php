<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Fields Resource
 *
 * Provides access to ExpressionEngine channel fields. Supports both listing all fields
 * and retrieving specific field details by ID.
 */
#[EeCategory('content')]
class FieldsResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://fields';
    }

    public function name(): ?string
    {
        return 'fields';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine channel fields. Use ee://fields to list all fields, or ee://fields/{fieldId} for a specific field.';
    }

    /**
     * List all fields - regular resource
     */
    #[McpResource(
        uri: 'ee://fields',
        name: 'fields_list',
        description: 'List all ExpressionEngine channel fields'
    )]
    public function listFields(?int $siteId = null, $includeAllSites = null): mixed
    {
        return $this->listFieldsData($siteId, $includeAllSites);
    }

    /**
     * Get specific field - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://fields/{fieldId}',
        name: 'field',
        description: 'Get a specific ExpressionEngine channel field by ID'
    )]
    public function getField(string $fieldId): mixed
    {
        return $this->getFieldData($fieldId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['field_id']) && ! is_numeric($params['field_id'])) {
            throw new \InvalidArgumentException('field_id must be a numeric value');
        }

        if (isset($params['site_id']) && ! is_numeric($params['site_id'])) {
            throw new \InvalidArgumentException('site_id must be a numeric value');
        }

        if (isset($params['include_all_sites']) && $this->toBoolean($params['include_all_sites']) === null) {
            throw new \InvalidArgumentException('include_all_sites must be a boolean value');
        }
    }

    public function fetch(array $params = []): mixed
    {
        // If field_id is provided, return specific field
        if (isset($params['field_id'])) {
            return $this->getFieldData($params['field_id']);
        }

        // Otherwise, return list of all fields
        return $this->listFieldsData(
            $this->toOptionalInteger($params['site_id'] ?? $params['siteId'] ?? null),
            $params['include_all_sites'] ?? $params['includeAllSites'] ?? null
        );
    }

    /**
     * List all fields
     */
    private function listFieldsData(?int $siteIdOverride = null, $includeAllSites = null): array
    {
        $siteId = $siteIdOverride ?? $this->toOptionalInteger(ee()->config->item('site_id'));
        $includeAllSites = $this->toBoolean($includeAllSites) ?? false;

        $query = ee('Model')->get('ChannelField')
            ->order('field_label');

        if (! $includeAllSites) {
            $this->applySiteFilter($query, $siteId);
        }

        $fields = $query->all();

        // If site scoping resolved to no results, fall back to all fields.
        if (! $includeAllSites && $fields->count() === 0 && is_numeric($siteId) && (int) $siteId > 0) {
            $fields = ee('Model')->get('ChannelField')
                ->order('field_label')
                ->all();
        }

        $result = [
            'fields' => [],
            'total' => $fields->count(),
            'site_id' => $siteId,
            'include_all_sites' => $includeAllSites,
        ];

        foreach ($fields as $field) {
            $result['fields'][] = $this->formatField($field, false);
        }

        return $result;
    }

    /**
     * Get a specific field by ID
     */
    private function getFieldData($fieldId): array
    {
        $field = ee('Model')->get('ChannelField')
            ->with('Site')
            ->filter('field_id', $fieldId)
            ->first();

        if (! $field) {
            throw new \InvalidArgumentException("Field with ID {$fieldId} not found");
        }

        return [
            'field' => $this->formatField($field, true),
        ];
    }

    /**
     * Format field data for output
     *
     * @param  \ExpressionEngine\Model\Channel\ChannelField  $field
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatField($field, bool $includeDetails = false): array
    {
        $data = [
            'field_id' => (int) $field->field_id,
            'field_name' => $field->field_name,
            'field_label' => $field->field_label,
            'field_type' => $field->field_type,
            'site_id' => (int) $field->site_id,
        ];

        if ($includeDetails) {
            // Include detailed information
            $data['field_instructions'] = $field->field_instructions ?? '';
            $data['field_list_items'] = $field->field_list_items ?? '';
            $data['field_pre_populate'] = $field->field_pre_populate ?? '';
            $data['field_pre_channel_id'] = $field->field_pre_channel_id ? (int) $field->field_pre_channel_id : null;
            $data['field_pre_field_id'] = $field->field_pre_field_id ? (int) $field->field_pre_field_id : null;
            $data['field_ta_rows'] = $field->field_ta_rows ? (int) $field->field_ta_rows : null;
            $data['field_maxl'] = $field->field_maxl ? (int) $field->field_maxl : null;
            $data['field_required'] = $field->field_required === 'y';
            $data['field_text_direction'] = $field->field_text_direction ?? 'ltr';
            $data['field_search'] = $field->field_search === 'y';
            $data['field_is_hidden'] = $field->field_is_hidden === 'y';
            $data['field_is_conditional'] = $field->field_is_conditional === 'y';
            $data['field_fmt'] = $field->field_fmt ?? '';
            $data['field_show_fmt'] = $field->field_show_fmt === 'y';
            $data['field_order'] = $field->field_order ? (int) $field->field_order : null;
            $data['field_content_type'] = $field->field_content_type ?? '';
            $data['legacy_field_data'] = $field->legacy_field_data === 'y';
            $data['enable_frontedit'] = $field->enable_frontedit === 'y';

            // Field Groups
            $data['field_groups'] = [];
            foreach ($field->ChannelFieldGroups as $group) {
                $data['field_groups'][] = [
                    'group_id' => (int) $group->group_id,
                    'group_name' => $group->group_name,
                ];
            }

            // Channels using this field
            $data['channels'] = [];
            foreach ($field->Channels as $channel) {
                $data['channels'][] = [
                    'channel_id' => (int) $channel->channel_id,
                    'channel_name' => $channel->channel_name,
                    'channel_title' => $channel->channel_title,
                ];
            }

            // Site information
            if ($field->Site) {
                $data['site'] = [
                    'site_id' => (int) $field->Site->site_id,
                    'site_name' => $field->Site->site_name,
                    'site_label' => $field->Site->site_label,
                ];
            }
        }

        return $data;
    }

    /**
     * Apply site filter for fields.
     *
     * Channel fields may be site-specific (site_id = current site) or shared
     * across sites (site_id = 0), so include both when site context exists.
     */
    private function applySiteFilter($query, $siteId): void
    {
        if (! is_numeric($siteId)) {
            return;
        }

        $siteId = (int) $siteId;

        if ($siteId > 0) {
            $query->filter('site_id', 'IN', [0, $siteId]);

            return;
        }

        $query->filter('site_id', 0);
    }

    /**
     * Normalize optional numeric value to integer.
     */
    private function toOptionalInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Normalize common boolean-like values.
     */
    private function toBoolean($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return match ($normalized) {
                '1', 'true', 'yes', 'on', 'y' => true,
                '0', 'false', 'no', 'off', 'n' => false,
                default => null,
            };
        }

        return null;
    }
}
