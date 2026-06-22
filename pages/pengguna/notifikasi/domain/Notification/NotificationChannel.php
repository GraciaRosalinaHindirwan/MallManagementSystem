<?php

enum NotificationChannel
{
    case email;
    case inapp;

    public function to_string()
    {
        return match ($this) {
            $this::email => "email",
            $this::inapp => "inapp",
        };
    }
}
