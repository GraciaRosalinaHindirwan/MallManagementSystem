<?php

require_once __DIR__ . '/../infrastructure/queries/mysql/MysqlNotificationQuery.php';

// koneksi DB kamu
require_once __DIR__ . '/../../../../config/konek.php';

$query = new MysqlNotificationQuery($conn);

$data = $query->get_all();

echo "<pre>";
print_r($data);
echo "</pre>";