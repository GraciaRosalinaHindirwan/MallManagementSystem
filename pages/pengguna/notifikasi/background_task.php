<?php
echo "notification task running...";

while (true) {
    if (new DateTime() != DateTime::createFromFormat("H:i", "08:00")) {
        sleep(60);
        continue;
    }

    require_once __DIR__ . "/scripts/ReceiveContractNotificationScript.php";
    require_once __DIR__ . "/scripts/ReceiveFeedbackNotificationScript.php";
    require_once __DIR__ . "/scripts/ReceivePaymentDueNotificationScript.php";
    require_once __DIR__ . "/scripts/ReceivePendingApprovalRequestScript.php";
    sleep(60);
}
