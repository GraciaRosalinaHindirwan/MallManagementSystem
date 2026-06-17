<?php

class Notification
{
    public int $id;
    public NotificationContent $content;
    public DateTime $date_time;

    public function __construct(int $id, NotificationContent $content, DateTime $date_time)
    {
        $this->id = $id;
        $this->content = $content;
        $this->date_time = $date_time;
    }
}
