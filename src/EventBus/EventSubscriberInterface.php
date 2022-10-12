<?php
declare(strict_types=1);

namespace Geckoboom\Scheduler\EventBus;

interface EventSubscriberInterface
{
    public function handle(EventInterface $event): void;

    public function isSubscribedTo(EventInterface $event): bool;
}