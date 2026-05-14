<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Channels Resource
 *
 * Provides access to ExpressionEngine channels. Supports both listing all channels
 * and retrieving specific channel details by ID.
 */
#[EeCategory('content')]
class ChannelsResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://channels';
    }

    public function name(): ?string
    {
        return 'channels';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine channels. Use ee://channels to list all channels, or ee://channels/{channel_id} for a specific channel.';
    }

    /**
     * List all channels - regular resource
     */
    #[McpResource(
        uri: 'ee://channels',
        name: 'channels_list',
        description: 'List all ExpressionEngine channels'
    )]
    public function listChannels(): mixed
    {
        return $this->listChannelsData();
    }

    /**
     * Get specific channel - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://channels/{channelId}',
        name: 'channel',
        description: 'Get a specific ExpressionEngine channel by ID'
    )]
    public function getChannel(string $channelId): mixed
    {
        return $this->getChannelData($channelId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['channel_id']) && ! is_numeric($params['channel_id'])) {
            throw new \InvalidArgumentException('channel_id must be a numeric value');
        }
    }

    public function fetch(array $params = []): mixed
    {
        // If channel_id is provided, return specific channel
        if (isset($params['channel_id'])) {
            return $this->getChannelData($params['channel_id']);
        }

        // Otherwise, return list of all channels
        return $this->listChannelsData();
    }

    /**
     * List all channels
     */
    private function listChannelsData(): array
    {
        $siteId = ee()->config->item('site_id');

        $channels = ee('Model')->get('Channel')
            ->filter('site_id', $siteId)
            ->order('channel_title')
            ->all();

        $result = [
            'channels' => [],
            'total' => $channels->count(),
            'site_id' => $siteId,
        ];

        foreach ($channels as $channel) {
            $result['channels'][] = $this->formatChannel($channel, false);
        }

        return $result;
    }

    /**
     * Get a specific channel by ID
     */
    private function getChannelData($channelId): array
    {
        $channel = ee('Model')->get('Channel')
            ->filter('channel_id', $channelId)
            ->first();

        if (! $channel) {
            throw new \InvalidArgumentException("Channel with ID {$channelId} not found");
        }

        return [
            'channel' => $this->formatChannel($channel, true),
        ];
    }

    /**
     * Format channel data for output
     *
     * @param  \ExpressionEngine\Model\Channel\Channel  $channel
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatChannel($channel, bool $includeDetails = false): array
    {
        $data = [
            'channel_id' => (int) $channel->channel_id,
            'channel_name' => $channel->channel_name,
            'channel_title' => $channel->channel_title,
            'site_id' => (int) $channel->site_id,
            'total_entries' => (int) $channel->total_entries,
            'total_comments' => (int) $channel->total_comments,
        ];

        if ($includeDetails) {
            // Include detailed information
            $data['description'] = $channel->channel_description ?? '';
            $data['url'] = $channel->channel_url ?? '';
            $data['comment_url'] = $channel->comment_url ?? '';
            $data['rss_url'] = $channel->rss_url ?? '';
            $data['max_entries'] = $channel->max_entries ? (int) $channel->max_entries : null;
            $data['deft_status'] = $channel->deft_status ?? '';
            $data['deft_category'] = $channel->deft_category ?? '';
            $data['deft_comments'] = (bool) $channel->deft_comments;
            $data['channel_require_membership'] = (bool) $channel->channel_require_membership;
            $data['channel_allow_img_urls'] = (bool) $channel->channel_allow_img_urls;
            $data['channel_auto_link_urls'] = (bool) $channel->channel_auto_link_urls;
            $data['channel_notify'] = (bool) $channel->channel_notify;
            $data['sticky_enabled'] = (bool) $channel->sticky_enabled;
            $data['enable_entry_cloning'] = (bool) $channel->enable_entry_cloning;
            $data['comment_system_enabled'] = (bool) $channel->comment_system_enabled;
            $data['comment_require_membership'] = (bool) $channel->comment_require_membership;
            $data['comment_moderate'] = (bool) $channel->comment_moderate;
            $data['comment_require_email'] = (bool) $channel->comment_require_email;
            $data['comment_allow_img_urls'] = (bool) $channel->comment_allow_img_urls;
            $data['comment_auto_link_urls'] = (bool) $channel->comment_auto_link_urls;
            $data['comment_notify'] = (bool) $channel->comment_notify;
            $data['comment_notify_authors'] = (bool) $channel->comment_notify_authors;
            $data['enable_versioning'] = (bool) $channel->enable_versioning;
            $data['search_excerpt'] = $channel->search_excerpt ? (int) $channel->search_excerpt : null;
            $data['conditional_sync_required'] = (bool) $channel->conditional_sync_required;
            $data['enforce_auto_url_title'] = (bool) $channel->enforce_auto_url_title;

            // Include relationships
            $data['field_groups'] = [];
            foreach ($channel->FieldGroups as $group) {
                $data['field_groups'][] = [
                    'group_id' => (int) $group->group_id,
                    'group_name' => $group->group_name,
                ];
            }

            $data['statuses'] = [];
            foreach ($channel->Statuses as $status) {
                $data['statuses'][] = [
                    'status_id' => (int) $status->status_id,
                    'status' => $status->status,
                    'highlight' => $status->highlight,
                ];
            }

            $data['category_groups'] = [];
            foreach ($channel->CategoryGroups as $group) {
                $data['category_groups'][] = [
                    'group_id' => (int) $group->group_id,
                    'group_name' => $group->group_name,
                ];
            }

            $data['custom_fields'] = [];
            foreach ($channel->CustomFields as $field) {
                $data['custom_fields'][] = [
                    'field_id' => (int) $field->field_id,
                    'field_name' => $field->field_name,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                ];
            }

            // Count entries and comments
            $data['entry_count'] = (int) $channel->Entries->count();
            $data['comment_count'] = (int) $channel->Comments->count();

            // Site information
            if ($channel->Site) {
                $data['site'] = [
                    'site_id' => (int) $channel->Site->site_id,
                    'site_name' => $channel->Site->site_name,
                    'site_label' => $channel->Site->site_label,
                ];
            }
        }

        return $data;
    }
}
