<?php

require_once __DIR__ . "/../domain/User.php";

require_once __DIR__ . "/../infrastructure/queries/IApprovalRequestQuery.php";

require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlApprovalRequestQuery.php";
require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlNotificationQuery.php";
require_once __DIR__ . "/../infrastructure/writer/mysql/MysqlNotificationWriter.php";
require_once __DIR__ . "/../infrastructure/writer/mysql/MysqlNotificationLogWriter.php";
require_once __DIR__ . "/../infrastructure/notifier/WebNotifier.php";

class PendingApprovalRequestNotificationUsecase
{
    private IApprovalRequestQuery $request_query;
    private INotifier $notifier;

    public function __construct(IApprovalRequestQuery $request_query, INotifier $notifier)
    {
        $this->request_query = $request_query;
        $this->notifier = $notifier;
    }

    public static function create_mysql(mysqli $db)
    {
        return new PendingApprovalRequestNotificationUsecase(
            new MysqlApprovalRequestQuery($db),

            new WebNotifier(
                new MysqlNotificationWriter($db),
                new MysqlNotificationQuery($db),
                new MysqlNotificationLogWriter($db),
            ),
        );
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
