<?php

require_once __DIR__ . "/../domain/User.php";

require_once __DIR__ . "/../infrastructure/queries/IApprovalRequestQuery.php";

class PendingApprovalRequestNotificationUsecase
{
    private IApprovalRequestQuery $request_query;
    private INotifier $notifier;

    public function __construct(InMemoryApprovalRequestQuery $request_query, INotifier $notifier)
    {
        $this->request_query = $request_query;
        $this->notifier = $notifier;
    }

    public function execute(User $user)
    {
        $pending_requests = $this->request_query->get_pending();

        /** @var ApprovalRequest $request */
        foreach ($pending_requests as $request) {
            $this->notifier->notify(new NotificationContent(
                $request->title,
                $request->description . "\n\ncurrently pending",
                NotificationType::approval_request
            ), $user);
        }
    }
}
