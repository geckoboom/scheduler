# Task Scheduling

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [Scheduling](doc/scheduling.md)
   1. [Schedule Frequency Options](doc/scheduling.md#schedule-frequency-options)
   1. [Truth Test Constraints](doc/scheduling.md#truth-test-constraints)
6[Advanced](doc/advanced.md)

## Introduction

Command scheduler is a great approach to managing scheduled console commands on the server.
The package allows you to control your task scheduling withing console application. When using a
scheduler, only a single cron entry is required on your server.

## Installation

This is installable via Composer as This is installable via [Composer](https://getcomposer.org/) as
[nelmio/alice](https://packagist.org/packages/geckoboom/scheduler):

    composer require geckoboom/scheduler

## Configuration

Several steps required to configure scheduler

1. Provide implementation of `Geckoboom\Scheduler\CallerInterface`.
```php
class ReflectionCaller implements \Geckoboom\Scheduler\CallerInterface
{
    protected ReflectionContainer $container;
    
    public function call(\Closure $callback){
        $args = [];
        $reflectionMethod = new \ReflectionMethod($callback, '__invoke');
        $args = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
            if (!$this->container->has($parameter->getName()) && $parameter->isDefaultValueAvailable()) {
                $args[$parameter->getName()] = $parameter->getDefaultValue();
            }
        }
   
        return $this->container->call($callback, $args);     
    }
}
```
2. Provide two implementations of `EventMutexInterface` and `ScheduleMutexInterface`. The package contains default 
realizations of these interfaces based on `Psr\SimpleCache\CacheInterface` dependency. You can do it in such ways
```php
   $container->add(
        \Psr\SimpleCache\CacheInterface::class,
        MyCacheImplementation::class
   );

   $container->add(
        \Geckoboom\Scheduler\EventMutexInterface::class,
        \Geckoboom\Scheduler\EventMutex\CacheEventMutex::class
   );

   $container->add(
        \Geckoboom\Scheduler\ScheduleMutexInterface::class,
        \Geckoboom\Scheduler\ScheduleMutex\CacheScheduleMutex::class
   );
```
or create your own realizations
```php
    $container->add(
           \Geckoboom\Scheduler\EventMutexInterface::class,
           MyEventMutexImplementation::class
    );

    $container->add(
        \Geckoboom\Scheduler\ScheduleMutexInterface::class,
        MyScheduleMutexImplementation::class
    );
 ```
3. Provide your `ScheduleRegistrarInterface` dependency.
```php
class MyScheduleRegistrar implements \Geckoboom\Scheduler\ScheduleRegistrarInterface
{
    public function schedule(\Geckoboom\Scheduler\Schedule $schedule) : void{
    
    }
}

...

$container->add(
    \Geckoboom\Scheduler\ScheduleRegistrarInterface::class,
    MyScheduleRegistrar::class
);
```
4. Provider `Schedule` singleton dependency

```php
   $container->addShared(
        \Geckoboom\Scheduler\Schedule::class,
        function ($di): \Geckoboom\Scheduler\Schedule {
            $schedule = new \Geckoboom\Scheduler\Schedule(
                $di->get(\Geckoboom\Scheduler\EventMutexInterface::class),
                $di->get(\Geckoboom\Scheduler\ScheduleMutexInterface::class),
                $di->get(\Geckoboom\Scheduler\CommandBuilder::class),
                '/path/to/project/root',
                new DateTimeZone('Europe/London')
            );
            
            $registrar = $di->get(\Geckoboom\Scheduler\ScheduleRegistrarInterface::class);
            $registrar->schedule($schedule);
            
            return $schedule;
        }
   );
```

## Usage

Now you can create executable script or console command based on the appropriate framework syntax.
```php
   $service = $container->get(\Geckoboom\Scheduler\ScheduleService::class);

   $service->run(new \DateTimeImmutable());
```
The last step is to add a single cron configuration entry to our server that runs your executable command every 
minute.

```shell
* * * * * cd /path-to-your-project && php /path-to-executable-script >> /dev/null 2>&1
```