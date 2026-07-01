<?php

require_once __DIR__ . '/../infrastructure/queries/mysql/MysqlNotificationQuery.php';
require_once __DIR__ . '/../../../../config/konek.php';

$query = new MysqlNotificationQuery($conn);

$notification = $query->get_by_id(999999);

echo "<pre>";
var_dump($notification);
echo "</pre>";