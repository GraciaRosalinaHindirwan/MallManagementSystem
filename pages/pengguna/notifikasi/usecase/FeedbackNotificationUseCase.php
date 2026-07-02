<?php

require_once __DIR__ . "/../domain/Feedback.php";

require_once __DIR__ . "/../infrastructure/queries/IFeedbackQuery.php";
require_once __DIR__ . "/../infrastructure/notifier/INotifier.php";

require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlFeedbackQuery.php";
require_once __DIR__ . "/../infrastructure/notifier/WebNotifier.php";
require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlNotificationQuery.php";
require_once __DIR__ . "/../infrastructure/writer/mysql/MysqlNotificationWriter.php";
require_once __DIR__ . "/../infrastructure/writer/mysql/MysqlNotificationLogWriter.php";

class FeedbackNotificationUseCase
{
    private INotifier $notifier;
    private IFeedbackQuery $_feedback_query;

    public function __construct(INotifier $notifier, IFeedbackQuery $feedback_query)
    {
        $this->notifier = $notifier;
        $this->_feedback_query = $feedback_query;
    }

    public static function create_mysql(mysqli $db)
    {
        return new FeedbackNotificationUseCase(
            new WebNotifier(
                new MysqlNotificationWriter($db),
                new MysqlNotificationQuery($db),
                new MysqlNotificationLogWriter($db)
            ),
            new MysqlFeedbackQuery($db)
        );
    }

    public function execute(User $user)
    {
        if (count($this->_feedback_query->get_all()) <= 0) return;

        //** @var Feedback $feedback */
        foreach ($this->_feedback_query->get_all() as $feedback) {
            if ($feedback->rating > 3) continue;

            $this->notifier->notify(new NotificationContent(
                subject: "feedback rendah",
                body: $feedback->komentar,
                type: NotificationType::complaint
            ), $user);
        }
    }
}
