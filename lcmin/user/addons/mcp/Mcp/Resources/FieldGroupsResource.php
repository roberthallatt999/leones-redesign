<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Field Groups Resource
 *
 * Provides access to ExpressionEngine channel field groups. Supports both listing all field groups
 * and retrieving specific field group details by ID.
 */
#[EeCategory('content')]
class FieldGroupsResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://field-groups';
    }

    public function name(): ?string
    {
        return 'field-groups';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine channel field groups. Use ee://field-groups to list all field groups, or ee://field-groups/{groupId} for a specific field group.';
    }

    /**
     * List all field groups - regular resource
     */
    #[McpResource(
        uri: 'ee://field-groups',
        name: 'field_groups_list',
        description: 'List all ExpressionEngine channel field groups'
    )]
    public function listFieldGroups(?int $siteId = null, $includeAllSites = null): mixed
    {
        return $this->listFieldGroupsData($siteId, $includeAllSites);
    }

    /**
     * Get specific field group - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://field-groups/{groupId}',
        name: 'field_group',
        description: 'Get a specific ExpressionEngine channel field group by ID'
    )]
    public function getFieldGroup(string $groupId): mixed
    {
        return $this->getFieldGroupData($groupId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['group_id']) && ! is_numeric($params['group_id'])) {
            throw new \InvalidArgumentException('group_id must be a numeric value');
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
        // If group_id is provided, return specific field group
        if (isset($params['group_id'])) {
            return $this->getFieldGroupData($params['group_id']);
        }

        // Otherwise, return list of all field groups
        return $this->listFieldGroupsData(
            $this->toOptionalInteger($params['site_id'] ?? $params['siteId'] ?? null),
            $params['include_all_sites'] ?? $params['includeAllSites'] ?? null
        );
    }

    /**
     * List all field groups
     */
    private function listFieldGroupsData(?int $siteIdOverride = null, $includeAllSites = null): array
    {
        $siteId = $siteIdOverride ?? $this->toOptionalInteger(ee()->config->item('site_id'));
        $includeAllSites = $this->toBoolean($includeAllSites) ?? false;

        $query = ee('Model')->get('ChannelFieldGroup')
            ->order('group_name');

        if (! $includeAllSites) {
            $this->applySiteFilter($query, $siteId);
        }

        $fieldGroups = $query->all();

        // If site scoping resolved to no results, fall back to all field groups.
        if (! $includeAllSites && $fieldGroups->count() === 0 && is_numeric($siteId) && (int) $siteId > 0) {
            $fieldGroups = ee('Model')->get('ChannelFieldGroup')
                ->order('group_name')
                ->all();
        }

        $result = [
            'field_groups' => [],
            'total' => $fieldGroups->count(),
            'site_id' => $siteId,
            'include_all_sites' => $includeAllSites,
        ];

        foreach ($fieldGroups as $fieldGroup) {
            $result['field_groups'][] = $this->formatFieldGroup($fieldGroup, false);
        }

        return $result;
    }

    /**
     * Get a specific field group by ID
     */
    private function getFieldGroupData($groupId): array
    {
        $fieldGroup = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_id', $groupId)
            ->first();

        if (! $fieldGroup) {
            throw new \InvalidArgumentException("Field group with ID {$groupId} not found");
        }

        return [
            'field_group' => $this->formatFieldGroup($fieldGroup, true),
        ];
    }

    /**
     * Format field group data for output
     *
     * @param  \ExpressionEngine\Model\Channel\ChannelFieldGroup  $fieldGroup
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatFieldGroup($fieldGroup, bool $includeDetails = false): array
    {
        $data = [
            'group_id' => (int) $fieldGroup->group_id,
            'group_name' => $fieldGroup->group_name,
            'site_id' => (int) $fieldGroup->site_id,
        ];

        if ($includeDetails) {
            // Include detailed information
            $data['short_name'] = $fieldGroup->short_name ?? '';
            $data['group_description'] = $fieldGroup->group_description ?? '';

            // Channels using this field group
            $data['channels'] = [];
            foreach ($fieldGroup->Channels as $channel) {
                $data['channels'][] = [
                    'channel_id' => (int) $channel->channel_id,
                    'channel_name' => $channel->channel_name,
                    'channel_title' => $channel->channel_title,
                ];
            }

            // Fields in this group
            $data['fields'] = [];
            foreach ($fieldGroup->ChannelFields as $field) {
                $data['fields'][] = [
                    'field_id' => (int) $field->field_id,
                    'field_name' => $field->field_name,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'field_order' => $field->field_order ? (int) $field->field_order : null,
                ];
            }

            $data['field_count'] = count($data['fields']);
            $data['channel_count'] = count($data['channels']);

            // Site information
            if ($fieldGroup->Site) {
                $data['site'] = [
                    'site_id' => (int) $fieldGroup->Site->site_id,
                    'site_name' => $fieldGroup->Site->site_name,
                    'site_label' => $fieldGroup->Site->site_label,
                ];
            }
        }

        return $data;
    }

    /**
     * Apply site filter for field groups.
     *
     * Field groups may be site-specific (site_id = current site) or shared
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
