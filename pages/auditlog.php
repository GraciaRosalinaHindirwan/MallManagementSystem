<?php

$file = '../config/audit_log.json';

$logs = [];

if(file_exists($file))
{
    $logs = json_decode(
        file_get_contents($file),
        true
    );
}

$batasUI = strtotime('-7 days');

$logs = array_filter(
    $logs,
    function($log) use ($batasUI){
        return strtotime($log['tanggal']) >= $batasUI;
    }
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Audit Log</title>
</head>
<body>

<h2>Audit Log (7 Hari Terakhir)</h2>

<table border="1" cellpadding="10">

<tr>
    <th>Username</th>
    <th>Aktivitas</th>
    <th>Tanggal</th>
</tr>

<?php foreach($logs as $log): ?>

<tr>
    <td><?= htmlspecialchars($log['username']) ?></td>
    <td><?= htmlspecialchars($log['aktivitas']) ?></td>
    <td><?= htmlspecialchars($log['tanggal']) ?></td>
</tr>

<?php endforeach; ?>

</table>

</body>
</html>