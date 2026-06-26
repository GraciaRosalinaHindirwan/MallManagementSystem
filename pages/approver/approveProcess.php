<?php
session_start();
require_once '../../config/konek.php';
require_once __DIR__ . '/../../public/auth/checkSession.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$manager_roles = [
    'Leasing Manager',
    'Finance Manager',
    'Manager',
    'Purchasing Manager',
    'Facility Manager',
    'Event Manager'
];

if (!in_array($role, $manager_roles)) {
    header("Location: ../approver/myApproval.php");
    exit();
}

if (!isset($conn) || !$conn) {
    die("Koneksi database gagal!");
}


if (!isset($_GET['id'])) {
    header("Location: approvalList.php");
    exit;
}

$id = (int)$_GET['id'];

$approver = "Manager";

$sql = "
UPDATE `08_approval_requests`
SET
    status = 'approved',
    approved_by = '$approver',
    approved_at = NOW()
WHERE approval_id = $id
";

if ($conn->query($sql)) {

    echo "
    <script>
        alert('Approval berhasil disetujui');
        window.location='approvalList.php';
    </script>
    ";
} else {

    echo "
    <script>
        alert('Gagal approve data');
        window.location='approvalList.php';
    </script>
    ";
}
