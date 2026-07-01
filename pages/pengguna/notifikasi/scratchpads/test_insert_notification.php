<?php

require_once __DIR__ . "/../../../../config/konek.php";

require_once __DIR__ . "/../infrastructure/writer/mysql/MysqlNotificationLogWriter.php";

require_once __DIR__ . "/../domain/Notification/NotificationLog.php";
require_once __DIR__ . "/../domain/Notification/NotificationContent.php";
require_once __DIR__ . "/../domain/Notification/NotificationType.php";
require_once __DIR__ . "/../domain/Notification/Recipient.php";

$writer = new MysqlNotificationLogWriter($conn);

$log = NotificationLog::pending(
    recipient: new Recipient(
        email: "test@test.com",
        name: "Tester"
    ),
    content: new NotificationContent(
        subject: "Test Notification",
        body: "Ini notifikasi percobaan",
        type: NotificationType::payment_due
    )
);

$writer->insert($log);

echo "Berhasil";