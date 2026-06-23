<?php

use Crunz\Schedule;

$schedule = new Schedule();
$task = $schedule->run(PHP_BINARY . " " . __DIR__ . "/../scripts/ReceiveContractNotificationScript.php");

/* $task->daily()->at("08:00"); */
$task->everyMinute()
    ->description("notify users about contract expiry");

return $schedule;
