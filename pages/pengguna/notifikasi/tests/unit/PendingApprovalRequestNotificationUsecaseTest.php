<?php

require_once __DIR__ . "/../../usecase/PendingApprovalRequestNotificationUsecase.php";
require_once __DIR__ . "/../../domain/Approval/ApprovalRequest.php";
require_once __DIR__ . "/../../domain/Approval/ApprovalRequestStatus.php";

require_once __DIR__ . "/../../infrastructure/queries/inmemory/InMemoryApprovalRequestQuery.php";
require_once __DIR__ . "/../../infrastructure/queries/inmemory/InMemoryUserQuery.php";

use PHPUnit\Framework\TestCase;

class PendingApprovalRequestNotificationUsecaseTest extends TestCase
{
    private InMemoryUserQuery $user;

    /** @var Notification[] $notifications */
    private array $notifications;
    /** @var NotificationLog[] $logs */
    private array $logs;

    private WebNotifier $notifier;


    #[Override]
    public function setUp(): void
    {
        $this->user = new InMemoryUserQuery([
            User::create_default(1, "user1", "user@gmail.com")
        ]);

        $this->notifications = [];
        $this->logs = [];

        $writer = new InMemoryNotificationWriter($this->notifications);
        $query = new InMemoryNotificationQuery($this->notifications);
        $logger = new InMemoryNotificationLogWriter($this->logs);

        $this->notifier = new WebNotifier($writer, $query, $logger);
    }

    public function testWhenThereIsARequest_NotificationShouldNotBeEmpty()
    {
        $approval_requests = new InMemoryApprovalRequestQuery([
            ApprovalRequest::create_default(1, ApprovalRequestStatus::Pending)
        ]);

        $usecase = new PendingApprovalRequestNotificationUsecase($approval_requests, $this->notifier);
        $usecase->execute($this->user->get_by_id(1));

        $this->assertNotEmpty($this->notifications);
    }

    public function testWhenThereAreNoRequest_NotificationShouldBeEmpty()
    {
        $approval_requests = new InMemoryApprovalRequestQuery([]);

        $usecase = new PendingApprovalRequestNotificationUsecase($approval_requests, $this->notifier);
        $usecase->execute($this->user->get_by_id(1));

        $this->assertEmpty($this->notifications);
    }

    public function testWhenThereAreMultipleRequests_NotifyEverything()
    {
        $approval_requests = new InMemoryApprovalRequestQuery([
            ApprovalRequest::create_default(1, ApprovalRequestStatus::Pending),
            ApprovalRequest::create_default(2, ApprovalRequestStatus::Pending),
            ApprovalRequest::create_default(3, ApprovalRequestStatus::Pending),
        ]);

        $usecase = new PendingApprovalRequestNotificationUsecase($approval_requests, $this->notifier);
        $usecase->execute($this->user->get_by_id(1));

        $this->assertCount(3, $this->notifications);
    }

    public function testIfApprovalRequestIsNotPending_DontNotify()
    {
        $approval_requests = new InMemoryApprovalRequestQuery([
            ApprovalRequest::create_default(1, ApprovalRequestStatus::Pending),
            ApprovalRequest::create_default(2, ApprovalRequestStatus::Approved),
            ApprovalRequest::create_default(3, ApprovalRequestStatus::Rejected),
        ]);

        $usecase = new PendingApprovalRequestNotificationUsecase($approval_requests, $this->notifier);
        $usecase->execute($this->user->get_by_id(1));

        $this->assertCount(1, $this->notifications);
    }
}
