<?php
require_once __DIR__ . '/../../config/konek_05.php';

$id         = $_POST['id'] ?? '';
$status_baru = $_POST['status_baru'] ?? '';
$catatan    = trim($_POST['catatan'] ?? '') ?: null;
$allowed    = ['open', 'in_progress', 'resolved'];

if (!$id || !in_array($status_baru, $allowed)) {
    header('Location: tiket.php');
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM `05_tiket` WHERE id = ?");
$stmt->execute([$id]);
$tiket = $stmt->fetch();

if (!$tiket) {
    header('Location: tiket.php');
    exit;
}

$pdo->prepare("UPDATE `05_tiket` SET status = ? WHERE id = ?")
    ->execute([$status_baru, $id]);

$pdo->prepare("
    INSERT INTO `05_tiket_log` (tiket_id, status_lama, status_baru, catatan)
    VALUES (?, ?, ?, ?)
")->execute([$id, $tiket['status'], $status_baru, $catatan]);

header('Location: tiket-detail.php?id=' . urlencode($id));
exit;