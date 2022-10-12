<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler\ScheduleMutex;

use DateTimeInterface;
use Geckoboom\Scheduler\Event;
use Geckoboom\Scheduler\ScheduleMutexInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheScheduleMutex implements ScheduleMutexInterface
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
     * @param DateTimeInterface $time
     * @return bool
     * @throws InvalidArgumentException
     */
    public function create(Event $event, DateTimeInterface $time): bool
    {
        $cacheKey = $event->getMutexName() . $time->format('Hi');

        if ($this->cache->has($cacheKey)) {
            return false;
        }

        return $this->cache->set($cacheKey, true, 3600);
    }

    /**
     * @param Event $event
     * @param DateTimeInterface $time
     * @return bool
     * @throws InvalidArgumentException
     */
    public function exists(Event $event, DateTimeInterface $time): bool
    {
        return $this->cache->has($event->getMutexName() . $time->format('Hi'));
    }
}
