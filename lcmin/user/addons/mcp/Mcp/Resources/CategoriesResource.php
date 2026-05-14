<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Categories Resource
 *
 * Provides access to ExpressionEngine categories. Supports both listing all categories
 * and retrieving specific category details by ID.
 */
#[EeCategory('content')]
class CategoriesResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://categories';
    }

    public function name(): ?string
    {
        return 'categories';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine categories. Use ee://categories to list all categories, or ee://categories/{categoryId} for a specific category.';
    }

    /**
     * List all categories - regular resource
     */
    #[McpResource(
        uri: 'ee://categories',
        name: 'categories_list',
        description: 'List all ExpressionEngine categories'
    )]
    public function listCategories(): mixed
    {
        return $this->listCategoriesData();
    }

    /**
     * Get specific category - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://categories/{categoryId}',
        name: 'category',
        description: 'Get a specific ExpressionEngine category by ID'
    )]
    public function getCategory(string $categoryId): mixed
    {
        return $this->getCategoryData($categoryId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['category_id']) && ! is_numeric($params['category_id'])) {
            throw new \InvalidArgumentException('category_id must be a numeric value');
        }
    }

    public function fetch(array $params = []): mixed
    {
        // If category_id is provided, return specific category
        if (isset($params['category_id'])) {
            return $this->getCategoryData($params['category_id']);
        }

        // Otherwise, return list of all categories
        return $this->listCategoriesData();
    }

    /**
     * List all categories
     */
    private function listCategoriesData(): array
    {
        $siteId = ee()->config->item('site_id');

        $categories = ee('Model')->get('Category')
            ->filter('site_id', $siteId)
            ->order('cat_name')
            ->all();

        $result = [
            'categories' => [],
            'total' => $categories->count(),
            'site_id' => $siteId,
        ];

        foreach ($categories as $category) {
            $result['categories'][] = $this->formatCategory($category, false);
        }

        return $result;
    }

    /**
     * Get a specific category by ID
     */
    private function getCategoryData($categoryId): array
    {
        $category = ee('Model')->get('Category')
            ->with('CategoryGroup', 'Parent', 'Children', 'Site')
            ->filter('cat_id', $categoryId)
            ->first();

        if (! $category) {
            throw new \InvalidArgumentException("Category with ID {$categoryId} not found");
        }

        return [
            'category' => $this->formatCategory($category, true),
        ];
    }

    /**
     * Format category data for output
     *
     * @param  \ExpressionEngine\Model\Category\Category  $category
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatCategory($category, bool $includeDetails = false): array
    {
        $data = [
            'cat_id' => (int) $category->cat_id,
            'cat_name' => $category->cat_name,
            'cat_url_title' => $category->cat_url_title,
            'site_id' => (int) $category->site_id,
            'group_id' => (int) $category->group_id,
        ];

        if ($includeDetails) {
            // Include detailed information
            $data['cat_description'] = $category->cat_description ?? '';
            $data['cat_image'] = $category->cat_image ?? '';
            $data['cat_order'] = $category->cat_order ? (int) $category->cat_order : null;
            $data['parent_id'] = $category->parent_id ? (int) $category->parent_id : null;

            // Category Group information
            if ($category->CategoryGroup) {
                $data['category_group'] = [
                    'group_id' => (int) $category->CategoryGroup->group_id,
                    'group_name' => $category->CategoryGroup->group_name,
                ];
            }

            // Parent category information
            if ($category->Parent) {
                $data['parent'] = [
                    'cat_id' => (int) $category->Parent->cat_id,
                    'cat_name' => $category->Parent->cat_name,
                    'cat_url_title' => $category->Parent->cat_url_title,
                ];
            }

            // Children categories
            $data['children'] = [];
            foreach ($category->Children as $child) {
                $data['children'][] = [
                    'cat_id' => (int) $child->cat_id,
                    'cat_name' => $child->cat_name,
                    'cat_url_title' => $child->cat_url_title,
                ];
            }

            // Count entries in this category
            $data['entry_count'] = (int) $category->ChannelEntries->count();

            // Site information
            if ($category->Site) {
                $data['site'] = [
                    'site_id' => (int) $category->Site->site_id,
                    'site_name' => $category->Site->site_name,
                    'site_label' => $category->Site->site_label,
                ];
            }
        }

        return $data;
    }
}
