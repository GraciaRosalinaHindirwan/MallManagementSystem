<?php

require_once __DIR__ . "/NotificationStatus.php";

class DeliveryResult
{
    public readonly NotificationStatus $status;
    public readonly string|null $error_message;
    public readonly DateTime|null $sent_at;

    public function __construct(
        NotificationStatus $status,
        ?string $errorMessage = null,
        ?DateTime $sentAt = null,
    ) {
        $this->status = $status;
        $this->error_message = $errorMessage;
        $this->sent_at = $sentAt;
    }
}
