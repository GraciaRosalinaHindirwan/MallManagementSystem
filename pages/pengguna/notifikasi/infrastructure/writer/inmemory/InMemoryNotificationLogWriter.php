<?php
require_once __DIR__ . "/../INotificationLogWriter.php";
require_once __DIR__ . "/../../../domain/Notification/NotificationLog.php";

class InMemoryNotificationLogWriter implements INotificationLogWriter
{
    public array $logs;

    public function __construct(array $logs)
    {
        $this->logs = $logs;
    }

    public function get_logs()
    {
        return $this->logs;
    }

    public function insert(NotificationLog $log)
    {
        $inserted_log = $log;

        if ($log->id == 0) {
            $largest_id = array_reduce(
                $this->logs,
                function ($largest, NotificationLog $log) {
                    return max($largest, $log->id);
                },
                0
            );

            $inserted_log->assign_id($largest_id + 1);
        }

        array_push($this->logs, $inserted_log);
    }
}
