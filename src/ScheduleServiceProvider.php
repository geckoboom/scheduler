<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler;

use Geckoboom\WhirlwindScheduler\Commands\ScheduleFinishCommand;
use Geckoboom\WhirlwindScheduler\Commands\ScheduleRunCommand;
use Geckoboom\WhirlwindScheduler\EventMutex\CacheEventMutex;
use Geckoboom\WhirlwindScheduler\ScheduleMutex\CacheScheduleMutex;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Whirlwind\App\Console\Application;

class ScheduleServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    protected array $provides = [
        Schedule::class,
        ScheduleMutexInterface::class,
        EventMutexInterface::class,
        DefinitionContainerInterface::class,
    ];

    public function provides(string $id): bool
    {
        return \in_array($id, $this->provides);
    }

    public function register(): void
    {
        $this->getContainer()->addShared(
            Schedule::class,
            function (): Schedule {
                $schedule =  new Schedule(
                    $this->getContainer()->get(EventMutexInterface::class),
                    $this->getContainer()->get(ScheduleMutexInterface::class),
                    $this->getContainer(),
                    env('APP_BASE_PATH', ''),
                    $this->getContainer()->get(CommandBuilder::class),
                    new \DateTimeZone(env('APP_TIMEZONE') ?? 'UTC')
                );

                /** @var ScheduleRegistrarInterface $registrar */
                $registrar = $this->getContainer()->get(ScheduleRegistrarInterface::class);
                $registrar->schedule($schedule);

                return $schedule;
            }
        );

        $this->getContainer()->add(
            EventMutexInterface::class,
            fn(): EventMutexInterface => $this->getContainer()->get(CacheEventMutex::class)
        );

        $this->getContainer()->add(
            ScheduleMutexInterface::class,
            fn(): ScheduleMutexInterface => $this->getContainer()->get(CacheScheduleMutex::class)
        );

        $this->getContainer()->addShared(
            DefinitionContainerInterface::class,
            $this->getContainer()
        );
    }

    public function boot(): void
    {
        /** @var Application $app */
        $app = $this->getContainer()->get(Application::class);

        $app->addCommand('schedule:run', ScheduleRunCommand::class);
        $app->addCommand('schedule:finish', ScheduleFinishCommand::class);
    }
}
