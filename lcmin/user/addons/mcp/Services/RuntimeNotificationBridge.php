<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Bridges out-of-process settings changes to active MCP SDK sessions.
 *
 * This writes MCP notifications into each active session's outgoing queue so
 * the stdio transport can flush them to connected clients.
 */
class RuntimeNotificationBridge
{
    public const TOOLS_LIST_CHANGED_METHOD = 'notifications/tools/list_changed';

    public const RESOURCES_LIST_CHANGED_METHOD = 'notifications/resources/list_changed';

    public const PROMPTS_LIST_CHANGED_METHOD = 'notifications/prompts/list_changed';

    private const QUEUE_ROOT_KEY = '_mcp';

    private const QUEUE_KEY = 'outgoing_queue';

    private const SESSION_DIR_SUFFIX = 'mcp/sdk_sessions';

    private const UUID_FILENAME_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';

    /**
     * Returns the shared MCP SDK session directory path.
     */
    public function getSessionDirectory(): string
    {
        $base = defined('PATH_CACHE')
            ? rtrim(PATH_CACHE, '/\\')
            : rtrim(sys_get_temp_dir(), '/\\');

        $dir = $base.DIRECTORY_SEPARATOR.self::SESSION_DIR_SUFFIX;
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Queue list-changed notifications for the provided MCP element types.
     *
     * @param  array<int, string>  $types  supported values: tools|resources|prompts
     */
    public function emitListChangedForTypes(array $types): int
    {
        $methods = [];
        foreach ($types as $type) {
            $mapped = $this->mapTypeToMethod($type);
            if ($mapped !== null) {
                $methods[] = $mapped;
            }
        }

        return $this->enqueueNotifications($methods);
    }

    /**
     * Queue tool/resource/prompt list-changed notifications for active sessions.
     */
    public function emitAllListChanged(): int
    {
        return $this->enqueueNotifications([
            self::TOOLS_LIST_CHANGED_METHOD,
            self::RESOURCES_LIST_CHANGED_METHOD,
            self::PROMPTS_LIST_CHANGED_METHOD,
        ]);
    }

    /**
     * Queue one or more notification methods for all active sessions.
     *
     * @param  array<int, string>  $methods
     */
    public function enqueueNotifications(array $methods): int
    {
        $methods = array_values(array_unique(array_filter($methods, fn ($m) => is_string($m) && $m !== '')));
        if (empty($methods)) {
            return 0;
        }

        $notifications = [];
        foreach ($methods as $method) {
            $payload = $this->buildNotificationPayload($method);
            if ($payload !== null) {
                $notifications[] = $payload;
            }
        }

        if (empty($notifications)) {
            return 0;
        }

        $updatedSessions = 0;
        foreach ($this->sessionFiles() as $filePath) {
            if ($this->appendToSessionQueue($filePath, $notifications)) {
                $updatedSessions++;
            }
        }

        return $updatedSessions;
    }

    /**
     * @return array<int, string>
     */
    private function sessionFiles(): array
    {
        $sessionDir = $this->getSessionDirectory();
        $entries = @scandir($sessionDir);
        if (! is_array($entries)) {
            return [];
        }

        $files = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (! preg_match(self::UUID_FILENAME_PATTERN, $entry)) {
                continue;
            }

            $fullPath = $sessionDir.DIRECTORY_SEPARATOR.$entry;
            if (is_file($fullPath)) {
                $files[] = $fullPath;
            }
        }

        return $files;
    }

    /**
     * @param  array<int, string>  $notifications
     */
    private function appendToSessionQueue(string $sessionPath, array $notifications): bool
    {
        $raw = @file_get_contents($sessionPath);
        if ($raw === false || trim($raw) === '') {
            return false;
        }

        $sessionData = json_decode($raw, true);
        if (! is_array($sessionData)) {
            return false;
        }

        $queueRoot = $sessionData[self::QUEUE_ROOT_KEY] ?? [];
        if (! is_array($queueRoot)) {
            $queueRoot = [];
        }

        $queue = $queueRoot[self::QUEUE_KEY] ?? [];
        if (! is_array($queue)) {
            $queue = [];
        }

        foreach ($notifications as $notification) {
            $queue[] = [
                'message' => $notification,
                'context' => ['type' => 'notification'],
            ];
        }

        $queueRoot[self::QUEUE_KEY] = $queue;
        $sessionData[self::QUEUE_ROOT_KEY] = $queueRoot;

        $encoded = json_encode($sessionData, JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded)) {
            return false;
        }

        $tmpPath = $sessionPath.'.tmp';
        if (@file_put_contents($tmpPath, $encoded, LOCK_EX) === false) {
            return false;
        }

        if (! @rename($tmpPath, $sessionPath)) {
            @unlink($tmpPath);

            return false;
        }

        return true;
    }

    private function buildNotificationPayload(string $method): ?string
    {
        try {
            return json_encode(
                [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mapTypeToMethod(string $type): ?string
    {
        switch (strtolower($type)) {
            case 'tools':
            case 'tool':
                return self::TOOLS_LIST_CHANGED_METHOD;
            case 'resources':
            case 'resource':
                return self::RESOURCES_LIST_CHANGED_METHOD;
            case 'prompts':
            case 'prompt':
                return self::PROMPTS_LIST_CHANGED_METHOD;
            default:
                return null;
        }
    }
}
