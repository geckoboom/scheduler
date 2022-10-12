# Task Scheduling

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Scheduling](doc/scheduling.md)
   1. [Schedule Frequency Options](doc/scheduling.md#schedule-frequency-options)
   1. [Truth Test Constraints](doc/scheduling.md#truth-test-constraints)
5. [Advanced](doc/advanced.md)

## Introduction

Whirlwind's command scheduler is a great approach to managing scheduled console commands on the server.
The package allows you to control your task scheduling withing Whirlwind console application. When using a
scheduler, only a single cron entry is required on your server.

## Installation

This is installable via Composer as This is installable via [Composer](https://getcomposer.org/) as
[nelmio/alice](https://packagist.org/packages/geckoboom/whirlwind-scheduler):

    composer require geckoboom/whirlwind-scheduler

## Configuration

Several steps required to configure scheduler

1. Setup your project environment with variables `APP_BASE_PATH` and `APP_TIMEZONE`. The `APP_BASE_PATH` variable 
defines path to your project root folder, while the `APP_TIMEZONE` defines server time zone for your scheduler. 
By default the `APP_TIMEZONE` variable use `UTC` timezone and it can be omitted during environment configuration.
2. `ScheduleServiceProvider` register two implementations `EventMutexInterface` and `ScheduleMutexInterface`.
   - By default, these realizations based on data storing in cache. In such way you have to ensure that you provide 
   `Psr\SimpleCache\CacheInterface` dependency. 
   
   ```php
    $container->add(
        Psr\SimpleCache\CacheInterface::class,
        static function (): Psr\SimpleCache\CacheInterface
        {
            // return your dependency
        }
    );
    ```
   
   - If you need your own realizations of `EventMutexInterface` and/or `ScheduleMutexInterface` you can override it
   
   ```php
   $container->add(
       EventMutexInterface::class,
       static function (): EventMutexInterface
       {
           // return your dependency
       }
   );
    
   $container->add(
        ScheduleMutexInterface::class,
        static function (): ScheduleMutexInterface
        {
            // return your dependency
        }
   )
   ```

3. Provide your `ScheduleRegistrarInterface` dependency.
4. Make sure that you provide shared `Whirlwind\App\Console\Application::class` dependency.

```php
class MyScheduleRegistrar implements \Geckoboom\WhirlwindScheduler\ScheduleRegistrarInterface
{
    public function schedule(\Geckoboom\WhirlwindScheduler\Schedule $schedule) : void{
    
    }
}

...

$container->add(
    \Geckoboom\WhirlwindScheduler\ScheduleRegistrarInterface::class,
    MyScheduleRegistrar::class
);
```
