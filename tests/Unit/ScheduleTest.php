<?php

declare(strict_types=1);

namespace Geckoboom\Scheduler\Test\Unit;

use DG\BypassFinals;
use Geckoboom\Scheduler\CommandBuilder;
use Geckoboom\Scheduler\Event;
use Geckoboom\Scheduler\EventMutexInterface;
use Geckoboom\Scheduler\Schedule;
use Geckoboom\Scheduler\ScheduleMutexInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;

class ScheduleTest extends TestCase
{
    private MockObject $eventMutex;
    private MockObject $scheduleMutex;
    private MockObject $commandBuilder;
    private string $basePath = '/';
    private \DateTimeZone $timezone;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();

        $this->eventMutex = $this->createMock(EventMutexInterface::class);
        $this->scheduleMutex = $this->createMock(ScheduleMutexInterface::class);
        $this->commandBuilder = $this->createMock(CommandBuilder::class);
        $this->timezone = new \DateTimeZone('Europe/London');

        $this->schedule = new Schedule(
            $this->eventMutex,
            $this->scheduleMutex,
            $this->commandBuilder,
            $this->basePath,
            $this->timezone
        );
    }

    public function testDueEvents()
    {
        [$dueEvent, $skipEvent]  = [
            $this->createMock(Event::class),
            $this->createMock(Event::class)
        ];

        $dueEvent->expects(self::once())
            ->method('isDue')
            ->willReturn(true);

        $skipEvent->expects(self::once())
            ->method('isDue')
            ->willReturn(false);

        $this->call($this->schedule, function () use ($dueEvent, $skipEvent) {
            $this->events[] = $dueEvent;
            $this->events[] = $skipEvent;
        });

        $actual = $this->schedule->dueEvents();
        self::assertCount(1, $actual);
        self::assertSame($dueEvent, $actual[0]);
    }

    private function call(object $obj, \Closure $callback)
    {
        return $callback->bindTo($obj, \get_class($obj))();
    }

    public function testServerShouldRun()
    {
        $event = $this->createMock(Event::class);
        $time = new \DateTimeImmutable('now', $this->timezone);
        $this->scheduleMutex->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [self::identicalTo($event), self::identicalTo($time)],
                [self::identicalTo($event), self::identicalTo($time)]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->schedule->serverShouldRun($event, $time));
        self::assertFalse($this->schedule->serverShouldRun($event, $time));
    }

    public function testCommand()
    {
        $consoleBinary = 'console';

        $this->commandBuilder->expects(self::once())
            ->method('getConsoleBinary')
            ->willReturn($consoleBinary);

        $expected = \sprintf(
            "'%s' '%s' %s",
            (new PhpExecutableFinder())->find(false),
            $consoleBinary,
            'test:command --test=\'value\' \'arg\''
        );
        $actual = $this->schedule->command('test:command', ['--test' => 'value', 'arg']);
        self::assertEquals($expected, $actual->getCommand());
        self::assertCount(1, $this->schedule->getEvents());
    }

    public function testGetEvents()
    {
        self::assertEmpty($this->schedule->getEvents());
    }
}
