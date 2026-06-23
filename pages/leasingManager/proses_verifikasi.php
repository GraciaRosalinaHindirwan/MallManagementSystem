<?php
// session_start();
// require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
    exit;
}

if ($action === 'approve') {
    $sql    = "UPDATE `02_tenant_prospects` SET status = 'Verified' WHERE id_prospect = $id AND status = 'Prospect'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_affected_rows($conn) > 0) {
        echo json_encode(['success' => true, 'message' => 'Tenant berhasil diverifikasi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memverifikasi tenant: ' . mysqli_error($conn)]);
    }

} elseif ($action === 'reject') {
    $sql    = "UPDATE `02_tenant_prospects` SET status = 'Rejected' WHERE id_prospect = $id AND status = 'Prospect'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_affected_rows($conn) > 0) {
        echo json_encode(['success' => true, 'message' => 'Tenant berhasil ditolak dan dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus tenant: ' . mysqli_error($conn)]);
    }
}
?>