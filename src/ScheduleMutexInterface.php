<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler;

interface ScheduleMutexInterface
{
    /**
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param Event $event
     * @param \DateTimeInterface $time
     * @return bool
     */
    public function create(Event $event, \DateTimeInterface $time): bool;

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param Event $event
     * @param \DateTimeInterface $time
     * @return bool
     */
    public function exists(Event $event, \DateTimeInterface $time): bool;
}
