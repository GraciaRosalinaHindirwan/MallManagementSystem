<?php

require_once __DIR__ . '/../usecase/NotificationService.php';
require_once __DIR__ . '/../infrastructure/writer/mysql/MysqlNotificationLogWriterInApp.php';

$db = new mysqli("localhost", "root", "", "mall_management");
// writer (sesuai punyamu)
$writer = new MysqlNotificationLogWriterInApp($db);

$service = new NotificationService($writer);

try {
    echo "TEST PAYMENT SUCCESS\n";

    $result1 = $service->handlePaymentEvent([
        'type' => 'payment_success',
        'user_id' => 1,
        'email' => 'test@mail.com',
        'name' => 'Budi',
        'invoice' => 'INV-001'
    ]);

    print_r($result1);

    echo "\n\nTEST PAYMENT DUE\n";

    $result2 = $service->handlePaymentEvent([
        'type' => 'payment_due',
        'user_id' => 1,
        'email' => 'test@mail.com',
        'name' => 'Budi',
        'invoice_id' => 'INV-DUE-001',
        'amount' => 150000,
        'due_date' => '2026-06-30'
    ]);

    print_r($result2);

    echo "\n\n✔ ALL TEST PASSED\n";

} catch (Throwable $e) {
    echo "❌ ERROR:\n";
    echo $e->getMessage();
}