<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Templates Resource
 *
 * Provides access to ExpressionEngine templates. Supports both listing all templates
 * and retrieving specific template details by ID.
 */
#[EeCategory('content')]
class TemplatesResource extends AbstractResource
{
    private const DEFAULT_PAGE_SIZE = 100;

    private const MAX_PAGE_SIZE = 500;

    public function uri(): string
    {
        return 'ee://templates';
    }

    public function name(): ?string
    {
        return 'templates';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine templates. Use ee://templates for paginated template lists, or ee://templates/{templateId} for a specific template.';
    }

    /**
     * List all templates - regular resource
     */
    #[McpResource(
        uri: 'ee://templates',
        name: 'templates_list',
        description: 'List ExpressionEngine templates (paginated)'
    )]
    public function listTemplates(
        ?string $limit = null,
        ?string $offset = null,
        ?string $search = null,
        ?string $groupId = null
    ): mixed {
        return $this->listTemplatesData([
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search,
            'group_id' => $groupId,
        ]);
    }

    /**
     * Get specific template - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://templates/{templateId}',
        name: 'template',
        description: 'Get a specific ExpressionEngine template by ID'
    )]
    public function getTemplate(string $templateId): mixed
    {
        return $this->getTemplateData($templateId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['template_id']) && ! is_numeric($params['template_id'])) {
            throw new \InvalidArgumentException('template_id must be a numeric value');
        }

        if (isset($params['limit']) && ! is_numeric($params['limit'])) {
            throw new \InvalidArgumentException('limit must be a numeric value');
        }

        if (isset($params['offset']) && ! is_numeric($params['offset'])) {
            throw new \InvalidArgumentException('offset must be a numeric value');
        }

        if (isset($params['group_id']) && ! is_numeric($params['group_id'])) {
            throw new \InvalidArgumentException('group_id must be a numeric value');
        }
    }

    public function fetch(array $params = []): mixed
    {
        // If template_id is provided, return specific template
        if (isset($params['template_id'])) {
            return $this->getTemplateData($params['template_id']);
        }

        // Otherwise, return list of all templates
        return $this->listTemplatesData($params);
    }

    /**
     * List all templates
     */
    private function listTemplatesData(array $params = []): array
    {
        $siteId = (int) ee()->config->item('site_id');
        $limit = $this->normalizePageSize($params['limit'] ?? null);
        $offset = $this->normalizeOffset($params['offset'] ?? null);
        $search = trim((string) ($params['search'] ?? ''));
        $groupId = $this->normalizeOptionalInteger($params['group_id'] ?? null);

        $totalQuery = ee()->db->from('templates');
        $this->applyListFilters($totalQuery, $siteId, $search, $groupId);
        $total = (int) $totalQuery->count_all_results();

        $query = ee()->db
            ->select('template_id, template_name, template_type, site_id, group_id')
            ->from('templates');
        $this->applyListFilters($query, $siteId, $search, $groupId);
        $rows = $query
            ->order_by('template_name', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->result_array();

        $result = [
            'templates' => [],
            'total' => $total,
            'site_id' => $siteId,
            'limit' => $limit,
            'offset' => $offset,
        ];

        foreach ($rows as $row) {
            $result['templates'][] = [
                'template_id' => (int) $row['template_id'],
                'template_name' => $row['template_name'],
                'template_type' => $row['template_type'],
                'site_id' => (int) $row['site_id'],
                'group_id' => (int) $row['group_id'],
            ];
        }

        $returned = count($result['templates']);
        $result['returned'] = $returned;
        $result['has_more'] = ($offset + $returned) < $total;
        $result['next_offset'] = $result['has_more'] ? ($offset + $returned) : null;

        if ($search !== '') {
            $result['search'] = $search;
        }

        if ($groupId !== null) {
            $result['group_id'] = $groupId;
        }

        return $result;
    }

    private function applyListFilters($query, int $siteId, string $search, ?int $groupId): void
    {
        $query->where('site_id', $siteId);

        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        }

        if ($search !== '') {
            $query->like('template_name', $search);
        }
    }

    private function normalizePageSize($value): int
    {
        if ($value === null || $value === '') {
            return self::DEFAULT_PAGE_SIZE;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('limit must be a numeric value');
        }

        $limit = (int) $value;
        if ($limit < 1) {
            return self::DEFAULT_PAGE_SIZE;
        }

        return min($limit, self::MAX_PAGE_SIZE);
    }

    private function normalizeOffset($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('offset must be a numeric value');
        }

        $offset = (int) $value;

        return max(0, $offset);
    }

    private function normalizeOptionalInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('group_id must be a numeric value');
        }

        return max(0, (int) $value);
    }

    /**
     * Get a specific template by ID
     */
    private function getTemplateData($templateId): array
    {
        $template = ee('Model')->get('Template')
            ->with('Site', 'TemplateGroup', 'LastAuthor', 'TemplateRoute')
            ->filter('template_id', $templateId)
            ->first();

        if (! $template) {
            throw new \InvalidArgumentException("Template with ID {$templateId} not found");
        }

        return [
            'template' => $this->formatTemplate($template, true),
        ];
    }

    /**
     * Format template data for output
     *
     * @param  \ExpressionEngine\Model\Template\Template  $template
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatTemplate($template, bool $includeDetails = false): array
    {
        $data = [
            'template_id' => (int) $template->template_id,
            'template_name' => $template->template_name,
            'template_type' => $template->template_type,
            'site_id' => (int) $template->site_id,
            'group_id' => (int) $template->group_id,
        ];

        if ($includeDetails) {
            // Include detailed information
            $data['template_engine'] = $template->template_engine ?? '';
            $data['template_notes'] = $template->template_notes ?? '';
            $data['edit_date'] = $template->edit_date ? date('c', $template->edit_date) : null;
            $data['cache'] = $template->cache === 'y';
            $data['refresh'] = $template->refresh ? (int) $template->refresh : null;
            $data['enable_http_auth'] = $template->enable_http_auth === 'y';
            $data['allow_php'] = $template->allow_php === 'y';
            $data['php_parse_location'] = $template->php_parse_location ?? '';
            $data['hits'] = $template->hits ? (int) $template->hits : 0;
            $data['protect_javascript'] = $template->protect_javascript === 'y';
            $data['enable_frontedit'] = $template->enable_frontedit === 'y';

            // Template Group information
            if ($template->TemplateGroup) {
                $data['template_group'] = [
                    'group_id' => (int) $template->TemplateGroup->group_id,
                    'group_name' => $template->TemplateGroup->group_name,
                ];
            }

            // Last Author information
            if ($template->LastAuthor) {
                $data['last_author'] = [
                    'member_id' => (int) $template->LastAuthor->member_id,
                    'username' => $template->LastAuthor->username,
                    'screen_name' => $template->LastAuthor->screen_name ?? '',
                ];
            }

            // Template Route information
            if ($template->TemplateRoute) {
                $data['route'] = [
                    'route_id' => (int) $template->TemplateRoute->route_id,
                    'route' => $template->TemplateRoute->route ?? '',
                    'route_parsed' => $template->TemplateRoute->route_parsed ?? '',
                ];
            }

            // Template data (content) - include if requested but truncate for large templates
            $templateData = $template->template_data ?? '';
            if (! empty($templateData)) {
                $data['template_data_length'] = strlen($templateData);
                // Only include full content if it's reasonably sized (less than 50KB)
                if (strlen($templateData) < 50000) {
                    $data['template_data'] = $templateData;
                } else {
                    $data['template_data'] = substr($templateData, 0, 1000).'... [truncated]';
                    $data['template_data_truncated'] = true;
                }
            } else {
                $data['template_data'] = '';
                $data['template_data_length'] = 0;
            }

            // Site information
            if ($template->Site) {
                $data['site'] = [
                    'site_id' => (int) $template->Site->site_id,
                    'site_name' => $template->Site->site_name,
                    'site_label' => $template->Site->site_label,
                ];
            }
        }

        return $data;
    }
}
