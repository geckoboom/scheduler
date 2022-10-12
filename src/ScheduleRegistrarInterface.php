<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler;

interface ScheduleRegistrarInterface
{
    public function schedule(Schedule $schedule): void;
}
