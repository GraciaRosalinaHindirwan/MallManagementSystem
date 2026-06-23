<?php

require_once __DIR__ . '/../infrastructure/writer/mysql/MysqlNotificationLogWriterInApp.php';
require_once __DIR__ . '/../domain/Notification/NotificationLog.php';
require_once __DIR__ . '/../domain/Notification/Recipient.php';
require_once __DIR__ . '/../domain/Notification/NotificationContent.php';
require_once __DIR__ . '/../domain/Notification/NotificationType.php';
require_once __DIR__ . '/../domain/Notification/NotificationChannel.php';
require_once __DIR__ . '/../usecase/NotificationService.php';

require_once __DIR__ . '/../../../../config/database.php';

$conn = db();

$writer = new MysqlNotificationLogWriterInApp($conn);
$service = new NotificationService($writer);

/**
 * TEST PAYMENT SUCCESS
 */
$result = $service->handlePaymentSuccess([
    'user_id' => 1,
    'email' => 'test@mail.com',
    'name' => 'Budi',
    'invoice' => 'INV-TEST-001'
]);

echo "SUCCESS PAYMENT SUCCESS\n";
print_r($result);

/**
 * TEST PAYMENT DUE
 */
$result2 = $service->handlePaymentDue([
    'user_id' => 1,
    'email' => 'test@mail.com',
    'name' => 'Budi',
    'invoice_id' => 'INV-DUE-001',
    'amount' => 150000,
    'due_date' => '2026-06-30'
]);

echo "\nSUCCESS PAYMENT DUE\n";
print_r($result2);