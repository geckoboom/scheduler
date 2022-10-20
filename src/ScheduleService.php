<?php
declare(strict_types=1);

namespace Geckoboom\Scheduler;

use Geckoboom\Scheduler\EventBus\EventDispatcher;
use Geckoboom\Scheduler\EventBus\ScheduleTaskFailed;
use Geckoboom\Scheduler\EventBus\ScheduleTaskFinished;
use Geckoboom\Scheduler\EventBus\ScheduleTaskSkipped;
use Geckoboom\Scheduler\EventBus\ScheduleTaskStarting;
use Geckoboom\Scheduler\Exception\SkipEventException;

class ScheduleService
{
    protected Schedule $schedule;
    protected CallerInterface $caller;
    protected EventDispatcher $dispatcher;

    /**
     * @param Schedule $schedule
     * @param CallerInterface $caller
     * @param EventDispatcher $dispatcher
     */
    public function __construct(Schedule $schedule, CallerInterface $caller, EventDispatcher $dispatcher)
    {
        $this->schedule = $schedule;
        $this->caller = $caller;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param \DateTimeImmutable $startedAt
     * @return void
     * @throws SkipEventException
     */
    public function run(\DateTimeImmutable $startedAt): void
    {
        foreach ($this->schedule->dueEvents() as $event) {
            if (!$event->filtersPass($this->caller)) {
                $this->dispatcher->dispatch(new ScheduleTaskSkipped($event));

                continue;
            }

            if ($event->isOneServer()) {
                $this->runSingleServerEvent($event, $startedAt);
            } else {
                $this->runEvent($event);
            }
        }
    }

    /**
     * @param Event $event
     * @param \DateTimeImmutable $startedAt
     * @return void
     * @throws SkipEventException
     */
    public function runSingleServerEvent(Event $event, \DateTimeImmutable $startedAt): void
    {
        if ($this->schedule->serverShouldRun($event, $startedAt)) {
            $this->runEvent($event);
        }

        throw new SkipEventException(
            'Skipping command (has already run on another server): ' . $event->getSummary()
        );
    }

    public function runEvent(Event $event): void
    {
        $this->dispatcher->dispatch(new ScheduleTaskStarting($event));
        $start = \microtime(true);

        try {
            $event->run($this->caller);
            $this->dispatcher->dispatch(new ScheduleTaskFinished(
                $event,
                \round(\microtime(true) - $start, 2)
            ));
        } catch (\Throwable $e) {
            $this->dispatcher->dispatch(new ScheduleTaskFailed($event, $e));
        }
    }
}