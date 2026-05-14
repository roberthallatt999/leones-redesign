<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Resources;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;

/**
 * Members Resource
 *
 * Provides access to ExpressionEngine members. Supports both listing all members
 * and retrieving specific member details by ID.
 */
#[EeCategory('members')]
class MembersResource extends AbstractResource
{
    public function uri(): string
    {
        return 'ee://members';
    }

    public function name(): ?string
    {
        return 'members';
    }

    public function description(): ?string
    {
        return 'Access ExpressionEngine members. Use ee://members to list all members, or ee://members/{memberId} for a specific member.';
    }

    /**
     * List all members - regular resource
     */
    #[McpResource(
        uri: 'ee://members',
        name: 'members_list',
        description: 'List all ExpressionEngine members'
    )]
    public function listMembers(): mixed
    {
        return $this->listMembersData();
    }

    /**
     * Get specific member - template resource
     */
    #[McpResourceTemplate(
        uriTemplate: 'ee://members/{memberId}',
        name: 'member',
        description: 'Get a specific ExpressionEngine member by ID'
    )]
    public function getMember(string $memberId): mixed
    {
        return $this->getMemberData($memberId);
    }

    public function validateParams(array $params): void
    {
        if (isset($params['memberId']) && ! is_numeric($params['memberId'])) {
            throw new \InvalidArgumentException('memberId must be a numeric value');
        }
    }

    public function fetch(array $params = []): mixed
    {
        // If memberId is provided, return specific member
        if (isset($params['memberId'])) {
            return $this->getMemberData($params['memberId']);
        }

        // Otherwise, return list of all members
        return $this->listMembersData();
    }

    /**
     * List all members
     */
    private function listMembersData(): array
    {
        $members = ee('Model')->get('Member')
            ->order('screen_name', 'asc')
            ->all();

        $result = [
            'members' => [],
            'total' => $members->count(),
        ];

        foreach ($members as $member) {
            $result['members'][] = $this->formatMember($member, false);
        }

        return $result;
    }

    /**
     * Get a specific member by ID
     */
    private function getMemberData($memberId): array
    {
        $member = ee('Model')->get('Member')
            ->with('PrimaryRole')
            ->with('Roles')
            ->filter('member_id', $memberId)
            ->first();

        if (! $member) {
            throw new \InvalidArgumentException("Member with ID {$memberId} not found");
        }

        return [
            'member' => $this->formatMember($member, true),
        ];
    }

    /**
     * Format member data for output
     *
     * @param  \ExpressionEngine\Model\Member\Member  $member
     * @param  bool  $includeDetails  Include detailed information
     */
    private function formatMember($member, bool $includeDetails = false): array
    {
        $data = [
            'member_id' => (int) $member->member_id,
            'username' => $member->username,
            'screen_name' => $member->screen_name ?? '',
            'email' => $member->email,
            'role_id' => (int) $member->role_id,
        ];

        if ($includeDetails) {
            // Include detailed information
            // Handle date fields - they might be DateTime objects or integers
            $data['join_date'] = $this->formatDate($member->join_date);
            $data['last_visit'] = $this->formatDate($member->last_visit);
            $data['last_activity'] = $this->formatDate($member->last_activity);
            $data['ip_address'] = $member->ip_address ?? '';
            $data['location'] = $member->location ?? '';
            $data['timezone'] = $member->timezone ?? '';
            $data['date_format'] = $member->date_format ?? '';
            $data['time_format'] = $member->time_format ?? '';
            $data['language'] = $member->language ?? '';
            $data['accept_admin_email'] = (bool) $member->accept_admin_email;
            $data['accept_user_email'] = (bool) $member->accept_user_email;
            $data['notify_by_default'] = (bool) $member->notify_by_default;
            $data['notify_of_pm'] = (bool) $member->notify_of_pm;
            $data['pm_inbox_full'] = (bool) $member->pm_inbox_full;
            $data['cp_homepage'] = $member->cp_homepage ?? '';
            $data['cp_homepage_channel'] = $member->cp_homepage_channel ?? null;
            $data['cp_homepage_custom'] = $member->cp_homepage_custom ?? '';
            $data['profile_theme'] = $member->profile_theme ?? '';
            $data['forum_theme'] = $member->forum_theme ?? '';
            $data['tracker'] = $member->tracker ?? '';
            $data['bio'] = $member->bio ?? '';
            $data['signature'] = $member->signature ?? '';
            $data['avatar_filename'] = $member->avatar_filename ?? '';
            $data['avatar_width'] = $member->avatar_width ? (int) $member->avatar_width : null;
            $data['avatar_height'] = $member->avatar_height ? (int) $member->avatar_height : null;
            $data['photo_filename'] = $member->photo_filename ?? '';
            $data['photo_width'] = $member->photo_width ? (int) $member->photo_width : null;
            $data['photo_height'] = $member->photo_height ? (int) $member->photo_height : null;
            $data['sig_img_filename'] = $member->sig_img_filename ?? '';
            $data['sig_img_width'] = $member->sig_img_width ? (int) $member->sig_img_width : null;
            $data['sig_img_height'] = $member->sig_img_height ? (int) $member->sig_img_height : null;
            $data['ignore_list'] = $member->ignore_list ?? '';
            $data['private_messages'] = $member->private_messages ? (int) $member->private_messages : 0;
            $data['in_authorlist'] = (bool) $member->in_authorlist;
            $data['enable_mfa'] = (bool) $member->enable_mfa;
            $data['mfa_secret'] = $member->mfa_secret ?? null; // Usually null for security
            $data['backup_mfa_code'] = null; // Never expose this
            $data['password'] = null; // Never expose password
            $data['unique_id'] = $member->unique_id ?? '';
            $data['authcode'] = null; // Never expose authcode

            // Primary Role information
            if ($member->PrimaryRole) {
                $data['primary_role'] = [
                    'role_id' => (int) $member->PrimaryRole->role_id,
                    'name' => $member->PrimaryRole->name,
                ];
            }

            // All Roles
            $data['roles'] = [];
            foreach ($member->Roles as $role) {
                $data['roles'][] = [
                    'role_id' => (int) $role->role_id,
                    'name' => $role->name,
                ];
            }

            // Count relationships
            $data['authored_entries_count'] = (int) $member->AuthoredChannelEntries->count();
            $data['comments_count'] = (int) $member->Comments->count();
            $data['uploaded_files_count'] = (int) $member->UploadedFiles->count();
            $data['modified_files_count'] = (int) $member->ModifiedFiles->count();
        }

        return $data;
    }

    /**
     * Format a date field that might be a DateTime object or integer timestamp
     *
     * @param  mixed  $date  DateTime object, integer timestamp, or null
     * @return string|null ISO 8601 formatted date string or null
     */
    private function formatDate($date): ?string
    {
        if (! $date) {
            return null;
        }

        if ($date instanceof \DateTime) {
            return $date->format('c');
        }

        if (is_numeric($date) && $date > 0) {
            $dt = new \DateTime();
            $dt->setTimestamp((int) $date);

            return $dt->format('c');
        }

        return null;
    }
}
