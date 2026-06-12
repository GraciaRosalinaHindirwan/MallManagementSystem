<?php

class ReceiveContractNotificationUseCase
{
    private INotifier $_notifier;
    private IUserQuery $_user;
    private INotificationQuery $_notification;

    public function execute(int $user_id) {}
}
