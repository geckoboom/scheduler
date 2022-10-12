<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler\EventBus;

use Geckoboom\WhirlwindScheduler\Event;
use Whirlwind\Domain\Event\EventInterface;

class ScheduleTaskFailed implements EventInterface
{
    protected Event $event;
    protected \Throwable $exception;
    protected \DateTimeImmutable $occurredOn;

    /**
     * @param Event $event
     * @param \Throwable $e
     */
    public function __construct(Event $event, \Throwable $e)
    {
        $this->event = $event;
        $this->exception = $e;
        $this->occurredOn = new \DateTimeImmutable();
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
