<?php
session_start();

$file = '../../config/audit_log.json';

$logs = file_exists($file)
    ? json_decode(file_get_contents($file), true)
    : [];

if (!is_array($logs)) $logs = [];

// urut terbaru
usort($logs, function ($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});

// filter user
$filterUser = $_GET['user'] ?? '';
if ($filterUser) {
    $logs = array_filter($logs, function ($log) use ($filterUser) {
        return strtolower($log['username']) === strtolower($filterUser);
    });
}
?>

<?php include '../../includes/header.php'; ?>

<div class="box">

    <h2>Audit Log</h2>

    <form method="get">
        <input type="text" name="user" placeholder="Filter user"
            value="<?= htmlspecialchars($filterUser) ?>">
        <button class="btn-green">Search</button>
    </form>

</div>

<div class="box">

    <table>
        <tr>
            <th>No</th>
            <th>User</th>
            <th>Activity</th>
            <th>Time</th>
        </tr>

        <?php if (empty($logs)) : ?>
            <tr>
                <td colspan="4" style="text-align:center;">No logs found</td>
            </tr>
        <?php else : ?>
            <?php $no = 1; foreach ($logs as $log) : ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($log['username']) ?></td>
                    <td><?= htmlspecialchars($log['aktivitas']) ?></td>
                    <td><?= htmlspecialchars($log['tanggal']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

</div>

<?php include '../../includes/footer.php'; ?>