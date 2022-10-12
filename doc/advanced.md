## Advanced

Using the `setTimezone` method, you may specify that a scheduled task's time should be interpreted within a given 
timezone:

```php
$schedule->command('my:command')
    ->setTimezone(new DateTimeZone('America/New_York'))
    ->at('2:00'
```

By default, scheduled tasks will be run even if the previous instance of the task is still running. To prevent this, 
you may use the `withoutOverlapping` method. If needed, you may specify how many minutes must pass before the "without 
overlapping" lock expires. By default, the lock will expire after 24 hours:

```php
$schedule->command('my:command')->withoutOverlapping(10);
```

To indicate that the task should run on only one server, use the `useOneServer` method when defining the scheduled task.
The first server to obtain the task will secure an atomic lock on the job to prevent other servers from running the same
task at the same time:

```php
$schedule->command('my:command')
    ->fridays()
    ->at('17:00')
    ->ueOneServer();
```

By default, multiple tasks scheduled at the same time will execute sequentially based on the order they are defined in 
your `schedule` method. If you have long-running tasks, this may cause subsequent tasks to start much later than 
anticipated. If you would like to run tasks in the background so that they may all run simultaneously, you may use the 
`runInBackground` method:

```php
$schedule->command('my:command')
    ->daily()
    ->runInBackground();
```

