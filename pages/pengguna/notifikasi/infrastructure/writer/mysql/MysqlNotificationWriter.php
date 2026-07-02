<?php

require_once __DIR__ . "/../INotificationWriter.php";

class MysqlNotificationWriter implements INotificationWriter
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function insert(Notification $notification)
    {
        $stmt = $this->db->prepare("
            INSERT INTO 08_notification_templates (notification_type, subject_template, body_template, is_active) VALUES
            (?, ?, ?, ?)
        ");

        $notification_type = $notification->content->type->to_string();
        $is_active = $notification->is_active ? 1 : 0;

        $stmt->bind_param(
            "sssi",
            $notification_type,
            $notification->content->subject,
            $notification->content->body,
            $is_active
        );

        $stmt->execute();
    }

    public function insert_with_id(Notification $notification)
    {
        $stmt = $this->db->prepare("
            INSERT INTO 08_notification_templates (id, notification_type, subject_template, body_template, is_active) VALUES
            (?, ?, ?, ?, ?)
        ");

        $notification_type = $notification->content->type->to_string();
        $is_active = $notification->is_active ? 1 : 0;

        $stmt->bind_param(
            "isssi",
            $notification->id,
            $notification_type,
            $notification->content->subject,
            $notification->content->body,
            $is_active
        );

        $stmt->execute();
    }
}
