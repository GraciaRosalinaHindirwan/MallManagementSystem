<?php

class InMemoryNotifictionQuery implements INotificationQuery
{
    private array $notification;

    public function __construct(array $notification)
    {
        $this->notification = $notification;
    }

    public function get_by_id(int $id) {}
}
