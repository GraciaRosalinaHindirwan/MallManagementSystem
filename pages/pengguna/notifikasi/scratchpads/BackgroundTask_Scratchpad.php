<?php

require_once __DIR__ . "/../usecase/ReceiveContractNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/notifier/ConsoleNotifier.php";
require_once __DIR__ . "/../infrastructure/queries/InMemoryUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/InMemoryContractQuery.php";
require_once __DIR__ . "/../infrastructure/writer/InMemoryNotificationLogWriter.php";

require_once __DIR__ . "/../domain/User.php";

$user = new InMemoryUserQuery([
    new User(1, "user1", "user1@gmail.com"),
    new User(2, "user2", "user2@gmail.com"),
    new User(3, "user3", "user3@gmail.com")
]);
$notifier = new ConsoleNotifier();
$contract = new InMemoryContractQuery([
    new Contract(1, 1, new DateTime()),
    new Contract(2, 2, new DateTime()),
    new Contract(3, 3, new DateTime())
]);

$log_writer = new InMemoryNotificationLogWriter([]);

$usecase = new ReceiveContractNotificationUseCase($notifier, $user, $contract, $log_writer);
$usecase->execute();
