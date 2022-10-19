<?php
declare(strict_types=1);

namespace Geckoboom\Scheduler;

interface CallerInterface
{
    /**
     * @param \Closure $callback
     * @return mixed
     */
    public function call(\Closure $callback);
}