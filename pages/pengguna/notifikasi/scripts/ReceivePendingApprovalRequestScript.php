<?php

require_once __DIR__ . "/../usecase/PendingApprovalRequestNotificationUsecase.php";
require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlUserQuery.php";
require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlApprovalRequestQuery.php";

require_once "./config/konek.php";

$user_query = new MysqlsUserQuery($conn);
$usecase = PendingApprovalRequestNotificationUsecase::create_mysql($conn);

$users = $user_query->get_all();

/** @var User $user */
foreach ($users as $user) {
    $usecase->execute($user);
}
