<?php
declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler\Test\Unit;

use DG\BypassFinals;
use Geckoboom\WhirlwindScheduler\CommandBuilder;
use Geckoboom\WhirlwindScheduler\Event;
use Geckoboom\WhirlwindScheduler\EventMutexInterface;
use League\Container\DefinitionContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;

class EventTest extends TestCase
{
    protected MockObject $eventMutex;
    protected string $command = 'test:command';
    protected MockObject $commandBuilder;
    protected \DateTimeZone $timezone;
    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();

        $this->eventMutex = $this->createMock(EventMutexInterface::class);
        $this->commandBuilder = $this->createMock(CommandBuilder::class);
        $this->timezone = new \DateTimeZone('Europe/London');

        $this->event = new Event(
            $this->eventMutex,
            $this->command,
            $this->commandBuilder,
            $this->timezone
        );
    }

    public function testCallBeforeCallbacks()
    {
        $this->event->before(function () {
            self::assertTrue(true);
        });
        $container = $this->createMock(DefinitionContainerInterface::class);
        $this->event->callBeforeCallbacks($container);
    }

    public function testIsDue()
    {
        self::assertTrue($this->event->isDue());
        $now = new \DateTimeImmutable('+ 1 day', $this->timezone);
        $this->event->cron(\sprintf('* * %d * *', $now->format('d')));
        self::assertFalse($this->event->isDue());
    }

    public function testGetOutput()
    {
        $expected = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';

        self::assertEquals($expected, $this->event->getOutput());
    }

    public function testBefore()
    {
        $expected = function () {};
        $this->event->before($expected);

        [$actual] = $callbacks = $this->call($this->event, function () {
            return $this->beforeCallbacks;
        });
        self::assertCount(1, $callbacks);
        self::assertSame($expected, $actual);
    }

    private function call(object $obj, \Closure $callback)
    {
        return $callback->bindTo($obj, \get_class($obj))();
    }

    public function testCallAfterCallbacks()
    {
        $this->event->after(function () {
            self::assertTrue(true);
        });
        $container = $this->createMock(DefinitionContainerInterface::class);
        $this->event->callAfterCallbacks($container);
    }

    public function testEveryTwoMinutes()
    {
        $this->event->everyTwoMinutes();

        $expected = '*/2 * * * *';
        $actual = $this->call($this->event, function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testWeekdays()
    {
        $expected = '* * * * 1-5';
        $actual = $this->call($this->event->weekdays(), function () {
            return $this->expression;
        });

        self::assertEquals($expected, $actual);
    }

    public function testTuesdays()
    {
        $expected = '* * * * 2';
        $actual = $this->call($this->event->tuesdays(), function () {
            return $this->expression;
        });

        self::assertEquals($expected, $actual);
    }

    public function testWithoutOverlapping()
    {
        $this->event->withoutOverlapping();
        self::assertFalse($this->call($this->event, function () {
            return $this->isOverlapping;
        }));
    }

    public function testWithoutOverlappingExpiresAtChanged()
    {
        $expected = 100;
        $this->event->withoutOverlapping($expected);

        self::assertEquals($expected, $this->event->getExpiresAt());
    }

    public function testWithoutOverlappingAfterCallbackRegistered()
    {
        $this->event->withoutOverlapping();

        self::assertCount(1, $this->call($this->event, function () {
            return $this->afterCallbacks;
        }));
    }

    public function testGetCommand()
    {
        self::assertEquals($this->command, $this->event->getCommand());
    }

    public function testGetSummary()
    {
        $this->commandBuilder->expects(self::any())
            ->method('buildCommand')
            ->with(self::identicalTo($this->event))
            ->willReturn($this->command);

        self::assertEquals($this->command, $this->event->getSummary());
    }

    public function testGetSummaryWithDescription()
    {
        $expected = 'Test description';
        $this->event->setDescription($expected);

        self::assertEquals($expected, $this->event->getSummary());
    }

    public function testGetExitCode()
    {
        self::assertNull($this->event->getExitCode());
    }

    public function testFridays()
    {
        $expected = '* * * * 5';
        $actual = $this->call($this->event->fridays(), function () {
            return $this->expression;
        });

        self::assertEquals($expected, $actual);
    }

    public function testEveryThirtyMinutes()
    {
        $expected = '0,30 * * * *';
        $actual = $this->call($this->event->everyThirtyMinutes(), function () {
            return $this->expression;
        });

        self::assertEquals($expected, $actual);
    }

    public function testSetBasePath()
    {
        $expected = '/';
        $this->event->setBasePath($expected);
        $actual = $this->call($this->event, function () {
            return $this->basePath;
        });

        self::assertEquals($expected, $actual);
    }

    public function testEveryFiveMinutes()
    {
        $expected = '*/5 * * * *';
        $actual = $this->call($this->event->everyFiveMinutes(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testWeekly()
    {
        $expected = '0 0 * * 0';
        $actual = $this->call($this->event->weekly(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testThursdays()
    {
        $expected = '* * * * 4';
        $actual = $this->call($this->event->thursdays(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testSkip()
    {
        $expected = function () {};
        $this->event->skip($expected);
        [$actual] = $rejects = $this->call($this->event, function () {
            return $this->rejects;
        });
        self::assertCount(1, $rejects);
        self::assertSame($expected, $actual);
    }

    public function testSetDescription()
    {
        $expected = 'Test';
        $this->event->setDescription($expected);
        $actual = $this->call($this->event, function () {
            return $this->description;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEveryTenMinutes()
    {
        $expected = '*/10 * * * *';
        $actual = $this->call($this->event->everyTenMinutes(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEverySixHours()
    {
        $expected = '0 */6 * * *';
        $actual = $this->call($this->event->everySixHours(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testWeekends()
    {
        $expected = '* * * * 0,6';
        $actual = $this->call($this->event->weekends(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testQuarterly()
    {
        $expected = '0 0 1 1-12/3 *';
        $actual = $this->call($this->event->quarterly(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testMondays()
    {
        $expected = '* * * * 1';
        $actual = $this->call($this->event->mondays(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testIsOneServer()
    {
        self::assertFalse($this->event->isOneServer());
    }

    public function testHourly()
    {
        $expected = '0 * * * *';
        $actual = $this->call($this->event->hourly(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testIsBackgroundRunnable()
    {
        self::assertFalse($this->event->isBackgroundRunnable());
    }

    public function testTwiceDaily()
    {
        $expected = '0 1,13 * * *';
        $actual = $this->call($this->event->twiceDaily(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testTwiceMonthly()
    {
        $expected = '0 0 1,16 * *';
        $actual = $this->call($this->event->twiceMonthly(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEveryFifteenMinutes()
    {
        $expected = '*/15 * * * *';
        $actual = $this->call($this->event->everyFifteenMinutes(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testCron()
    {
        $expected = '0 0 1,16 * *';
        $actual = $this->call($this->event->cron($expected), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testWednesdays()
    {
        $expected = '* * * * 3';
        $actual = $this->call($this->event->wednesdays(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testRunInBackground()
    {
        self::assertTrue($this->call($this->event->runInBackground(), function () {
            return $this->isBackgroundRunnable;
        }));
    }

    public function testGetMutexName()
    {
        $expected = 'event-* * * * *' . $this->command;
        $actual = $this->event->getMutexName();

        self::assertEquals($expected, $actual);
    }

    public function testRun()
    {
        $container = $this->createMock(DefinitionContainerInterface::class);

        $this->commandBuilder->expects(self::once())
            ->method('buildCommand')
            ->with(self::identicalTo($this->event))
            ->willReturn('echo "Test"');

        $this->event->setBasePath(__DIR__);
        $this->event->run($container);
        self::assertEquals(0, $this->event->getExitCode());
    }

    public function testRunCommandInBackground()
    {
        \define('CONSOLE_BINARY', __DIR__ . '/console_stub');
        $container = $this->createMock(DefinitionContainerInterface::class);

        $finish = \sprintf(
            "'%s' '%s' schedule:finish \"event-* * * * *{$this->command}\"",
            (new PhpExecutableFinder())->find(false),
            CONSOLE_BINARY
        );
        $commandString = '(echo "Test" >> \'/dev/null\' 2>&1 ; ' . $finish . ' "$?") > \'/dev/null\' 2>&1 &';
        $this->commandBuilder->expects(self::once())
            ->method('buildCommand')
            ->with(self::identicalTo($this->event))
            ->willReturn($commandString);

        $this->event->setBasePath(__DIR__);
        $this->event->run($container);
        self::assertEquals(0, $this->event->getExitCode());
    }

    public function testRunWithoutOverlapping()
    {
        $container = $this->createMock(DefinitionContainerInterface::class);

        $this->event->withoutOverlapping();

        $this->eventMutex->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($this->event))
            ->willReturn(false);

        $this->event->run($container);
        self::assertEquals(null, $this->event->getExitCode());
    }

    public function testRunWithoutOverlappingForgotMutex()
    {
        $container = $this->createMock(DefinitionContainerInterface::class);

        $this->event->withoutOverlapping();

        $this->eventMutex->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($this->event))
            ->willReturn(true);

        $this->eventMutex->expects(self::once())
            ->method('forget')
            ->with(self::identicalTo($this->event))
            ->willReturn(true);

        $this->commandBuilder->expects(self::once())
            ->method('buildCommand')
            ->with(self::identicalTo($this->event))
            ->willReturn('echo "Test"');

        $this->event->setBasePath(__DIR__);
        $this->event->run($container);
        self::assertEquals(0, $this->event->getExitCode());
    }

    public function testYearly()
    {
        $expected = '0 0 1 1 *';
        $actual = $this->call($this->event->yearly(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testSetUser()
    {
        $expected = 'root';
        $this->event->setUser($expected);
        self::assertEquals($expected, $this->event->getUser());
    }

    public function testAfter()
    {
        $expected = function () {};
        $this->event->after($expected);
        [$actual] = $afterCallbacks = $this->call($this->event, function () {
            return $this->afterCallbacks;
        });
        self::assertCount(1, $afterCallbacks);
        self::assertSame($expected, $actual);
    }

    /**
     * @param string $startTime
     * @param string $endTime
     * @param bool $inclusive
     * @param bool $expected
     * @return void
     *
     * @dataProvider unlessBetweenDataProvider
     */
    public function testUnlessBetween(string $startTime, string $endTime, bool $inclusive, bool $expected)
    {
        $this->event->unlessBetween($startTime, $endTime, $inclusive);
        $callback = $this->call($this->event, function () {
            return \reset($this->rejects);
        }) ;
        self::assertEquals($expected, $callback());
    }

    public function unlessBetweenDataProvider(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/London'));

        return [
            [
                'startTime' => $now->modify('-1 hour')->format('H:i'),
                'endTime' => $now->modify('+1 hour')->format('H:i'),
                'inclusive' => true,
                'expected' => true,
            ],
            [
                'startTime' => $now->modify('+1 hour')->format('H:i'),
                'endTime' => $now->modify('+2 hour')->format('H:i'),
                'inclusive' => false,
                'expected' => false,
            ],
            [
                'startTime' => $now->modify('-2 hour')->format('H:i'),
                'endTime' => $now->modify('-1 hour')->format('H:i'),
                'inclusive' => false,
                'expected' => false,
            ],
        ];
    }

    public function testGetExpiresAt()
    {
        self::assertEquals(1440, $this->event->getExpiresAt());
    }

    public function testDays()
    {
        $expected = '* * * * 3';
        $actual = $this->call($this->event->days(3), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);

        $expected = '* * * * 3,5';
        $actual = $this->call($this->event->days(3, 5), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testSaturdays()
    {
        $expected = '* * * * 6';
        $actual = $this->call($this->event->saturdays(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testHourlyAt()
    {
        $expected = '2 * * * *';
        $actual = $this->call($this->event->hourlyAt(2), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);

        $expected = '5,45 * * * *';
        $actual = $this->call($this->event->hourlyAt(5, 45), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testMonthlyOn()
    {
        $expected = '0 0 1 * *';
        $actual = $this->call($this->event->monthlyOn(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);

        $expected = '21 13 28 * *';
        $actual = $this->call($this->event->monthlyOn(28,'13:21'), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testUseOneServer()
    {
        self::assertTrue($this->event->useOneServer()->isOneServer());
    }

    public function testEveryFourHours()
    {
        $expected = '0 */4 * * *';
        $actual = $this->call($this->event->everyFourHours(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEveryThreeMinutes()
    {
        $expected = '*/3 * * * *';
        $actual = $this->call($this->event->everyThreeMinutes(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testSetEventMutex()
    {
        $expected = $this->createMock(EventMutexInterface::class);
        $this->event->setEventMutex($expected);
        $actual = $this->call($this->event, function () {
            return $this->eventMutex;
        });
        self::assertSame($expected, $actual);
    }

    public function testGetDefaultOutput()
    {
        $expected = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';

        self::assertEquals($expected, $this->event->getDefaultOutput());
    }

    public function testWhen()
    {
        $expected = function () {};
        $this->event->when($expected);

        [$actual] = $filters = $this->call($this->event, function () {
            return $this->filters;
        });
        self::assertCount(1, $filters);
        self::assertSame($expected, $actual);
    }

    public function testWeeklyOn()
    {
        $expected = '0 0 * * 5';
        $actual = $this->call($this->event->weeklyOn(5), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    /**
     * @param string $startTime
     * @param string $endTime
     * @param bool $inclusive
     * @param bool $expected
     * @return void
     *
     * @dataProvider unlessBetweenDataProvider
     */
    public function testBetween(string $startTime, string $endTime, bool $inclusive, bool $expected)
    {
        $this->event->between($startTime, $endTime, $inclusive);

        $callback = $this->call($this->event, function () {
            return \reset($this->filters);
        });
        self::assertEquals($expected, $callback());
    }

    public function testMonthly()
    {
        $expected = '0 0 1 * *';
        $actual = $this->call($this->event->monthly(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testDailyAt()
    {
        $expected = '25 14 * * *';
        $actual = $this->call($this->event->dailyAt('14:25'), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testSundays()
    {
        $expected = '* * * * 0';
        $actual = $this->call($this->event->sundays(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testGetUser()
    {
        self::assertEmpty($this->event->getUser());
    }

    public function testAt()
    {
        $expected = '47 16 * * *';
        $actual = $this->call($this->event->at('16:47'), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testSetTimezone()
    {
        $expected = new \DateTimeZone('Europe/Warsaw');
        $this->event->setTimezone($expected);
        self::assertSame($expected, $this->call($this->event, function () {
            return $this->timezone;
        }));
    }

    /**
     * @param \Closure|null $filter
     * @param \Closure|null $reject
     * @param bool $expected
     * @return void
     *
     * @dataProvider filterPassDataProvider
     */
    public function testFiltersPass(?\Closure $filter, ?\Closure $reject, bool $expected)
    {
        $container = $this->createMock(DefinitionContainerInterface::class);

        if ($filter) {
            $this->event->when($filter);
        }

        if ($reject) {
            $this->event->skip($reject);
        }

        self::assertEquals($expected, $this->event->filtersPass($container));
    }

    public function filterPassDataProvider(): array
    {
        return [
            [
                'filter' => null,
                'reject' => null,
                'expected' => true,
            ],
            [
                'filter' => function () { return true; },
                'reject' => null,
                'expected' => true,
            ],
            [
                'filter' => function () { return false; },
                'reject' => null,
                'expected' => false,
            ],
            [
                'filter' => null,
                'reject' => function () { return true; },
                'expected' => false,
            ],
            [
                'filter' => null,
                'reject' => function () { return false; },
                'expected' => true,
            ],
            [
                'filter' => function () { return true; },
                'reject' => function () { return true; },
                'expected' => false,
            ],
            [
                'filter' => function () { return true; },
                'reject' => function () { return false; },
                'expected' => true,
            ],
        ];
    }

    public function testEveryFourMinutes()
    {
        $expected = '*/4 * * * *';
        $actual = $this->call($this->event->everyFourMinutes(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEveryTwoHours()
    {
        $expected = '0 */2 * * *';
        $actual = $this->call($this->event->everyTwoHours(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testDaily()
    {
        $expected = '0 0 * * *';
        $actual = $this->call($this->event->daily(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEveryMinute()
    {
        $expected = '* * * * *';
        $actual = $this->call($this->event->everyMinute(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }

    public function testEveryThreeHours()
    {
        $expected = '0 */3 * * *';
        $actual = $this->call($this->event->everyThreeHours(), function () {
            return $this->expression;
        });
        self::assertEquals($expected, $actual);
    }
}
