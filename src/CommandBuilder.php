<?php

declare(strict_types=1);

namespace Geckoboom\WhirlwindScheduler;

use Symfony\Component\Process\PhpExecutableFinder;

class CommandBuilder
{
    public function buildCommand(Event $event): string
    {
        if ($event->isBackgroundRunnable()) {
            return $this->buildBackgroundCommand($event);
        }

        return $this->buildForegroundCommand($event);
    }

    protected function buildBackgroundCommand(Event $event): string
    {
        $output = ProcessUtils::escapeArgument($event->getOutput());

        $finished = $this->formatCommandString('schedule:finish') . ' "' . $event->getMutexName() . '"';

        if ($this->isWindowsOs()) {
            return 'start /b cmd /c "(' . $event->getCommand() . ' & ' . $finished . ' "%errorlevel%") >> '
                . $output . ' 2>&1"';
        }

        return $this->ensureCorrectUser(
            $event,
            '(' . $event->getCommand() . ' >> ' . $output . ' 2>&1 ; ' . $finished . ' "$?") > '
            . ProcessUtils::escapeArgument($event->getDefaultOutput()) . ' 2>&1 &'
        );
    }

    private function isWindowsOs(): bool
    {
        return \PHP_OS_FAMILY === 'Windows';
    }

    protected function formatCommandString(string $string): string
    {
        return \sprintf(
            '%s %s %s',
            ProcessUtils::escapeArgument((new PhpExecutableFinder())->find(false)),
            \defined('CONSOLE_BINARY')
                ? ProcessUtils::escapeArgument(CONSOLE_BINARY)
                : 'src/App/Console/console.php',
            $string
        );
    }

    protected function ensureCorrectUser(Event $event, $command): string
    {
        return $event->getUser() && !$this->isWindowsOs()
            ? 'sudo -u ' . $event->getUser() . ' -- sh -c \'' . $command . '\''
            : $command;
    }

    protected function buildForegroundCommand(Event $event): string
    {
        $output = ProcessUtils::escapeArgument($event->getOutput());

        return $this->ensureCorrectUser(
            $event,
            $event->getCommand() . ' >> ' . $output . ' 2>&1'
        );
    }
}
