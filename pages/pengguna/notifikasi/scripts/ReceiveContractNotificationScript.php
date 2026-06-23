<?php
require_once __DIR__ . "/../usecase/ReceiveContractNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlUserQuery.php";
require_once __DIR__ . "/../tests/integration/db_populate/Prepopulate.php";
require_once __DIR__ . "/../../../../config/konek.php";


$usecase = ReceiveContractNotificationUseCase::create_mysql($conn);
$user_query = new MysqlsUserQuery($conn);

$users = $user_query->get_all();

/** @var User[] $users */
foreach ($users as $user) {
    $usecase->execute($user);
}

echo "new contract notification!";
