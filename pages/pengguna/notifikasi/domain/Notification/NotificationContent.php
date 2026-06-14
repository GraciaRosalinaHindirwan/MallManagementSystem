<?php

require_once __DIR__ . "/NotificationType.php";

class NotificationContent
{
    public readonly NotificationType $type;
    public readonly string $subject;
    public readonly string $body;

    public function __construct(string $subject, string $body, NotificationType $type)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->type = $type;
    }
}
