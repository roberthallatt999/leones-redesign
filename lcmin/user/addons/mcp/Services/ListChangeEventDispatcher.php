<?php

namespace ExpressionEngine\Addons\Mcp\Services;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Mcp\Event\PromptListChangedEvent;
use Mcp\Event\ResourceListChangedEvent;
use Mcp\Event\ResourceTemplateListChangedEvent;
use Mcp\Event\ToolListChangedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Minimal PSR-14 dispatcher that maps MCP SDK list-change events to queued
 * MCP notifications for active stdio sessions.
 */
class ListChangeEventDispatcher implements EventDispatcherInterface
{
    public function __construct(private RuntimeNotificationBridge $bridge)
    {
    }

    public function dispatch(object $event): object
    {
        $method = $this->resolveMethod($event);
        if ($method !== null) {
            $this->bridge->enqueueNotifications([$method]);
        }

        return $event;
    }

    private function resolveMethod(object $event): ?string
    {
        if ($event instanceof ToolListChangedEvent) {
            return RuntimeNotificationBridge::TOOLS_LIST_CHANGED_METHOD;
        }

        if ($event instanceof ResourceListChangedEvent || $event instanceof ResourceTemplateListChangedEvent) {
            return RuntimeNotificationBridge::RESOURCES_LIST_CHANGED_METHOD;
        }

        if ($event instanceof PromptListChangedEvent) {
            return RuntimeNotificationBridge::PROMPTS_LIST_CHANGED_METHOD;
        }

        return null;
    }
}
