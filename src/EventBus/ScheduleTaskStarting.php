<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler\EventBus;

use Geckoboom\Scheduler\Event;

class ScheduleTaskStarting implements EventInterface
{
    protected Event $event;
    protected \DateTimeImmutable $occurredOn;

    /**
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
        $this->occurredOn = new \DateTimeImmutable();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
}
