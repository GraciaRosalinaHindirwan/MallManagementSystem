<?php

require_once __DIR__ . "/../../domain/Feedback.php";

require_once __DIR__ . "/../../usecase/FeedbackNotificationUseCase.php";

require_once __DIR__ . "/../../infrastructure/queries/inmemory/InMemoryNotificationQuery.php";
require_once __DIR__ . "/../../infrastructure/queries/inmemory/InMemoryFeedbackQuery.php";


use PHPUnit\Framework\TestCase;

class FeedbackNotificationUsecaseTest extends TestCase
{
    /** @var Notification[] $notifications */
    private array $notifications;

    /** @var NotificationLog[] $notifications */
    private array $logs;

    private WebNotifier $notifier;

    private InMemoryUserQuery $user_query;

    function setUp(): void
    {
        $this->user_query = new InMemoryUserQuery([
            User::create_default(1, "user1", "user1@gmail.com"),
            User::create_default(2, "user2", "user2@gmail.com"),
            User::create_default(3, "user3", "user3@gmail.com"),
        ]);

        $this->notifications = [];
        $this->logs = [];

        $notification_writer = new InMemoryNotificationWriter($this->notifications);
        $notification_query = new InMemoryNotificationQuery($this->notifications);
        $logger = new InMemoryNotificationLogWriter($this->logs);

        $this->notifier = new WebNotifier(
            $notification_writer,
            $notification_query,
            $logger

        );
    }

    function testNotificationShouldNotBeEmpty_WhenAParticularFeedbackWithCertainConditionExists()
    {
        $feedback = new InMemoryFeedbackQuery([
            Feedback::create_with_rating(1, 2),
            Feedback::create_with_rating(2, 1),
            Feedback::create_with_rating(3, 1),
        ]);

        $usecase = new FeedbackNotificationUseCase($this->notifier, $feedback);

        $usecase->execute($this->user_query->get_by_id(1));

        $this->assertNotEmpty($this->notifications);
    }

    function testWhenThereAreNoFeedback_NotificationShouldBeEmpty()
    {
        $feedback = new InMemoryFeedbackQuery([]);

        $usecase = new FeedbackNotificationUseCase($this->notifier, $feedback);

        $usecase->execute($this->user_query->get_by_id(1));

        $this->assertEmpty($this->notifications);
    }

    function testMultipleFeedback_ShouldProduceMultipleNotification()
    {
        $feedback = new InMemoryFeedbackQuery([
            Feedback::create_with_rating(1, 2),
            Feedback::create_with_rating(1, 1),
            Feedback::create_with_rating(1, 1),
        ]);

        $usecase = new FeedbackNotificationUseCase($this->notifier, $feedback);

        $usecase->execute($this->user_query->get_by_id(1));

        $this->assertCount(3, $this->notifications);
    }

    function testShouldOnlySendNotification_ForFeedbackEqualOrLowerThan3()
    {
        $feedback = new InmemoryFeedbackQuery([
            Feedback::create_with_rating(1, 5),
            Feedback::create_with_rating(2, 3),
            Feedback::create_with_rating(3, 1),
        ]);
        $usecase = new FeedbackNotificationUseCase($this->notifier, $feedback);

        $usecase->execute($this->user_query->get_by_id(1));

        $this->assertCount(2, $this->notifications);
    }
}
