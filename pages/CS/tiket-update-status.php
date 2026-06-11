<?php
require_once __DIR__ . '/../../config/koneksi.php';

$id         = $_POST['id'] ?? '';
$status_baru = $_POST['status_baru'] ?? '';
$catatan    = trim($_POST['catatan'] ?? '') ?: null;
$allowed    = ['open', 'in_progress', 'resolved'];

if (!$id || !in_array($status_baru, $allowed)) {
    header('Location: tiket.php');
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM tiket WHERE id = ?");
$stmt->execute([$id]);
$tiket = $stmt->fetch();

if (!$tiket) {
    header('Location: tiket.php');
    exit;
}

$updated_by = $_SESSION['user_id'] ?? null;

$pdo->prepare("UPDATE tiket SET status = ? WHERE id = ?")
    ->execute([$status_baru, $id]);

$pdo->prepare("
    INSERT INTO tiket_log (tiket_id, status_lama, status_baru, catatan, updated_by)
    VALUES (?, ?, ?, ?, ?)
")->execute([$id, $tiket['status'], $status_baru, $catatan, $updated_by]);

header('Location: tiket-detail.php?id=' . urlencode($id));
exit;