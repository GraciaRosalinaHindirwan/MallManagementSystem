<?php

require_once __DIR__ . "/../../infrastructure/writer/mysql/MysqlNotificationLogWriter.php";


use PHPUnit\Framework\TestCase;

class NotificationLogTest extends TestCase
{
    private mysqli $db;

    #[Override]
    function setUp(): void
    {
        $this->db = new mysqli("localhost", "root", "", "mall_erp");
        $this->db->begin_transaction();
    }

    function testInsertion()
    {
        $log = new MysqlNotificationLogWriter($this->db);

        $log->insert(NotificationLog::pending(
            recipient: new Recipient("email", "username"),
            content: new NotificationContent("subject", "body", NotificationType::payment_due),
            user_id: 1,
            channel: NotificationChannel::inapp,
            notificationId: "notif-1",
        ));

        $stmt = $this->db->prepare("SELECT * FROM 08_notification_logs");
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        $this->assertNotEmpty($result);
    }


    #[Override]
    function tearDown(): void
    {
        $this->db->rollback();
        $this->db->close();
    }
}
