<?php
interface INotificationWriter
{
    public function insert(NotificationContent $notification);
}
