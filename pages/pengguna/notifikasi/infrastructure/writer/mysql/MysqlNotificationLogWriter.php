<?php

require_once __DIR__ . "/../INotificationLogWriter.php";

class MysqlNotificationLogWriter implements INotificationLogWriter
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function insert(NotificationLog $log)
    {
        $stmt = $this->db->prepare("
            INSERT INTO 08_notification_logs (
                notification_id,
                recipient_email,
                recipient_name,
                notification_type,
                subject,
                message,
                channel,
                status,
                error_message,
                sent_at,
                created_at,
                user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $notification_type = $log->notification_content->type->to_string();
        $status = $log->get_delivery_result_status()->to_string();
        $error_message = $log->get_error_messsage();
        $sent_at = $log->get_sent_at();
        $channel = $log->channel->to_string();
        $created_at = $log->created_at->format("Y-m-d");

        $stmt->bind_param(
            "sssssssssssi",
            $log->notification_id,
            $log->recipient->email,
            $log->recipient->name,
            $log->notification_content->subject,
            $log->notification_content->body,
            $channel,
            $notification_type,
            $status,
            $error_message,
            $sent_at,
            $created_at,
            $log->user_id
        );

        $stmt->execute();
    }
}
