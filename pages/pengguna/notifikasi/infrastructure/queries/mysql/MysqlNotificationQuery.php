<?php

require_once __DIR__ . "//../INotificationQuery.php";

class MysqlNotificationQuery implements INotificationQuery
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    //** @return Notification[] */
    public function get_all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM 08_notification_templates");
        $stmt->execute();

        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $returned_value = [];

        foreach ($result as $notification) {
            array_push(
                $returned_value,
                new Notification(
                    $notification["id"],
                    new NotificationContent(
                        $notification["subject_template"],
                        $notification["body_template"],
                        NotificationType::from_string($notification["notification_type"]),
                    )
                )
            );
        }

        return $returned_value;
    }

    public function get_by_id(int $id): ?Notification
    {
        $stmt = $this->db->prepare("SELECT * FROM 08_notification_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        if ($result == null) {
            return null;
        }

        return new Notification(
            $result["id"],
            new NotificationContent(
                $result["subject_template"],
                $result["body_template"],
                NotificationType::from_string($result["notification_type"]),
            )
        );
    }
}
