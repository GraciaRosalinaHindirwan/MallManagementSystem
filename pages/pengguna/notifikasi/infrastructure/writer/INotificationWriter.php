<?php
interface INotificationWriter
{
    public function insert(Notification $notification);
}
