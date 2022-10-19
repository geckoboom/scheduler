<?php
declare(strict_types=1);

namespace Geckoboom\Scheduler\Test\Unit;

use Geckoboom\Scheduler\CallerInterface;

class DummyCaller implements CallerInterface
{
    public function call(\Closure $callback)
    {
        return $callback();
    }
}