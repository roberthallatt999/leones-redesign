<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Prompts;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Mcp\Tools\GetFieldTemplateTagsTool;
use ExpressionEngine\Addons\Mcp\Support\AbstractPrompt;
use Mcp\Capability\Attribute\McpPrompt;

/**
 * Build Channel Template Prompt
 *
 * Generates a complete ExpressionEngine template for a channel, including
 * all fields with proper template tags. Uses channel and field resources
 * to gather structure information and generates template code.
 */
#[EeCategory('developer')]
#[McpPrompt(
    name: 'build_channel_template',
    description: 'Generate a complete ExpressionEngine template for a channel with all fields and proper template tag structure'
)]
class BuildChannelTemplatePrompt extends AbstractPrompt
{
    public function description(): ?string
    {
        return 'Generate a complete ExpressionEngine template for a channel with all fields and proper template tag structure. Uses channel and field resources to gather structure information.';
    }

    public function arguments(): array
    {
        return [
            'channel_id' => [
                'type' => 'integer',
                'description' => 'The channel ID to build a template for',
                'required' => true,
            ],
            'template_type' => [
                'type' => 'string',
                'enum' => ['web_page', 'feed', 'partial'],
                'description' => 'The type of template to generate',
                'default' => 'web_page',
            ],
            'include_fields' => [
                'type' => 'boolean',
                'description' => 'Whether to include all channel fields in the template',
                'default' => true,
            ],
            'include_comments' => [
                'type' => 'boolean',
                'description' => 'Whether to include comment structure in the template',
                'default' => false,
            ],
            'include_categories' => [
                'type' => 'boolean',
                'description' => 'Whether to include category structure in the template',
                'default' => false,
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $channelId = $arguments['channel_id'] ?? null;
        $templateType = $arguments['template_type'] ?? 'web_page';
        $includeFields = $arguments['include_fields'] ?? true;
        $includeComments = $arguments['include_comments'] ?? false;
        $includeCategories = $arguments['include_categories'] ?? false;

        if (! $channelId) {
            throw new \InvalidArgumentException('channel_id is required');
        }

        // Fetch channel data directly using EE models
        $channelModel = ee('Model')->get('Channel')
            ->filter('channel_id', $channelId)
            ->first();

        if (! $channelModel) {
            throw new \RuntimeException("Channel with ID {$channelId} not found");
        }

        // Build channel data array
        $channel = [
            'channel_id' => (int) $channelModel->channel_id,
            'channel_name' => $channelModel->channel_name,
            'channel_title' => $channelModel->channel_title,
            'channel_description' => $channelModel->channel_description ?? '',
        ];

        // Fetch fields for this channel
        $fields = [];
        if ($includeFields) {
            $fieldTagsTool = new GetFieldTemplateTagsTool();

            // Get all custom fields for this channel
            $customFields = $channelModel->CustomFields;

            foreach ($customFields as $fieldModel) {
                try {
                    // Get template tag for this field
                    $tagData = $fieldTagsTool->handle([
                        'field_id' => $fieldModel->field_id,
                        'format' => 'code',
                    ]);

                    $fields[] = [
                        'field_id' => (int) $fieldModel->field_id,
                        'field_name' => $fieldModel->field_name,
                        'field_label' => $fieldModel->field_label,
                        'field_type' => $fieldModel->field_type,
                        'template_tag' => $tagData['template_tag'] ?? '',
                        'is_tag_pair' => $tagData['is_tag_pair'] ?? false,
                        'field_instructions' => $fieldModel->field_instructions ?? '',
                    ];
                } catch (\Throwable $e) {
                    // Skip fields that can't be loaded
                    continue;
                }
            }
        }

        // Build the template
        $template = $this->buildTemplate($channel, $fields, $templateType, $includeComments, $includeCategories);

        // Return prompt messages
        return [
            [
                'role' => 'user',
                'content' => "Build a complete ExpressionEngine template for channel '{$channel['channel_title']}' (ID: {$channelId})",
            ],
            [
                'role' => 'assistant',
                'content' => $template,
            ],
        ];
    }

    /**
     * Build the template code
     *
     * @param  array  $channel  Channel data
     * @param  array  $fields  Field data with template tags
     * @param  string  $templateType  Template type
     * @param  bool  $includeComments  Include comment structure
     * @param  bool  $includeCategories  Include category structure
     * @return string Template code
     */
    private function buildTemplate(array $channel, array $fields, string $templateType, bool $includeComments, bool $includeCategories): string
    {
        $channelName = $channel['channel_name'];
        $channelTitle = $channel['channel_title'];
        $lines = [];

        // Template header
        $lines[] = "{!-- Template for channel: {$channelTitle} ({$channelName}) --}";
        $lines[] = '{!-- Generated by MCP Build Channel Template Prompt --}';
        $lines[] = '';

        // Template type specific opening
        if ($templateType === 'feed') {
            $lines[] = "{exp:channel:entries channel=\"{$channelName}\" limit=\"20\" disable=\"categories|member_data|pagination\"}";
        } elseif ($templateType === 'partial') {
            $lines[] = '{!-- Partial template for channel entries --}';
            $lines[] = "{exp:channel:entries channel=\"{$channelName}\"}";
        } else {
            // Web page template
            $lines[] = "{exp:channel:entries channel=\"{$channelName}\" limit=\"10\"}";
        }

        $lines[] = '';
        $lines[] = '    {!-- Entry Title --}';
        $lines[] = '    <h1>{title}</h1>';
        $lines[] = '';

        // Entry metadata
        $lines[] = '    {!-- Entry Metadata --}';
        $lines[] = '    <div class="entry-meta">';
        $lines[] = '        <span class="entry-date">{entry_date format="%F %d, %Y"}</span>';
        $lines[] = '        {if author}';
        $lines[] = '            <span class="entry-author">By {author}</span>';
        $lines[] = '        {/if}';
        $lines[] = '        {if edit_date}';
        $lines[] = '            <span class="entry-updated">Updated: {edit_date format="%F %d, %Y"}</span>';
        $lines[] = '        {/if}';
        $lines[] = '    </div>';
        $lines[] = '';

        // Entry URL Title
        $lines[] = '    {!-- Entry URL --}';
        $lines[] = '    <div class="entry-url">';
        $lines[] = "        <a href=\"{url_title_path='{$channelName}/detail'}\">{title}</a>";
        $lines[] = '    </div>';
        $lines[] = '';

        // Entry Status
        $lines[] = '    {!-- Entry Status --}';
        $lines[] = '    {if status == "open"}';
        $lines[] = '        <div class="entry-status">Published</div>';
        $lines[] = '    {/if}';
        $lines[] = '';

        // Categories
        if ($includeCategories) {
            $lines[] = '    {!-- Categories --}';
            $lines[] = '    {if categories}';
            $lines[] = '        <div class="entry-categories">';
            $lines[] = '            <h3>Categories</h3>';
            $lines[] = '            {categories}';
            $lines[] = '                <a href="{category_url}">{category_name}</a>';
            $lines[] = '            {/categories}';
            $lines[] = '        </div>';
            $lines[] = '    {/if}';
            $lines[] = '';
        }

        // Custom Fields
        if (! empty($fields)) {
            $lines[] = '    {!-- Custom Fields --}';
            foreach ($fields as $field) {
                $fieldName = $field['field_name'];
                $fieldLabel = $field['field_label'];
                $fieldType = $field['field_type'];
                $isTagPair = $field['is_tag_pair'];
                $instructions = $field['field_instructions'] ?? '';

                $lines[] = '';
                $lines[] = "    {!-- {$fieldLabel} ({$fieldType}) --}";
                if ($instructions) {
                    $lines[] = "    {!-- {$instructions} --}";
                }

                if ($isTagPair) {
                    // Tag pair field
                    $lines[] = "    {if {$fieldName}}";
                    $lines[] = "        <div class=\"field field-{$fieldName} field-{$fieldType}\">";
                    $lines[] = "            <h3>{$fieldLabel}</h3>";
                    $lines[] = "            {{$fieldName}}";
                    $lines[] = "                {!-- Field content for {$fieldLabel} --}";
                    $lines[] = '                {!-- Available variables depend on the field type --}';
                    $lines[] = "            {/{$fieldName}}";
                    $lines[] = '        </div>';
                    $lines[] = '    {/if}';
                } else {
                    // Single tag field
                    $lines[] = "    {if {$fieldName}}";
                    $lines[] = "        <div class=\"field field-{$fieldName} field-{$fieldType}\">";
                    $lines[] = "            <h3>{$fieldLabel}</h3>";
                    $lines[] = "            <div class=\"field-content\">{{$fieldName}}</div>";
                    $lines[] = '        </div>';
                    $lines[] = '    {/if}';
                }
            }
            $lines[] = '';
        }

        // Entry Content (if available)
        $lines[] = '    {!-- Entry Content --}';
        $lines[] = '    {if entry_content}';
        $lines[] = '        <div class="entry-content">';
        $lines[] = '            {entry_content}';
        $lines[] = '        </div>';
        $lines[] = '    {/if}';
        $lines[] = '';

        // Comments
        if ($includeComments) {
            $lines[] = '    {!-- Comments --}';
            $lines[] = '    {if comment_total > 0}';
            $lines[] = '        <div class="entry-comments">';
            $lines[] = '            <h3>Comments ({comment_total})</h3>';
            $lines[] = '            {exp:comment:entries entry_id="{entry_id}"}';
            $lines[] = '                <div class="comment">';
            $lines[] = '                    <div class="comment-author">{name}</div>';
            $lines[] = '                    <div class="comment-date">{comment_date format="%F %d, %Y"}</div>';
            $lines[] = '                    <div class="comment-body">{comment}</div>';
            $lines[] = '                </div>';
            $lines[] = '            {/exp:comment:entries}';
            $lines[] = '        </div>';
            $lines[] = '    {/if}';
            $lines[] = '';
        }

        // Related Entries (if relationship field exists)
        $hasRelationshipField = false;
        foreach ($fields as $field) {
            if ($field['field_type'] === 'relationship') {
                $hasRelationshipField = true;
                break;
            }
        }

        if ($hasRelationshipField) {
            $lines[] = '    {!-- Related Entries --}';
            $lines[] = '    {!-- Note: Add relationship field tags here if needed --}';
            $lines[] = '';
        }

        // Close channel entries tag
        $lines[] = '{/exp:channel:entries}';
        $lines[] = '';

        // Pagination (for web_page templates)
        if ($templateType === 'web_page') {
            $lines[] = '{!-- Pagination --}';
            $lines[] = '{if pagination}';
            $lines[] = '    <div class="pagination">';
            $lines[] = '        {pagination_links}';
            $lines[] = '    </div>';
            $lines[] = '{/if}';
        }

        return implode("\n", $lines);
    }
}
