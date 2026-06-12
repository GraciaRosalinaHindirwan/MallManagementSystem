<?php


require_once __DIR__ . "/../persistence/queries/InMemoryNotifictionQuery.php";
require_once __DIR__ . "/../domain/Notification.php";

$notification = new InMemoryNotifictionQuery([
    new Notification(1, "messagae 1", new DateTime()),
    new Notification(2, "messagae 2", new DateTime()),
    new Notification(3, "messagae 3", new DateTime())
]);

$specific = $notification->get_by_id(3);

echo "get by id: \n";
var_dump($specific);
