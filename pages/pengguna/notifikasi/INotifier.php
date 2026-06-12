<?php
interface INotifier
{
    public function notify(NotificationMessage $message, User $user);
}
