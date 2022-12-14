<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler;

use Symfony\Component\Process\PhpExecutableFinder;

class Schedule
{
    protected array $events = [];
    protected EventMutexInterface $eventMutex;
    protected ScheduleMutexInterface $scheduleMutex;
    protected CommandBuilder $commandBuilder;
    protected string $basePath;
    protected \DateTimeZone $timezone;

    /**
     * @param EventMutexInterface $eventMutex
     * @param ScheduleMutexInterface $scheduleMutex
     * @param CommandBuilder $commandBuilder
     * @param string $basePath
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        EventMutexInterface $eventMutex,
        ScheduleMutexInterface $scheduleMutex,
        CommandBuilder $commandBuilder,
        string $basePath,
        ?\DateTimeZone $timezone = null
    ) {
        $this->eventMutex = $eventMutex;
        $this->scheduleMutex = $scheduleMutex;
        $this->commandBuilder = $commandBuilder;
        $this->basePath = $basePath;
        $this->timezone = $timezone ?? new \DateTimeZone('UTC');
    }

    public function command(string $command, array $parameters): Event
    {
        return $this->exec($this->formatCommandString($command), $parameters);
    }

    protected function formatCommandString(string $command): string
    {
        return \sprintf(
            '%s %s %s',
            ProcessUtils::escapeArgument((new PhpExecutableFinder())->find(false)),
            ProcessUtils::escapeArgument($this->commandBuilder->getConsoleBinary()),
            $command
        );
    }

    /**
     * @param string $command
     * @param array $parameters
     * @return Event
     */
    public function exec(string $command, array $parameters = []): Event
    {
        if (\count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event(
            $this->eventMutex,
            $command,
            $this->commandBuilder,
            $this->timezone
        );

        $event->setBasePath($this->basePath);

        return $event;
    }

    protected function compileParameters(array $parameters): string
    {
        $result = [];
        foreach ($parameters as $key => $value) {
            if (!\is_numeric($value) && !\preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);
            }

            $result[] = \is_numeric($key) ? $value : "{$key}={$value}";
        }

        return \implode(' ', $result);
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return Event[]
     */
    public function dueEvents(): array
    {
        return \array_filter($this->events, static fn (Event $e) => $e->isDue());
    }

    /**
     * @param Event $event
     * @param \DateTimeInterface $time
     * @return bool
     */
    public function serverShouldRun(Event $event, \DateTimeInterface $time): bool
    {
        return $this->scheduleMutex->create($event, $time);
    }
}
