<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler\Commands;

use Geckoboom\WhirlwindScheduler\Event;
use Geckoboom\WhirlwindScheduler\EventBus\ScheduleTaskFailed;
use Geckoboom\WhirlwindScheduler\EventBus\ScheduleTaskFinished;
use Geckoboom\WhirlwindScheduler\EventBus\ScheduleTaskSkipped;
use Geckoboom\WhirlwindScheduler\EventBus\ScheduleTaskStarting;
use Geckoboom\WhirlwindScheduler\Schedule;
use League\Container\DefinitionContainerInterface;
use Whirlwind\Domain\Event\EventDispatcher;

class ScheduleRunCommand extends ConsoleCommand
{
    protected Schedule $schedule;
    protected DefinitionContainerInterface $container;
    protected EventDispatcher $dispatcher;
    protected \DateTimeImmutable $startedAt;

    public function __construct(
        Schedule $schedule,
        DefinitionContainerInterface $container,
        EventDispatcher $dispatcher
    ) {
        parent::__construct();
        $this->schedule = $schedule;
        $this->container = $container;
        $this->dispatcher = $dispatcher;
    }

    public function run(array $params = [])
    {
        $this->startedAt = new \DateTimeImmutable();
        foreach ($this->schedule->dueEvents() as $event) {
            if (!$event->filtersPass($this->container)) {
                $this->dispatcher->dispatch(new ScheduleTaskSkipped($event));

                continue;
            }

            if ($event->isOneServer()) {
                $this->runSingleServerEvent($event);
            } else {
                $this->runEvent($event);
            }
        }
    }

    protected function runSingleServerEvent(Event $event): void
    {
        if ($this->schedule->serverShouldRun($event, $this->startedAt)) {
            $this->runEvent($event);
        } else {
            $this->info('Skipping command (has already run on another server):' . $event->getSummary());
        }
    }

    protected function runEvent(Event $event): void
    {
        $this->info('Running scheduled command: ' . $event->getSummary());

        $this->dispatcher->dispatch(new ScheduleTaskStarting($event));
        $start = \microtime(true);

        try {
            $event->run($this->container);
            $this->dispatcher->dispatch(new ScheduleTaskFinished(
                $event,
                \round(\microtime(true) - $start, 2)
            ));
        } catch (\Throwable $e) {
            $this->error('Scheduled command failed: ' . $e->getMessage());
            $this->dispatcher->dispatch(new ScheduleTaskFailed($event, $e));
        }
    }
}
