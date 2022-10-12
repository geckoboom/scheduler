<?php
declare(strict_types=1);

namespace Geckoboom\Scheduler\EventBus;

interface EventInterface
{
    public function occurredOn(): \DateTimeImmutable;
}