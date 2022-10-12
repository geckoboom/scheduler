<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler\EventBus;

use Geckoboom\Scheduler\Event;

class ScheduleTaskFinished implements EventInterface
{
    protected Event $event;
    protected float $duration;
    protected \DateTimeImmutable $occurredOn;

    /**
     * @param Event $event
     * @param float $duration
     */
    public function __construct(Event $event, float $duration)
    {
        $this->event = $event;
        $this->duration = $duration;
        $this->occurredOn = new \DateTimeImmutable();
    }

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

    /**
     * @return float
     */
    public function getDuration(): float
    {
        return $this->duration;
    }
}
