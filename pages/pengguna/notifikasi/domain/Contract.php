<?php

class Contract
{
    public int $id;
    public int $user_id;
    public DateTime $due_date;

    public function __construct(int $id, int $user_id, DateTime $due_date)
    {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->due_date = $due_date;
    }
}
