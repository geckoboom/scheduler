<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler\EventMutex;

use Geckoboom\Scheduler\Event;
use Geckoboom\Scheduler\EventMutexInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheEventMutex implements EventMutexInterface
{
    protected CacheInterface $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param Event $event
     * @return bool
     * @throws InvalidArgumentException
     */
    public function create(Event $event): bool
    {
        if ($this->exists($event)) {
            return false;
        }

        return $this->cache->set($event->getMutexName(), true, $event->getExpiresAt());
    }

    /**
     * @param Event $event
     * @return bool
     * @throws InvalidArgumentException
     */
    public function exists(Event $event): bool
    {
        return $this->cache->has($event->getMutexName());
    }

    /**
     * @param Event $event
     * @return bool
     * @throws InvalidArgumentException
     */
    public function forget(Event $event): bool
    {
        return $this->cache->delete($event->getMutexName());
    }
}
