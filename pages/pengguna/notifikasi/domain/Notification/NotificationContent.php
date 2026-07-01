<?php

require_once __DIR__ . "/NotificationType.php";

class NotificationContent
{
    public NotificationType $type;
    public string $subject;
    public string $body;

    public function __construct(string $subject, string $body, NotificationType $type)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->type = $type;
    }
}
