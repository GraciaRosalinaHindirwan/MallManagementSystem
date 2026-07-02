<?php

interface INotificationQuery
{
    public function get_by_id(int $id);

    public function get_all();
}
