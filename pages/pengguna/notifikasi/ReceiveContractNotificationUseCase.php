<?php

require_once __DIR__ . "/infrastructure/notifier/INotifier.php";
require_once __DIR__ . "/infrastructure/queries/IUserQuery.php";
require_once __DIR__ . "/infrastructure/queries/IContractQuery.php";

require_once __DIR__ . "/domain/NotificationMessage.php";

class ReceiveContractNotificationUseCase
{
    private IUserQuery $_user;
    private INotifier $_notifier;
    private IContractQuery $_contracts;

    public function __construct(INotifier $_notifier, IUserQuery $_user, IContractQuery $_contracts)
    {
        $this->_notifier = $_notifier;
        $this->_user = $_user;
        $this->_contracts = $_contracts;
    }

    public function execute(int $user_id)
    {
        $contract = $this->_contracts->get_by_user_id($user_id);
        $current_user = $this->_user->get_by_id($user_id);
        $current_time = new DateTime();

        $this->_notifier->notify(new NotificationMessage(
            subject: "mmisreminder",
            body: "due in " . $current_time->diff($contract->due_date)->format("%R%a days, %H hours, %I minutes")
        ), $current_user);
    }
}
