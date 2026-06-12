<?php

require_once __DIR__ . "/../../domain/User.php";
require_once __DIR__ . "/INotificationQuery.php";

class InMemoryNotifictionQuery implements INotificationQuery
{
    private array $notifications;

    public function __construct(array $notification)
    {
        $this->notifications = $notification;
    }

    public function get_by_id(int $id)
    {
        $filtered = array_values(
            array_filter(
                $this->notifications,
                function (Notification $n) use ($id) {
                    return $n->id == $id;
                }
            )
        );

        return array_shift($filtered);
    }
}
