<?php

require_once __DIR__ . "/../infrastructure/notifier/INotifier.php";
require_once __DIR__ . "/../infrastructure/queries/IUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/IContractQuery.php";

require_once __DIR__ . "/../domain/Notification/NotificationLog.php";
require_once __DIR__ . "/../infrastructure/writer/INotificationLogWriter.php";

class ReceiveContractNotificationUseCase
{
    private INotifier $_notifier;
    private IContractQuery $_contracts;

    public function __construct(INotifier $_notifier, IContractQuery $_contracts)
    {
        $this->_notifier = $_notifier;
        $this->_contracts = $_contracts;
    }

    public function execute(User $user)
    {
        $contracts = $this->_contracts->get_all();

        /** @var Contract[] $contracts */
        foreach ($contracts as $contract) {
            $current_time = new DateTime();

            if (!$contract->will_expire_in_days(7)) {
                continue;
            }

            $notification_message = new NotificationContent(
                type: NotificationType::contract_expiry,
                subject: "mmisreminder",
                body: "your rental contract is due in " . $current_time->diff($contract->end_date)->format("%R%a days, %H hours, %I minutes"),
            );


            $this->_notifier->notify($notification_message, $user);
        }
    }
}
