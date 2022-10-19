## Scheduling

You may define all of your scheduled tasks in the `schedule` method of your own realization `ScheduleRegistrarInterface`.
In the example we will schedule a command to be called every day at midnight:
```php
class MyScheduleRegistrar implements \Geckoboom\Scheduler\ScheduleRegistrarInterface
{
    public function schedule(\Geckoboom\Scheduler\Schedule $schedule) : void{
        $schedule->command('orders:distribute', ['--option' => 'option1', 'argument'])->daily();
    }
}
```

### Schedule Frequency Options

| Method                                                | Description                                             |
|-------------------------------------------------------|---------------------------------------------------------|
| ->cron('* * * * *');                                  | Run the task on a custom cron schedule                  |
| ->everyMinute();                                      | Run the task every minute                               |
| ->everyTwoMinutes();                                  | Run the task every two minutes                          |
| ->everyThreeMinutes();                                | Run the task every three minutes                        |
| ->everyFourMinutes();                                 | Run the task every four minutes                         |
| ->everyFiveMinutes();                                 | Run the task every five minutes                         |
| ->everyTenMinutes();                                  | Run the task every ten minutes                          |
| ->everyFifteenMinutes();                              | Run the task every fifteen minutes                      |
| ->everyThirtyMinutes();                               | Run the task every thirty minutes                       |
| ->hourly();                                           | Run the task every hour                                 |
| ->hourlyAt(17);                                       | Run the task every hour at 17 minutes past the hour     |
| ->everyTwoHours();                                    | Run the task every two hours                            |
| ->everyThreeHours();                                  | Run the task every three hours                          |
| ->everyFourHours();                                   | Run the task every four hours                           |
| ->everySixHours();                                    | Run the task every six hours                            |
| ->daily();                                            | Run the task every day at midnight                      |
| ->dailyAt('13:00');                                   | Run the task every day at 13:00                         |
| ->twiceDaily(1, 13);                                  | Run the task daily at 1:00 & 13:00                      |
| ->twiceDailyAt(1, 13, 15);                            | Run the task daily at 1:15 & 13:15                      |
| ->weekly();                                           | Run the task every Sunday at 00:00                      |
| ->weeklyOn(1, '8:00');                                | Run the task every week on Monday at 8:00               |
| ->monthly();                                          | Run the task on the first day of every month at 00:00   |
| ->monthlyOn(4, '15:00');                              | Run the task every month on the 4th at 15:00            |
| ->twiceMonthly(1, 16, '13:00');                       | Run the task monthly on the 1st and 16th at 13:00       |
| ->quarterly();                                        | Run the task on the first day of every quarter at 00:00 |
| ->yearly();                                           | Run the task on the first day of every year at 00:00    |
| ->setTimezone(new \DateTimeZone('America/New_York')); | Set the timezone for the task                           |

These methods may be combined with additional constraints:
```php
// Run once per week on Monday at 1 PM...
$schedule->command('foo')->weekly()->mondays()->at('13:00');
 
// Run hourly from 8 AM to 5 PM on weekdays...
$schedule->command('bar')
          ->weekdays()
          ->hourly()
          ->timezone('Europe/London')
          ->between('8:00', '17:00');
```

A list of additional schedule constraints may be found below

| Method                                 | Description                                           |
|----------------------------------------|-------------------------------------------------------|
| ->weekdays();                          | Limit the task to weekdays                            |
| ->weekends();                          | Limit the task to weekends                            |
| ->sundays();                           | Limit the task to Sunday                              |
| ->mondays();                           | Limit the task to Monday                              |
| ->tuesdays();                          | Limit the task to Tuesday                             |
| ->wednesdays();                        | Limit the task to Wednesday                           |
| ->thursdays();                         | Limit the task to Thursday                            |
| ->fridays();                           | Limit the task to Friday                              |
| ->saturdays();                         | Limit the task to Saturday                            |
| ->days($day ...$days);                 | Limit the task to specific days                       |
| ->between($startTime, $endTime);       | Limit the task to run between start and end times     |
| ->unlessBetween($startTime, $endTime); | Limit the task to not run between start and end times |
| ->when(Closure);                       | Limit the task based on a truth test                  |
| ->skip(Closure);                       | Limit the task based on a truth test                  |
|                                        |                                                       |

### Truth Test Constraints

The `when` method may be used to limit the execution of a task based on the result of a given truth test. 
In other words, if the given closure returns `true`, the task will execute as long as no other constraining conditions
prevent the task from running:

```php
$schedule->command('emails:send')->daily()->when(function () {
    return true;
});
```

The `skip` method may be seen as the inverse of `when`. If the skip method returns `true`, the scheduled task will not 
be executed:
```php
$schedule->command('emails:send')->daily()->skip(function () {
    return true;
});
```