<?php

require_once __DIR__ . "/../ReceiveContractNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/notifier/ConsoleNotifier.php";
require_once __DIR__ . "/../infrastructure/queries/InMemoryUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/InMemoryContractQuery.php";

require_once __DIR__ . "/../domain/User.php";

$user = new InMemoryUserQuery([
    new User(1, "user1", "user1@gmail.com")
]);
$notifier = new ConsoleNotifier();
$contract = new InMemoryContractQuery([
    new Contract(1, 1)
]);

$usecase = new ReceiveContractNotificationUseCase($notifier, $user, $contract);

$usecase->execute(1);
