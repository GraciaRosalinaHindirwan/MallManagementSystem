<?php

require_once __DIR__ . "/NotificationType.php";
require_once __DIR__ . "/NotificationStatus.php";
require_once __DIR__ . "/NotificationChannel.php";
require_once __DIR__ . "/NotificationContent.php";
require_once __DIR__ . "/Recipient.php";
require_once __DIR__ . "/DeliveryResult.php";

class NotificationLog
{
    public int $id;
    public string $notification_id;
    public Recipient $recipient;
    public NotificationContent $notification_content;
    public NotificationChannel $channel;
    public DeliveryResult $delivery_result;
    public DateTime $created_at;
    public int $user_id;

    private function __construct(
        int $id,
        string $notification_id,
        Recipient $recipient,
        NotificationContent $notification_content,
        NotificationChannel $channel,
        DeliveryResult $delivery_result,
        DateTime $created_at,
        int $user_id
    ) {
        $this->id = $id;
        $this->notification_id = $notification_id;
        $this->recipient = $recipient;
        $this->notification_content = $notification_content;
        $this->channel = $channel;
        $this->delivery_result = $delivery_result;
        $this->created_at = $created_at;
        $this->user_id = $user_id;
    }
    public static function pending(
        Recipient $recipient,
        NotificationContent $content,
        int $user_id,
        NotificationChannel $channel = NotificationChannel::inapp,
        ?string $notificationId = null
    ): self {
        return new self(
            id: 0,
            notification_id: $notificationId ?? uniqid('notif_', true),
            recipient: $recipient,
            notification_content: $content,
            channel: $channel,
            created_at: new DateTime(),
            delivery_result: new DeliveryResult(
                status: NotificationStatus::pending,
                sentAt: new DateTime()
            ),
            user_id: $user_id,
        );
    }
    public function mark_as_sent(?DateTime $sentAt = null): void
    {
        $this->delivery_result = new DeliveryResult(
            status: NotificationStatus::sent,
            sentAt: $sentAt ?? new DateTime(),
        );
    }

    public function mark_as_failed(string $errorMessage): void
    {
        $this->delivery_result = new DeliveryResult(
            status: NotificationStatus::failed,
            errorMessage: $errorMessage,
            sentAt: new DateTime(),
        );
    }

    public function assign_id(int $id): void
    {
        if ($this->id !== 0) {
            throw new \RuntimeException('ID already assigned');
        }
        $this->id = $id;
    }

    public function get_delivery_result_status(): NotificationStatus
    {
        return $this->delivery_result->status;
    }

    public function get_error_messsage(): ?string
    {
        return $this->delivery_result->error_message;
    }

    public function get_sent_at(): ?DateTime
    {
        return $this->delivery_result->sent_at;
    }
    public static function reconstruct(
        int $id,
        string $notification_id,
        Recipient $recipient,
        NotificationContent $notification_content,
        NotificationChannel $channel,
        DeliveryResult $delivery_result,
        DateTime $created_at,
        int $user_id
    ): self {
        $log = new self(
            id: $id,
            notification_id: $notification_id,
            recipient: $recipient,
            notification_content: $notification_content,
            channel: $channel,
            delivery_result: $delivery_result,
            created_at: $created_at,
            user_id: $user_id
        );

        return $log;
    }
}

