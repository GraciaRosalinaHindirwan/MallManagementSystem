<?php

class NotificationMessage
{
    public string $subject;
    public string $body;

    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }
}
