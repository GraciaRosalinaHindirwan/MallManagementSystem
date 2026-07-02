<?php

require_once __DIR__ . "/NotificationContent.php";


class Notification
{
    public int $id;
    public NotificationContent $content;
    public bool $is_active = true;

    public function __construct(int $id, NotificationContent $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    public static function create(int $id, NotificationContent $content): Notification
    {
        return new Notification(
            id: $id,
            content: $content,
        );
    }

    public function deactivate()
    {
        $this->is_active = false;
    }
}
