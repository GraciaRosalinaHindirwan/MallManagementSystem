<?php

require_once __DIR__ . "/../usecase/ReceiveContractNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryContractQuery.php";
require_once __DIR__ . "/../infrastructure/writer/inmemory/InMemoryNotificationLogWriter.php";
require_once __DIR__ . "/../infrastructure/writer/inmemory/InMemoryNotificationWriter.php";
require_once __DIR__ . "/../infrastructure/queries/inmemory/InMemoryNotificationQuery.php";
require_once __DIR__ . "/../infrastructure/notifier/WebNotifier.php";

require_once __DIR__ . "/../domain/User.php";
require_once __DIR__ . "/../domain/Contract.php";

$user = new InMemoryUserQuery([
    User::create_filled_user(1, "johndoe", "", ""),
    User::create_filled_user(2, "johndoe", "", ""),
    User::create_filled_user(3, "johndoe", "", "")
]);
$contract = new InMemoryContractQuery([
    new Contract(
        id: 1,
        contract_number: "C-001",
        tenant_id: 1,
        unit_id: 1,
        start_date: new DateTime("2023-01-01"),
        end_date: new DateTime("2024-01-01"),
        contract_status: ContractStatus::Active,
        legal_document_url: "https://example.com/contract/C-001.pdf"
    ),
    new Contract(
        id: 2,
        contract_number: "C-002",
        tenant_id: 2,
        unit_id: 2,
        start_date: new DateTime("2023-02-01"),
        end_date: new DateTime("2024-02-01"),
        contract_status: ContractStatus::Active,
        legal_document_url: "https://example.com/contract/C-002.pdf"
    ),
    new Contract(
        id: 3,
        contract_number: "C-003",
        tenant_id: 3,
        unit_id: 3,
        start_date: new DateTime("2023-03-01"),
        end_date: new DateTime("2024-03-01"),
        contract_status: ContractStatus::Active,
        legal_document_url: "https://example.com/contract/C-003.pdf"
    )
]);

$notification = [];

$notification_writer = new InMemoryNotificationWriter($notification);
$notification_query = new InMemoryNotificationQuery($notification);
$notifier = new WebNotifier($notification_writer, $notification_query);

$log_writer = new InMemoryNotificationLogWriter([]);

$usecase = new ReceiveContractNotificationUseCase($notifier, $user, $contract, $log_writer);
$usecase->execute();

var_dump($notification);
