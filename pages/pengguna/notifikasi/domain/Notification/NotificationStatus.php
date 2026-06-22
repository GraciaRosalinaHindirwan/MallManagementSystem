<?php
enum NotificationStatus
{
    case sent;
    case failed;
    case pending;

    public function to_string()
    {
        return match ($this) {
            $this::sent => "sent",
            $this::failed => "failed",
            $this::pending => "pending",
        };
    }
}
