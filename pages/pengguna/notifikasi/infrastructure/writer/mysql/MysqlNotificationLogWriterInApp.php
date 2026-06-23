<?php

require_once __DIR__ . "/../INotificationLogWriter.php";
require_once __DIR__ . "/../../../domain/Notification/NotificationLog.php";

class MysqlNotificationLogWriterInApp implements INotificationLogWriter
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function insert(NotificationLog $log)
    {
        // =========================
        // DOMAIN MAPPING (SAFE)
        // =========================
        $notification_id   = $log->notification_id;
        $user_id           = $log->user_id;

        $recipient_email   = $log->recipient->email;
        $recipient_name    = $log->recipient->name;

        $notification_type = $log->notification_content->type->name;
        $subject           = $log->notification_content->subject;
        $message           = $log->notification_content->body;

        $channel           = $log->channel->name;

        $status            = $log->delivery_result->status->name;
        $error_message     = $log->delivery_result->error_message;

        $sent_at = $log->delivery_result->sent_at
            ? $log->delivery_result->sent_at->format('Y-m-d H:i:s')
            : null;

        $created_at = $log->created_at->format('Y-m-d H:i:s');

        // =========================
        // SQL
        // =========================
        $stmt = $this->db->prepare("
            INSERT INTO 08_notification_logs
            (
                notification_id,
                user_id,
                recipient_email,
                recipient_name,
                notification_type,
                subject,
                message,
                channel,
                status,
                error_message,
                sent_at,
                created_at
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new RuntimeException("DB Prepare Failed: " . $this->db->error);
        }

        // =========================
        // BIND PARAM (FIXED ORDER)
        // =========================
        $stmt->bind_param(
            "sissssssssss",
            $notification_id,
            $user_id,
            $recipient_email,
            $recipient_name,
            $notification_type,
            $subject,
            $message,
            $channel,
            $status,
            $error_message,
            $sent_at,
            $created_at
        );

        // =========================
        // EXECUTE
        // =========================
        $stmt->execute();

        if ($stmt->error) {
            throw new RuntimeException("Insert Failed: " . $stmt->error);
        }

        // =========================
        // ASSIGN ID BACK TO DOMAIN
        // =========================
        if ($log->id === 0) {
            $log->assign_id($this->db->insert_id);
        }
    }
}