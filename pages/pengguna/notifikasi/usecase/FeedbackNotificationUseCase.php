<?php

require_once __DIR__ . "/../domain/Feedback.php";
require_once __DIR__ . "/../infrastructure/queries/IFeedbackQuery.php";

class FeedbackNotificationUseCase
{
    private INotifier $notifier;
    private IFeedbackQuery $_feedback_query;

    public function __construct(INotifier $notifier, InMemoryFeedbackQuery $feedback_query)
    {
        $this->notifier = $notifier;
        $this->_feedback_query = $feedback_query;
    }

    public function execute(User $user)
    {
        if (count($this->_feedback_query->get_all()) <= 0) return;

        //** @var Feedback $feedback */
        foreach ($this->_feedback_query->get_all() as $feedback) {
            if ($feedback->rating > 3) continue;

            $this->notifier->notify(new NotificationContent(
                "feedback rendah",
                $feedback->komentar,
                NotificationType::complaint
            ), $user);
        }
    }
}
