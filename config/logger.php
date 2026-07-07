<?php

function writeLog($username, $activity)
{
    $file = __DIR__ . '/activity_log.json';

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }

    $logs = json_decode(
        file_get_contents($file),
        true
    );

    if (!is_array($logs)) {
    $logs = [];
}

    $logs[] = [
        "username" => $username,
        "activity" => $activity,
        "timestamp" => date("Y-m-d H:i:s")
    ];

    $oneMonthAgo = strtotime('-30 days');

    $logs = array_filter(
        $logs,
        function($log) use ($oneMonthAgo) {
            return strtotime($log['timestamp']) >= $oneMonthAgo;
        }
    );

    file_put_contents(
        $file,
        json_encode(
            array_values($logs),
            JSON_PRETTY_PRINT
        )
    );
}