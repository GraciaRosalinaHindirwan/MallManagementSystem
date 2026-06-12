<?php

class Notifiction
{
    public int $id;
    public string $message;
    public DateTime $date_time;

    public function __construct(int $id, string $message, DateTime $date_time)
    {
        $this->id = $id;
        $this->message = $message;
        $this->date_time = $date_time;
    }
}
