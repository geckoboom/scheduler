<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler;

interface ScheduleRegistrarInterface
{
    public function schedule(Schedule $schedule): void;
}
