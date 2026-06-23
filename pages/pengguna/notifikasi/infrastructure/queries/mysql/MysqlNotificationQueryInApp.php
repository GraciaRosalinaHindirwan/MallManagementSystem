<?php

require_once __DIR__ . '/../INotificationQuery.php';

require_once __DIR__ . '/../../../domain/Notification/NotificationLog.php';
require_once __DIR__ . '/../../../domain/Notification/NotificationContent.php';
require_once __DIR__ . '/../../../domain/Notification/Recipient.php';
require_once __DIR__ . '/../../../domain/Notification/DeliveryResult.php';

require_once __DIR__ . '/../../../domain/Notification/NotificationType.php';
require_once __DIR__ . '/../../../domain/Notification/NotificationChannel.php';
require_once __DIR__ . '/../../../domain/Notification/NotificationStatus.php';

class MysqlNotificationQueryInApp implements INotificationQuery
{
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    // =====================================================
    // GET BY ID
    // =====================================================
    public function get_by_id(int $id): ?NotificationLog {
        $stmt = $this->db->prepare("
            SELECT * FROM 08_notification_logs
            WHERE notification_log_id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (!$stmt) {
            throw new RuntimeException($this->db->error);
        }
        if (!$row) {
            return null;
        }

        return $this->mapToDomain($row);
    }

    // =====================================================
    // GET ALL
    // =====================================================
    public function get_all(): array {
        $result = $this->db->query("
            SELECT *
            FROM 08_notification_logs
            ORDER BY created_at DESC
        ");
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $this->mapToDomain($row);
        }
        return $results;
    }

    // =====================================================
    // MAPPING DB → DOMAIN
    // =====================================================
    private function mapToDomain(array $row): NotificationLog {
        // 🔥 SAFE ENUM MAPPING (NO STATIC INLINE ERROR)
        $type = $this->mapNotificationType($row['notification_type']);
        $channel = $this->mapNotificationChannel($row['channel']);
        $status = $this->mapNotificationStatus($row['status']);
        $notificationContent = new NotificationContent(
            subject: $row['subject'],
            body: $row['message'],
            type: $type
        );
        $recipient = new Recipient(
            email: $row['recipient_email'],
            name: $row['recipient_name']
        );
        $deliveryResult = new DeliveryResult(
            status: $status,
            errorMessage: $row['error_message'] ?? null,
            sentAt: !empty($row['sent_at'])
                ? new DateTime($row['sent_at'])
                : null
        );
        return NotificationLog::reconstruct(
            id: (int)$row['notification_log_id'],
            notification_id: $row['notification_id'],
            recipient: $recipient,
            notification_content: $notificationContent,
            channel: $channel,
            delivery_result: $deliveryResult,
            created_at: new DateTime($row['created_at']),
            user_id: (int)$row['user_id']
        );
    }

    // =====================================================
    // ENUM MAPPERS
    // =====================================================

    private function mapNotificationType(string $value): NotificationType {
        return match ($value) {
            'contract_expiry' => NotificationType::contract_expiry,
            'payment_due' => NotificationType::payment_due,
            'approval_request' => NotificationType::approval_request,
            'approval_result' => NotificationType::approval_result,
            // 🔥 SAFE FALLBACK (BIAR TIDAK CRASH)
            default => NotificationType::payment_due
        };
    }
    private function mapNotificationChannel(string $value): NotificationChannel {
        return match ($value) {
            'email' => NotificationChannel::email,
            'inapp' => NotificationChannel::inapp,
            default => throw new Exception("Invalid NotificationChannel: $value")
        };
    }

    private function mapNotificationStatus(string $value): NotificationStatus {
        return match ($value) {
            'sent' => NotificationStatus::sent,
            'failed' => NotificationStatus::failed,
            'pending' => NotificationStatus::pending,
            default => throw new Exception("Invalid NotificationStatus: $value")
        };
    }
    public function get_by_user_id(int $user_id): array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM 08_notification_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $this->mapToDomain($row);
        }
        return $notifications;
    }
}