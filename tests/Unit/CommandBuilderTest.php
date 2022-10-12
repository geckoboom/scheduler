<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler\Test\Unit;

use DG\BypassFinals;
use Geckoboom\WhirlwindScheduler\CommandBuilder;
use Geckoboom\WhirlwindScheduler\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;

class CommandBuilderTest extends TestCase
{
    protected CommandBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        BypassFinals::enable();

        $this->builder = new CommandBuilder();
    }

    /**
     * @param Event $event
     * @param string $expected
     * @return void
     *
     * @dataProvider eventDataProvider
     */
    public function testBuildCommand(Event $event, string $expected)
    {
        \defined('CONSOLE_BINARY') or \define('CONSOLE_BINARY', 'console');

        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Not available for Windows OS');
        }

        $actual = $this->builder->buildCommand($event);

        self::assertEquals($expected, $actual);
    }

    public function eventDataProvider(): array
    {
        $executableString = '\'' . (new PhpExecutableFinder())->find(false) . '\'' . ' \'console\'';

        return [
            [
                'event' => $this->createEventMock(
                    true,
                    'background-mutex',
                    'test:background',
                    'root'
                ),
                'expected' => 'sudo -u root -- sh -c \'(test:background >> \'/dev/null\' 2>&1 ; ' . $executableString
                    . ' schedule:finish "background-mutex" "$?") > \'/dev/null\' 2>&1 &\'',
            ],
            [
                'event' => $this->createEventMock(
                    false,
                    'foreground-mutex',
                    'test:foreground',
                    ''
                ),
                'expected' => 'test:foreground >> \'/dev/null\' 2>&1',
            ]
        ];
    }

    /**
     * @param bool $isBackgroundRunnable
     * @param string $mutexName
     * @param string $command
     * @param string $user
     * @return MockObject&Event
     */
    private function createEventMock(
        bool $isBackgroundRunnable,
        string $mutexName,
        string $command,
        string $user
    ): MockObject {
        $event = $this->createMock(Event::class);

        $event->expects(self::any())
            ->method('isBackgroundRunnable')
            ->willReturn($isBackgroundRunnable);

        $event->expects(self::any())
            ->method('getOutput')
            ->willReturn('/dev/null');

        $event->expects(self::any())
            ->method('getMutexName')
            ->willReturn($mutexName);

        $event->expects(self::any())
            ->method('getCommand')
            ->willReturn($command);

        $event->expects(self::any())
            ->method('getDefaultOutput')
            ->willReturn('/dev/null');

        $event->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        return $event;
    }
}
