<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler;

interface EventMutexInterface
{
    /**
     * Attempt to obtain an event mutex for the given event.
     *
     * @param Event $event
     * @return bool
     */
    public function create(Event $event): bool;

    /**
     * Determine if an event mutex exists for the given event.
     *
     * @param Event $event
     * @return bool
     */
    public function exists(Event $event): bool;

    /**
     * Clear the event mutex for the given event.
     *
     * @param Event $event
     * @return bool
     */
    public function forget(Event $event): bool;
}
