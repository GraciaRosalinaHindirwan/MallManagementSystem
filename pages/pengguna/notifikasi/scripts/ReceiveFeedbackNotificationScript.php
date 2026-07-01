<?php

require_once __DIR__ . "/../../../../config/konek.php";
require_once __DIR__ . "/../usecase/FeedbackNotificationUseCase.php";
require_once __DIR__ . "/../infrastructure/queries/mysql/MysqlUserQuery.php";


$usecase = FeedbackNotificationUseCase::create_mysql($conn);
$user_query = new MysqlsUserQuery($conn);

$users = $user_query->get_all();

/** @var User[] $users */
foreach ($users as $user) {
    $usecase->execute($user);
}
