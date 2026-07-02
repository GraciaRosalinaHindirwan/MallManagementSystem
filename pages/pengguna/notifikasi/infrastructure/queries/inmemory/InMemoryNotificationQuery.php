<?php

require_once __DIR__ . "/../INotificationQuery.php";

class InMemoryNotificationQuery implements INotificationQuery
{
    private array $_notifications = [];

    public function get_by_id(int $id)
    {
        foreach ($this->_notifications as $notification) {
            if ($notification->id === $id) {
                return $notification;
            }
        }
        return null;
    }

    public function get_all()
    {
        return $this->_notifications;
    }
}
