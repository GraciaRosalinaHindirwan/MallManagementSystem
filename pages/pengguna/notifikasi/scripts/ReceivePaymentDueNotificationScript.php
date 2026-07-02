<?php
require_once __DIR__ . "/../usecase/NotificationService.php";
require_once __DIR__ . "/../infrastructure/writer/mysql/MysqlNotificationLogWriterInApp.php";
require_once __DIR__ . "/../domain/DTO/PaymentEventData.php";
require_once __DIR__ . "/../../../../config/konek.php";

$writer = new MysqlNotificationLogWriterInApp($conn);
$service = new NotificationService($writer);

$stmt = $conn->prepare("
    SELECT i.invoice_number, i.total_amount, i.due_date,
           COALESCE(i.created_by, 1) AS user_id,
           u.email, u.full_name AS name
    FROM 06_invoices i
    LEFT JOIN 09_users u ON u.id = COALESCE(i.created_by, 1)
    WHERE i.status = 'Belum Bayar'
      AND i.due_date <= CURDATE() + INTERVAL 7 DAY
");
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($invoices == null || count($invoices) <= 0) echo "payment due date isn't soon";

foreach ($invoices as $invoice) {
    $service->handlePaymentDue(new PaymentEventData(
        type: 'payment_due',
        user_id: (int)$invoice['user_id'],
        email: $invoice['email'],
        name: $invoice['name'],
        invoice: $invoice['invoice_number'],
        amount: (int)$invoice['total_amount'],
        due_date: $invoice['due_date']
    ));
}
