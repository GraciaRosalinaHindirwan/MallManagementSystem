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

    public function execute(int $user_id)
    {
        $contract = $this->_contracts->get_by_user_id($user_id);
        $current_user = $this->_user->get_by_id($user_id);
        $current_time = new DateTime();


        $notification_content = new NotificationContent(
            subject: "mmisreminder",
            body: "your rental contract is due in " . $current_time->diff($contract->due_date)->format("%R%a days, %H hours, %I minutes"),
            type: NotificationType::contract_expiry
        );


        $this->_notifier->notify($notification_content, $current_user);

        $this->_logger->insert(NotificationLog::pending(
            new Recipient($current_user->email, $current_user->username),
            $notification_content,

        ));
    }
}
