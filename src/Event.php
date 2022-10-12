<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler;

use Cron\CronExpression;
use Symfony\Component\Process\Process;

class Event
{
    public const DEFAULT_EXPRESSION = '* * * * *';
    /**
     * The event mutex implementation.
     *
     * @var EventMutexInterface
     */
    protected EventMutexInterface $eventMutex;
    /**
     * The command (route) name.
     *
     * @var string
     */
    protected string $command;
    /**
     * Shell command builder
     *
     * @var CommandBuilder
     */
    protected CommandBuilder $commandBuilder;
    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone
     */
    protected \DateTimeZone $timezone;
    /**
     * The human-readable description of the event.
     *
     * @var string
     */
    protected string $description;
    /**
     * The user the command should run as.
     *
     * @var string
     */
    protected string $user = '';
    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    protected string $expression = self::DEFAULT_EXPRESSION;
    /**
     * Indicates if the command should only be allowed to run on one server for each cron expression.
     *
     * @var bool
     */
    protected bool $isOneServer = false;
    /**
     * Indicates if the command should run in background.
     *
     * @var bool
     */
    protected bool $isBackgroundRunnable = false;
    /**
     * Indicates if the command should not overlap itself.
     *
     * @var bool
     */
    protected bool $isOverlapping = true;

    /**
     * The amount of time the mutex should be valid.
     *
     * @var int
     */
    protected int $expiresAt = 1440;
    /**
     * The location that output should be sent to.
     *
     * @var string
     */
    protected string $output = '/dev/null';
    /**
     * Project directory base path
     *
     * @var string
     */
    protected string $basePath = '';
    /**
     * The exit status code of the command.
     *
     * @var int
     */
    protected int $exitCode;
    /**
     * The array of filter callbacks.
     *
     * @var \Closure[]
     */
    protected array $filters = [];
    /**
     * The array of reject callbacks.
     *
     * @var \Closure[]
     */
    protected array $rejects = [];
    /**
     * The array of callbacks to be run before the event is started.
     *
     * @var \Closure[]
     */
    public array $beforeCallbacks = [];
    /**
     * The array of callbacks to be run after the event is finished.
     *
     * @var \Closure[]
     */
    protected array $afterCallbacks = [];

