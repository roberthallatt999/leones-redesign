<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Evaluates EePermissions metadata against the current EE runtime identity.
 */
class PermissionEvaluatorService
{
    /**
     * @param  array<int, string>|null  $permissions
     */
    public function isAllowed(?array $permissions): bool
    {
        if (empty($permissions)) {
            return true;
        }

        // Escape hatch for local debugging or phased rollout.
        if (getenv('MCP_PERMISSION_BYPASS') === '1') {
            return true;
        }

        $context = $this->resolveContext();
        if (! $context['hasIdentity']) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (! is_string($permission)) {
                continue;
            }

            if ($this->matches(trim($permission), $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *   hasIdentity: bool,
     *   memberId: int|null,
     *   groupId: int|null,
     *   roleName: string|null,
     *   isSuperAdmin: bool
     * }
     */
    private function resolveContext(): array
    {
        $memberId = $this->toPositiveInt($this->sessionValue('member_id'));
        $groupId = $this->toPositiveInt($this->sessionValue('group_id'));
        $roleName = $this->toRoleName(
            $this->sessionValue('group_title')
            ?? $this->sessionValue('role_title')
            ?? $this->sessionValue('role_name')
        );

        if ($roleName === null && $groupId !== null) {
            $roleName = $this->lookupRoleNameByGroupId($groupId);
        }

        $isSuperAdmin = $groupId === 1 || ($roleName !== null && strtolower($roleName) === 'super admin');
        $hasIdentity = $memberId !== null || $groupId !== null || $roleName !== null;

        return [
            'hasIdentity' => $hasIdentity,
            'memberId' => $memberId,
            'groupId' => $groupId,
            'roleName' => $roleName,
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }

    /**
     * @param array{
     *   hasIdentity: bool,
     *   memberId: int|null,
     *   groupId: int|null,
     *   roleName: string|null,
     *   isSuperAdmin: bool
     * } $context
     */
    private function matches(string $permission, array $context): bool
    {
        if ($permission === '' || $permission === '*') {
            return true;
        }

        if (! str_contains($permission, ':')) {
            return false;
        }

        [$type, $value] = explode(':', $permission, 2);
        $type = strtolower(trim($type));
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        switch ($type) {
            case 'user':
                $requiredUserId = $this->toPositiveInt($value);

                return $requiredUserId !== null && $context['memberId'] === $requiredUserId;

            case 'group':
            case 'role_id':
                $requiredGroupId = $this->toPositiveInt($value);

                return $requiredGroupId !== null && $context['groupId'] === $requiredGroupId;

            case 'role':
                if ($context['roleName'] === null) {
                    return false;
                }

                if (strtolower($value) === 'super admin') {
                    return $context['isSuperAdmin'];
                }

                return strtolower($context['roleName']) === strtolower($value);

            case 'can':
                return $this->hasCapability($value, $context);
        }

        return false;
    }

    /**
     * @param array{
     *   hasIdentity: bool,
     *   memberId: int|null,
     *   groupId: int|null,
     *   roleName: string|null,
     *   isSuperAdmin: bool
     * } $context
     */
    private function hasCapability(string $capability, array $context): bool
    {
        if ($context['isSuperAdmin']) {
            return true;
        }

        $session = $this->session();
        if ($session) {
            foreach (['hasPermission', 'checkPermission', 'can'] as $method) {
                if (method_exists($session, $method)) {
                    try {
                        if ((bool) $session->{$method}($capability)) {
                            return true;
                        }
                    } catch (\Throwable $e) {
                        // Ignore and continue with fallbacks.
                    }
                }
            }
        }

        if (function_exists('ee') && isset(ee()->cp) && method_exists(ee()->cp, 'allowed_group')) {
            try {
                if ((bool) ee()->cp->allowed_group($capability)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Ignore and continue with fallbacks.
            }
        }

        return $this->isTruthy($this->sessionValue($capability))
            || $this->isTruthy($this->sessionValue('can_'.$capability));
    }

    private function session()
    {
        if (! function_exists('ee')) {
            return;
        }

        return ee()->session ?? null;
    }

    private function sessionValue(string $key)
    {
        $session = $this->session();
        if (! $session) {
            return;
        }

        if (method_exists($session, 'userdata')) {
            try {
                $value = $session->userdata($key);
                if ($value !== null && $value !== '') {
                    return $value;
                }
            } catch (\Throwable $e) {
                // Ignore and try other access patterns.
            }
        }

        if (isset($session->userdata) && is_array($session->userdata) && array_key_exists($key, $session->userdata)) {
            return $session->userdata[$key];
        }

        if (isset($session->{$key})) {
            return $session->{$key};
        }

    }

    private function lookupRoleNameByGroupId(int $groupId): ?string
    {
        if (! function_exists('ee') || ! isset(ee()->db)) {
            return null;
        }

        try {
            $query = ee()->db
                ->select('group_title')
                ->where('group_id', $groupId)
                ->limit(1)
                ->get('member_groups');

            if ($query->num_rows() === 0) {
                return null;
            }

            $title = $query->row('group_title');

            return $this->toRoleName($title);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toPositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $intVal = (int) $value;

        return $intVal > 0 ? $intVal : null;
    }

    private function toRoleName($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function isTruthy($value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, ['y', 'yes', 'true', 'on'], true);
    }
}
