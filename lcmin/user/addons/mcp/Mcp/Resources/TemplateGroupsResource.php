<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Template Groups Resource
 *
 * Provides access to ExpressionEngine template groups. Supports both listing all template groups
 * and retrieving specific template group details by ID.
 */
#[EeCategory('content')]
class TemplateGroupsResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://template-groups';
    }

    public function name(): ?string
    {
        return 'template-groups';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine template groups. Use ee://template-groups to list all template groups, or ee://template-groups/{groupId} for a specific template group.';
    }

    /**
     * List all template groups - regular resource
     */
    #[McpResource(
        uri: 'ee://template-groups',
        name: 'template_groups_list',
        description: 'List all ExpressionEngine template groups'
    )]
    public function listTemplateGroups(): mixed
    {
        return $this->listTemplateGroupsData();
    }

    /**
     * Get specific template group - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://template-groups/{groupId}',
        name: 'template_group',
        description: 'Get a specific ExpressionEngine template group by ID'
    )]
    public function getTemplateGroup(string $groupId): mixed
    {
        return $this->getTemplateGroupData($groupId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['group_id']) && ! is_numeric($params['group_id'])) {
            throw new \InvalidArgumentException('group_id must be a numeric value');
        }
    }

    public function fetch(array $params = []): mixed
    {
        // If group_id is provided, return specific template group
        if (isset($params['group_id'])) {
            return $this->getTemplateGroupData($params['group_id']);
        }

        // Otherwise, return list of all template groups
        return $this->listTemplateGroupsData();
    }

    /**
     * List all template groups
     */
    private function listTemplateGroupsData(): array
    {
        $siteId = ee()->config->item('site_id');

        $templateGroups = ee('Model')->get('TemplateGroup')
            ->filter('site_id', $siteId)
            ->order('group_name')
            ->all();

        $result = [
            'template_groups' => [],
            'total' => $templateGroups->count(),
            'site_id' => $siteId,
        ];

        foreach ($templateGroups as $templateGroup) {
            $result['template_groups'][] = $this->formatTemplateGroup($templateGroup, false);
        }

        return $result;
    }

    /**
     * Get a specific template group by ID
     */
    private function getTemplateGroupData($groupId): array
    {
        $templateGroup = ee('Model')->get('TemplateGroup')
            ->with('Site', 'Templates')
            ->filter('group_id', $groupId)
            ->first();

        if (! $templateGroup) {
            throw new \InvalidArgumentException("Template group with ID {$groupId} not found");
        }

        return [
            'template_group' => $this->formatTemplateGroup($templateGroup, true),
        ];
    }

    /**
     * Format template group data for output
     *
     * @param  \ExpressionEngine\Model\Template\TemplateGroup  $templateGroup
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatTemplateGroup($templateGroup, bool $includeDetails = false): array
    {
        $data = [
            'group_id' => (int) $templateGroup->group_id,
            'group_name' => $templateGroup->group_name,
            'site_id' => (int) $templateGroup->site_id,
        ];

        if ($includeDetails) {
            // Include detailed information
            $data['group_order'] = $templateGroup->group_order ? (int) $templateGroup->group_order : null;
            $data['is_site_default'] = $templateGroup->is_site_default === 'y';

            // Templates in this group
            $data['templates'] = [];
            foreach ($templateGroup->Templates as $template) {
                $data['templates'][] = [
                    'template_id' => (int) $template->template_id,
                    'template_name' => $template->template_name,
                    'template_type' => $template->template_type,
                ];
            }

            $data['template_count'] = count($data['templates']);

            // Site information
            if ($templateGroup->Site) {
                $data['site'] = [
                    'site_id' => (int) $templateGroup->Site->site_id,
                    'site_name' => $templateGroup->Site->site_name,
                    'site_label' => $templateGroup->Site->site_label,
                ];
            }
        }

        return $data;
    }
}