    /**
     * @param EventMutexInterface $eventMutex
     * @param string $command
     * @param CommandBuilder $commandBuilder
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        EventMutexInterface $eventMutex,
        string $command,
        CommandBuilder $commandBuilder,
        ?\DateTimeZone $timezone = null
    ) {
        $this->eventMutex = $eventMutex;
        $this->command = $command;
        $this->commandBuilder = $commandBuilder;
        $this->timezone = $timezone ?? new \DateTimeZone('UTC');

        $this->output = $this->getDefaultOutput();
    }

    /**
     * Get the default output depending on the OS.
     *
     * @return string
     */
    public function getDefaultOutput(): string
    {
        return (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
    }

    /**
     * @return string
     */
    public function getMutexName(): string
    {
        return 'event-' . $this->expression . $this->command;
    }

    /**
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * @param string $expression
     * @return $this
     */
    public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Only run between given times.
     *
     * @param string $startTime "HH:MM" (ie "09:00")
     * @param string $endTime   "HH:MM" (ie "14:30")
     * @param bool $inclusive
     * @return $this
     * @throws \Exception
     */
    public function between(string $startTime, string $endTime, bool $inclusive = true): self
    {
        return $this->when($this->inTimeInterval($startTime, $endTime, $inclusive));
    }

    private function inTimeInterval(string $startTime, string $endTime, bool $inclusive = true): \Closure
    {
        [$now, $startTime, $endTime] = [
            new \DateTimeImmutable('now', $this->timezone),
            $this->parseTime($startTime),
            $this->parseTime($endTime)
        ];

        if ($endTime < $startTime) {
            // account for overnight
            $endTime = $endTime->add(new \DateInterval('P1D'));
        }

        return static function () use ($now, $startTime, $endTime, $inclusive): bool {
            if ($inclusive) {
                return $now >= $startTime && $now <= $endTime;
            }

            return $now > $startTime && $now < $endTime;
        };
    }

    private function parseTime(string $time): \DateTimeImmutable
    {
        [$hour, $minute] = \explode(':', $time, 2);

        return (new \DateTimeImmutable('today', $this->timezone))
            ->add(new \DateInterval("PT{$hour}H{$minute}M"));
    }

    /**
     * Schedule the event to not run between start and end time.
     *
     * @param string $startTime "HH:MM" (ie "09:00")
     * @param string $endTime   "HH:MM" (ie "14:30")
     * @param bool $inclusive
     * @return $this
     */
    public function unlessBetween(string $startTime, string $endTime, bool $inclusive = true): self
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime, $inclusive));
    }

    /**
     * @return $this
     */
    public function everyMinute(): self
    {
        return $this->cron(self::DEFAULT_EXPRESSION);
    }

    /**
     * @return $this
     */
    public function everyTwoMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/2');
    }

    /**
     * @param int $position
     * @param string $value
     * @return $this
     */
    protected function spliceIntoPosition(int $position, string $value): self
    {
        $segments = \explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(\implode(' ', $segments));
    }

    /**
     * @return $this
     */
    public function everyThreeMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/3');
    }

    /**
     * @return $this
     */
    public function everyFourMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/4');
    }

    /**
     * @return $this
     */
    public function everyFiveMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * @return $this
     */
    public function everyTenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * @return $this
     */
    public function everyFifteenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /**
     * @return $this
     */
    public function everyThirtyMinutes(): self
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * @return $this
     */
    public function hourly(): self
    {
        return $this->spliceIntoPosition(1, '0');
    }

    /**
     * @param int $offset
     * @param int ...$offsets
     * @return $this
     */
    public function hourlyAt(int $offset, int ...$offsets): self
    {
        \array_unshift($offsets, $offset);
        $offset = \implode(',', $offsets);

        return $this->spliceIntoPosition(1, $offset);
    }

    /**
     * @return $this
     */
    public function everyTwoHours(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '*/2');
    }

    /**
     * @return $this
     */
    public function everyThreeHours(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '*/3');
    }

    /**
     * @return $this
     */
    public function everyFourHours(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '*/4');
    }

    /**
     * @return $this
     */
    public function everySixHours(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '*/6');
    }

    /**
     * @return $this
     */
    public function daily(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '0');
    }

    /**
     * @param string $time
     * @return $this
     */
    public function at(string $time): self
    {
        return $this->dailyAt($time);
    }

    /**
     * @param string $time
     * @return $this
     */
    public function dailyAt(string $time): self
    {
        $segments = \explode(':', $time);

        return $this->spliceIntoPosition(2, (string) ((int) $segments[0]))
            ->spliceIntoPosition(1, \count($segments) === 2 ? (string) ((int) $segments[1]) : '0');
    }

    /**
     * @param int $first
     * @param int $second
     * @return $this
     */
    public function twiceDaily(int $first = 1, int $second = 13): self
    {
        $hours = $first . ',' . $second;

        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, $hours);
    }

    /**
     * @return $this
     */
    public function weekdays(): self
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * @return $this
     */
    public function weekends(): self
    {
        return $this->spliceIntoPosition(5, '0,6');
    }

    /**
     * @return $this
     */
    public function mondays(): self
    {
        return $this->days(1);
    }

    /**
     * @param int $day
     * @param int ...$days
     * @return $this
     */
    public function days(int $day, int ...$days): self
    {
        \array_unshift($days, $day);

        return $this->spliceIntoPosition(5, \implode(',', $days));
    }

    /**
     * @return $this
     */
    public function tuesdays(): self
    {
        return $this->days(2);
    }

    /**
     * @return $this
     */
    public function wednesdays(): self
    {
        return $this->days(3);
    }

    /**
     * @return $this
     */
    public function thursdays(): self
    {
        return $this->days(4);
    }

    /**
     * @return $this
     */
    public function fridays(): self
    {
        return $this->days(5);
    }

    /**
     * @return $this
     */
    public function saturdays(): self
    {
        return $this->days(6);
    }

    /**
     * @return $this
     */
    public function sundays(): self
    {
        return $this->days(0);
    }

    /**
     * @return $this
     */
    public function weekly(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '0')
            ->spliceIntoPosition(5, '0');
    }

    /**
     * @param int $day
     * @param string $time
     * @return $this
     */
    public function weeklyOn(int $day, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, (string) $day);
    }

    /**
     * @return $this
     */
    public function monthly(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '0')
            ->spliceIntoPosition(3, '1');
    }

    /**
     * @param int $day
     * @param string $time
     * @return $this
     */
    public function monthlyOn(int $day = 1, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, (string) $day);
    }

    /**
     * @param int $first
     * @param int $second
     * @param string $time
     * @return $this
     */
    public function twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0'): self
    {
        $days = $first . ',' . $second;

        $this->dailyAt($time);

        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '0')
            ->spliceIntoPosition(3, $days);
    }

    /**
     * @return $this
     */
    public function quarterly(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '0')
            ->spliceIntoPosition(3, '1')
            ->spliceIntoPosition(4, '1-12/3');
    }

    /**
     * @return $this
     */
    public function yearly(): self
    {
        return $this->spliceIntoPosition(1, '0')
            ->spliceIntoPosition(2, '0')
            ->spliceIntoPosition(3, '1')
            ->spliceIntoPosition(4, '1');
    }

    /**
     * @param \DateTimeZone $timezone
     * @return $this
     */
    public function setTimezone(\DateTimeZone $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return $this
     */
    public function useOneServer(): self
    {
        $this->isOneServer = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function runInBackground(): self
    {
        $this->isBackgroundRunnable = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutOverlapping(int $expiresAt = 1440): self
    {
        $this->isOverlapping = false;

        $this->expiresAt = $expiresAt;

        $this->afterCallbacks[] = function () {
            $this->eventMutex->forget($this);
        };
        return $this;
    }

    public function run(CallerInterface $caller): void
    {
        if (!$this->isOverlapping && !$this->eventMutex->create($this)) {
            return;
        }

        $this->isBackgroundRunnable
            ? $this->runCommandInBackground($caller)
            : $this->runCommandInForeground($caller);
    }

    protected function runCommandInBackground(CallerInterface $caller): void
    {
        $this->callBeforeCallbacks($caller);
        Process::fromShellCommandline(
            $this->commandBuilder->buildCommand($this),
            $this->basePath,
            null,
            null,
            null
        )
            ->run();
    }

    public function callBeforeCallbacks(CallerInterface $caller): void
    {
        foreach ($this->beforeCallbacks as $beforeCallback) {
            $caller->call($beforeCallback);
        }
    }

    protected function runCommandInForeground(CallerInterface $caller): void
    {
        $this->callBeforeCallbacks($caller);
        $this->exitCode = Process::fromShellCommandline(
            $this->commandBuilder->buildCommand($this),
            $this->basePath,
            null,
            null,
            null
        )
                ->run();

        $this->callAfterCallbacks($caller);
    }

    public function callAfterCallbacks(CallerInterface $caller): void
    {
        foreach ($this->afterCallbacks as $afterCallback) {
            $caller->call($afterCallback);
        }
    }

    /**
     * @return bool
     */
    public function isBackgroundRunnable(): bool
    {
        return $this->isBackgroundRunnable;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function before(\Closure $callback): self
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function after(\Closure $callback): self
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    public function isDue(): bool
    {
        $now = new \DateTimeImmutable();
        $now->setTimezone($this->timezone);

        return (new CronExpression($this->expression))->isDue($now->format('Y-m-d H:i:s'));
    }

    /**
     * @return bool
     */
    public function isOneServer(): bool
    {
        return $this->isOneServer;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSummary(): string
    {
        if (isset($this->description)) {
            return $this->description;
        }

        return $this->commandBuilder->buildCommand($this);
    }

    /**
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->exitCode ?? null;
    }

    /**
     * @param EventMutexInterface $eventMutex
     * @return $this
     */
    public function setEventMutex(EventMutexInterface $eventMutex): self
    {
        $this->eventMutex = $eventMutex;

        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function when(\Closure $callback): self
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function skip(\Closure $callback): self
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * @param CallerInterface $caller
     * @return bool
     */
    public function filtersPass(CallerInterface $caller): bool
    {
        foreach ($this->filters as $callback) {
            if (!$caller->call($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if ($caller->call($callback)) {
                return false;
            }
        }

        return true;
    }
}
