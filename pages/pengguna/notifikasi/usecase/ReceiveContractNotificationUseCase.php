<?php

require_once __DIR__ . "/../infrastructure/notifier/INotifier.php";
require_once __DIR__ . "/../infrastructure/queries/IUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/IContractQuery.php";

require_once __DIR__ . "/../domain/Notification/NotificationLog.php";
require_once __DIR__ . "/../infrastructure/writer/INotificationLogWriter.php";

class ReceiveContractNotificationUseCase
{
    private IUserQuery $_user;
    private INotifier $_notifier;
    private IContractQuery $_contracts;
    private INotificationLogWriter $_logger;

    public function __construct(INotifier $_notifier, IUserQuery $_user, IContractQuery $_contracts, INotificationLogWriter $_logger)
    {
        $this->_notifier = $_notifier;
        $this->_user = $_user;
        $this->_contracts = $_contracts;
        $this->_logger = $_logger;
    }

    public function execute()
    {
        $contracts = $this->_contracts->get_all();
        $users = $this->_user->get_all();

        foreach ($users as $user) {
            foreach ($contracts as $contract) {
                $current_time = new DateTime();
                $notification_message = new NotificationContent(
                    type: NotificationType::contract_expiry,
                    subject: "mmisreminder",
                    body: "your rental contract is due in " . $current_time->diff($contract->due_date)->format("%R%a days, %H hours, %I minutes"),
                );


                $this->_notifier->notify($notification_message, $user);


                $this->_logger->insert(NotificationLog::pending(
                    new Recipient($user->email, $user->username),
                    $notification_message
                ));
            }
        }
    }
}
