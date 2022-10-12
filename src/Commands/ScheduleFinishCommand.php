<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler\Commands;

use Geckoboom\WhirlwindScheduler\Schedule;
use League\Container\DefinitionContainerInterface;

class ScheduleFinishCommand extends ConsoleCommand
{
    protected Schedule $schedule;
    protected DefinitionContainerInterface $container;

    /**
     * @param Schedule $schedule
     * @param DefinitionContainerInterface $container
     */
    public function __construct(Schedule $schedule, DefinitionContainerInterface $container)
    {
        parent::__construct();

        $this->schedule = $schedule;
        $this->container = $container;
    }

    public function run(array $params = []): int
    {
        if (!isset($params[0])) {
            $this->error('event mutex id is required');

            return 1;
        }
        foreach ($this->schedule->getEvents() as $event) {
            if ($params[0] == $event->getMutexName()) {
                $event->callAfterCallbacks($this->container);
            }
        }

        return 0;
    }
}
