<?php

require_once __DIR__ . "/../usecase/ReceiveContractNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/notifier/WebNotifier.php";

require_once __DIR__ . "/../domain/User.php";
require_once __DIR__ . "/../domain/Contract.php";

require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryContractQuery.php";
require_once __DIR__ . "/../infrastructure/writer/inmemory/InMemoryNotificationLogWriter.php";
require_once __DIR__ . "/../infrastructure/writer/inmemory/InMemoryNotificationWriter.php";

$user = new InMemoryUserQuery([
    new User(1, "user1", "user1@gmail.com", UserType::Pegawai),
    new User(2, "user2", "user2@gmail.com", UserType::Pegawai),
    new User(3, "user3", "user3@gmail.com", UserType::Pegawai)
]);
$contract = new InMemoryContractQuery([
    new Contract(1, 1, new DateTime()),
    new Contract(2, 2, new DateTime()),
    new Contract(3, 3, new DateTime())
]);

$notification = new InMemoryNotificationWriter([]);
$notifier = new WebNotifier($notification);

$log_writer = new InMemoryNotificationLogWriter([]);

$usecase = new ReceiveContractNotificationUseCase($notifier, $user, $contract, $log_writer);
$usecase->execute();

var_dump($notification->notifications);
