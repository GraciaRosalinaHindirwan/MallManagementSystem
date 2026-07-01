<?php

require_once __DIR__ . "/../IFeedbackQuery.php";

class InMemoryFeedbackQuery implements IFeedbackQuery
{
    //** @var Feedback[] $feedbacks */
    private array $feedbacks = [];

    //** @param Feedback[] $feedbacks */
    public function __construct(array $feedbacks)
    {
        $this->feedbacks = $feedbacks;
    }

    //** @return Feedback[] */
    public function get_all(): array
    {
        return $this->feedbacks;
    }
}
