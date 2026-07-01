<?php

require_once __DIR__ . '/../../../../config/konek.php';
require_once __DIR__ . '/../usecase/NotificationService.php';
require_once __DIR__ . '/../infrastructure/writer/mysql/MysqlNotificationLogWriter.php';

// =========================
// 1. SIMULASI PAYMENT EVENT
// =========================
$payment_event = [
    'event_type' => 'payment_due',
    'user_id' => 1,
    'invoice_id' => 'INV-' . rand(1000, 9999),
    'amount'     => 150000,
    'due_date'   => date('Y-m-d', strtotime('+3 days'))
];

// =========================
// 2. SIMULASI RECIPIENT
// =========================
$recipient = [
    'email' => 'test@test.com',
    'name'  => 'Tester User'
];

// =========================
// 3. WRITER INIT
// =========================
$writer = new MysqlNotificationLogWriter($conn);

// =========================
// 4. SERVICE INIT
// =========================
$service = new NotificationService($writer);

// =========================
// 5. HANDLE EVENT
// =========================
$log = $service->handlePaymentDue([
    'invoice_id' => $payment_event['invoice_id'],
    'amount'     => $payment_event['amount'],
    'due_date'   => $payment_event['due_date'],
    'email'      => $recipient['email'],
    'name'       => $recipient['name'],
    'user_id'    => 1
]);

// =========================
// 6. OUTPUT DEBUG (UNTUK DEMO)
// =========================
echo "<h3>Payment Event Processed Successfully</h3>";

echo "<pre>";
print_r($payment_event);
print_r($recipient);
echo "</pre>";

echo "<hr>";

echo "<h4>Notification Created:</h4>";
echo "<pre>";
print_r($log);
echo "</pre>";