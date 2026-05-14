<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Prompts;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractPrompt;
use Mcp\Capability\Attribute\McpPrompt;

/**
 * Convert HTML to Channel Template Prompt
 *
 * Provides instructions to convert static HTML into an ExpressionEngine
 * channel template by identifying fields and replacing static content
 * with appropriate channel tags.
 */
#[EeCategory('developer')]
#[McpPrompt(
    name: 'convert_html_to_channel',
    description: 'Convert static HTML to an ExpressionEngine channel template by identifying fields and replacing content with channel tags'
)]
class ConvertHtmlToChannelPrompt extends AbstractPrompt
{
    public function description(): ?string
    {
        return 'Convert static HTML into an ExpressionEngine channel template. This prompt guides the agent to use template generator tools to identify available fields and generators, then transform the HTML to include appropriate channel tags.';
    }

    public function arguments(): array
    {
        return [
            'html' => [
                'type' => 'string',
                'description' => 'The static HTML content to convert to a channel template',
                'required' => true,
            ],
            'channel_id' => [
                'type' => 'integer',
                'description' => 'The channel ID to generate templates for',
            ],
            'channel_name' => [
                'type' => 'string',
                'description' => 'The channel name (alternative to channel_id)',
            ],
            'preserve_structure' => [
                'type' => 'boolean',
                'description' => 'Whether to preserve the HTML structure and only replace content with channel tags',
                'default' => true,
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $html = $arguments['html'] ?? '';
        $channelId = $arguments['channel_id'] ?? null;
        $channelName = $arguments['channel_name'] ?? null;
        $preserveStructure = $arguments['preserve_structure'] ?? true;

        if (empty($html)) {
            throw new \InvalidArgumentException('HTML content is required');
        }

        if (! $channelId && ! $channelName) {
            throw new \InvalidArgumentException('Either channel_id or channel_name is required');
        }

        // Get channel information
        $channel = $this->getChannel($channelId, $channelName);
        if (! $channel) {
            $identifier = $channelId ? "ID {$channelId}" : "name '{$channelName}'";
            throw new \RuntimeException("Channel with {$identifier} not found");
        }

        // Build the prompt messages that guide the agent
        $instructions = $this->buildInstructions($channel, $html, $preserveStructure);

        return [
            [
                'role' => 'user',
                'content' => $instructions,
            ],
        ];
    }

    /**
     * Get channel by ID or name
     *
     * @param  int|null  $channelId
     * @param  string|null  $channelName
     * @return array|null
     */
    private function getChannel($channelId, $channelName)
    {
        $query = ee('Model')->get('Channel');

        if ($channelId) {
            $query->filter('channel_id', $channelId);
        } elseif ($channelName) {
            $query->filter('channel_name', $channelName);
        }

        $channelModel = $query->first();

        if (! $channelModel) {
            return;
        }

        return [
            'channel_id' => (int) $channelModel->channel_id,
            'channel_name' => $channelModel->channel_name,
            'channel_title' => $channelModel->channel_title,
        ];
    }

    /**
     * Build instructions for the agent
     */
    private function buildInstructions(array $channel, string $html, bool $preserveStructure): string
    {
        $channelName = $channel['channel_name'];
        $channelTitle = $channel['channel_title'];
        $channelId = $channel['channel_id'];

        $structureNote = $preserveStructure
            ? 'Keep the HTML structure intact, only replacing content with channel tags'
            : 'You may restructure as needed';

        $instructions = <<<INSTRUCTIONS
Convert the following static HTML into an ExpressionEngine channel template for channel "{$channelTitle}" ({$channelName}, ID: {$channelId}).

To accomplish this task, follow these steps:

1. **List available template generators**: Use the `list_template_generators` tool to see what generators are available. Pay special attention to:
   - `channel:channels` - for channel entry templates
   - `channel:fields` - for individual field templates
   - `channel:fieldGroups` - for field group templates

2. **Get channel field information**: Use the `execute_template_generator` tool with:
   - generator_key: "channel:channels"
   - options: { "channel": [ "{$channelName}" ] }
   - format: "full"
   
   This will show you all available fields and their template tag formats for this channel.

3. **Analyze the HTML structure**: Examine the provided HTML and identify:
   - Static content that should become channel entry variables (title, entry_date, etc.)
   - Content that maps to custom channel fields
   - Structural elements (headings, lists, etc.) that should be preserved
   - Loops or repeated content that should use {exp:channel:entries}

4. **Transform the HTML**: Replace static content with appropriate ExpressionEngine tags:
   - Wrap the content in {exp:channel:entries channel="{$channelName}"}
   - Replace static titles with {title}
   - Replace static dates with {entry_date format="..."}
   - Replace static content with appropriate field tags based on the generator output
   - Preserve HTML structure: {$structureNote}
   - Add conditional tags where appropriate ({if field_name}...{/if})

5. **Return the transformed template**: Provide the complete ExpressionEngine template code with:
   - Proper channel entries tag wrapping
   - All field tags properly formatted
   - Preserved HTML structure
   - Comments explaining what was changed

**HTML to convert:**
```html
{$html}
```

**Important Notes:**
- Use the generator tools to get the exact field names and tag formats for this channel
- Match HTML content to appropriate channel fields based on semantic meaning
- Preserve HTML classes, IDs, and structure where possible
- Add ExpressionEngine comments ({!-- ... --}) to explain transformations
- Ensure all channel tags are properly formatted and closed
INSTRUCTIONS;

        return $instructions;
    }
}
