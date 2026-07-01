<?php

require_once __DIR__ . '/../domain/Notification/NotificationLog.php';
require_once __DIR__ . '/../domain/Notification/Recipient.php';
require_once __DIR__ . '/../domain/Notification/NotificationContent.php';
require_once __DIR__ . '/../domain/Notification/NotificationType.php';
require_once __DIR__ . '/../domain/Notification/NotificationChannel.php';
require_once __DIR__ . '/../domain/DTO/PaymentEventData.php';

class NotificationService
{
    private $writer;

    public function __construct($writer)
    {
        $this->writer = $writer;
    }

    /**
     * PAYMENT DUE EVENT
     */
    public function handlePaymentDue(PaymentEventData $data)
    {
        return $this->createFromDomain(
            NotificationType::payment_due,
            $data->user_id,
            new Recipient($data->email, $data->name),
            new NotificationContent(
                type: NotificationType::payment_due,
                subject: "Payment Reminder - {$data->invoice}",
                body: "Invoice {$data->invoice} sebesar Rp {$data->amount} jatuh tempo pada {$data->due_date}"
            )
        );
    }

    /**
     * PAYMENT SUCCESS EVENT
     */
    public function handlePaymentSuccess(PaymentEventData $data)
    {
        return $this->createFromDomain(
            NotificationType::payment_success,
            $data->user_id,
            new Recipient($data->email, $data->name),
            new NotificationContent(
                type: NotificationType::payment_success,
                subject: "Pembayaran Berhasil",
                body: "Pembayaran invoice {$data->invoice} berhasil diproses."
            )
        );
    }

    /**
     * PAYMENT FAILED EVENT
     */
    public function handlePaymentFailed(PaymentEventData $data)
    {
        return $this->createFromDomain(
            NotificationType::payment_failed,
            $data->user_id,
            new Recipient($data->email, $data->name),
            new NotificationContent(
                type: NotificationType::payment_failed,
                subject: "Pembayaran Gagal",
                body: "Pembayaran invoice {$data->invoice} gagal diproses."
            )
        );
    }

    /**
     * CORE CREATION PIPELINE (SINGLE SOURCE OF TRUTH)
     */
    private function createFromDomain($type, $userId, Recipient $recipient, NotificationContent $content)
    {
        $log = NotificationLog::pending(
            recipient: $recipient,
            content: $content,
            user_id: $userId,
            channel: NotificationChannel::inapp,
            notificationId: null
        );

        $this->writer->insert($log);

        return $log;
    }

    /**
     * SIMPLE VALIDATION LAYER
     */
    private function validate(array $data, array $required)
    {
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("$field wajib ada di event");
            }
        }
    }
    public function handlePaymentEvent(array $payload)
    {
        $data = PaymentEventData::fromArray($payload);

        return match ($data->type) {
            'payment_success' => $this->handlePaymentSuccess($data),
            'payment_failed'  => $this->handlePaymentFailed($data),
            'payment_due'     => $this->handlePaymentDue($data),
            default => throw new InvalidArgumentException("Unknown type")
        };
    }
}

