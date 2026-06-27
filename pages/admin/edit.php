<?php
include '../../config/konek.php';

$successMessage = "";
$errorMessage = "";

// ===== Ambil & validasi ID user =====
$userId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($userId <= 0) {
    header("Location: listUser.php");
    exit;
}

// ===== Fetch data user existing (untuk prefill form) =====
$stmtUser = mysqli_prepare(
    $conn,
    "SELECT u.full_name, u.username, u.email, rp.role
     FROM 09_users u
     LEFT JOIN 09_role_pages rp ON u.role_page_id = rp.id
     WHERE u.id = ?"
);
mysqli_stmt_bind_param($stmtUser, "i", $userId);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$userData = mysqli_fetch_assoc($resUser);

if (!$userData) {
    header("Location: listUser.php");
    exit;
}

// Data yang ditampilkan di form (default dari DB, bisa berubah kalau ada error submit)
$currentUser = $userData;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama          = trim($_POST['nama_lengkap'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $role          = trim($_POST['role'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');

    $errors = [];

    if (empty($nama)) {
        $errors[] = "Nama lengkap wajib diisi.";
    }

    if (empty($username)) {
        $errors[] = "Username wajib diisi.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid.";
    }

    if (empty($role)) {
        $errors[] = "Role wajib dipilih.";
    }

    if (empty($adminPassword)) {
        $errors[] = "Password admin wajib diisi untuk konfirmasi perubahan.";
    }

    // ===== Cek username duplikat (exclude user yang sedang diedit) =====
    $cekUsername = mysqli_prepare(
        $conn,
        "SELECT id FROM 09_users WHERE username = ? AND id != ?"
    );
    mysqli_stmt_bind_param($cekUsername, "si", $username, $userId);
    mysqli_stmt_execute($cekUsername);
    $resultUsername = mysqli_stmt_get_result($cekUsername);

    if (mysqli_num_rows($resultUsername) > 0) {
        $errors[] = "Username sudah digunakan.";
    }

    // ===== Cek email duplikat (exclude user yang sedang diedit) =====
    $cekEmail = mysqli_prepare(
        $conn,
        "SELECT id FROM 09_users WHERE email = ? AND id != ?"
    );
    mysqli_stmt_bind_param($cekEmail, "si", $email, $userId);
    mysqli_stmt_execute($cekEmail);
    $resultEmail = mysqli_stmt_get_result($cekEmail);

    if (mysqli_num_rows($resultEmail) > 0) {
        $errors[] = "Email sudah digunakan.";
    }

    // ===== Konfirmasi password admin =====
    if (!empty($adminPassword) && $adminPassword !== 'admin123') {
        $errors[] = "Password admin tidak sesuai.";
    }

    if (empty($errors)) {

        $roleQuery = mysqli_prepare(
            $conn,
            "SELECT id FROM 09_role_pages WHERE role = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($roleQuery, "s", $role);
        mysqli_stmt_execute($roleQuery);
        $roleResult = mysqli_stmt_get_result($roleQuery);
        $roleData = mysqli_fetch_assoc($roleResult);

        if (!$roleData) {

            $errorMessage = "Role tidak ditemukan.";

        } else {

            $rolePageId = $roleData['id'];

            $update = mysqli_prepare(
                $conn,
                "UPDATE 09_users
                 SET full_name = ?, username = ?, email = ?, role_page_id = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "sssii",
                $nama,
                $username,
                $email,
                $rolePageId,
                $userId
            );

            if (mysqli_stmt_execute($update)) {

                $successMessage = "
                Data pengguna berhasil diperbarui.<br>
                Username : <b>$username</b><br>
                Role : <b>$role</b>
                ";

                // refresh nilai yang ditampilkan sesuai perubahan terbaru
                $currentUser = [
                    'full_name' => $nama,
                    'username'  => $username,
                    'email'     => $email,
                    'role'      => $role
                ];

            } else {
                $errorMessage = "Gagal memperbarui data pengguna.";
            }
        }

    } else {

        $errorMessage = implode("<br>", $errors);

        // tetap tampilkan data yang baru diketik user walau gagal validasi
        $currentUser = [
            'full_name' => $nama,
            'username'  => $username,
            'email'     => $email,
            'role'      => $role
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit User</title>
  <link rel="stylesheet" href="../../public/asset/css/designSystem.css">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: var(--font-family);
      background-color: var(--background);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: stretch;
    }

    .bg-panel {
      flex: 1;
      background: url('../../public/asset/images/background.png') center center / cover no-repeat;
      position: relative;
      display: flex;
      align-items: flex-end;
      padding: 40px;
    }
    .bg-panel::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(2,31,66,.65) 0%, rgba(11,55,109,.45) 100%);
    }
    .bg-panel__text {
      position: relative;
      z-index: 1;
    }
    .bg-panel__text h2 {
      font-size: var(--h2);
      font-weight: 700;
      color: var(--text);
      margin-bottom: 6px;
    }
    .bg-panel__text p {
      font-size: var(--label);
      color: rgba(245,247,250,.7);
    }

    .form-panel {
      width: 520px;
      flex-shrink: 0;
      background: rgba(11, 55, 109, 0.92);
      backdrop-filter: blur(12px);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 52px 48px;
      border-left: 1px solid rgba(0, 212, 216, 0.18);
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: var(--label);
      color: var(--accent);
      text-decoration: none;
      margin-bottom: 28px;
      opacity: .85;
      transition: opacity .2s;
    }
    .back-link:hover { opacity: 1; }
    .back-link svg { width: 16px; height: 16px; }

    .form-panel__heading h1 {
      font-size: var(--h1);
      font-weight: 700;
      color: var(--text);
      margin-bottom: 6px;
    }
    .form-panel__heading p {
      font-size: var(--label);
      color: rgba(245,247,250,.65);
      margin-bottom: 32px;
    }

    .form-grid {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .form-group label {
      font-size: var(--label);
      font-weight: 500;
      color: rgba(245,247,250,.8);
    }
    .form-group input,
    .form-group select {
      background: rgba(2, 31, 66, 0.6);
      border: 1.5px solid rgba(0, 212, 216, 0.35);
      border-radius: 8px;
      padding: 13px 16px;
      font-size: var(--body);
      font-family: var(--font-family);
      color: var(--text);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      width: 100%;
    }
    .form-group input::placeholder { color: rgba(245,247,250,.35); }
    .form-group input:focus,
    .form-group select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(0, 212, 216, 0.15);
    }

    .form-group select {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2300D4D8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 40px;
      cursor: pointer;
    }
    .form-group select option {
      background: #0B376D;
      color: var(--text);
    }

    /* ===== Card konfirmasi password admin ===== */
    .confirm-box {
      background: rgba(239, 68, 68, 0.08);
      border: 1px solid rgba(239, 68, 68, 0.3);
      border-radius: 8px;
      padding: 16px;
      margin-top: 4px;
    }
    .confirm-box label {
      font-size: var(--label);
      font-weight: 600;
      color: #F87171;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;
    }
    .confirm-box small {
      display: block;
      margin-top: 6px;
      font-size: var(--caption);
      color: rgba(245,247,250,.55);
    }

    .btn-submit {
      margin-top: 8px;
      background: var(--secondary);
      color: var(--text);
      border: none;
      border-radius: 8px;
      padding: 15px;
      font-size: var(--body);
      font-family: var(--font-family);
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: background .2s, transform .1s;
    }
    .btn-submit:hover { background: var(--secondary-dark); }
    .btn-submit:active { transform: scale(.99); }

    .alert {
      border-radius: 8px;
      padding: 12px 16px;
      font-size: var(--label);
      margin-bottom: 16px;
    }
    .alert--success {
      background: rgba(34,197,94,.12);
      border: 1px solid rgba(34,197,94,.35);
      color: #22C55E;
    }
    .alert--error {
      background: rgba(239,68,68,.12);
      border: 1px solid rgba(239,68,68,.35);
      color: #EF4444;
    }

    @media (max-width: 900px) {
      .bg-panel { display: none; }
      .form-panel { width: 100%; padding: 40px 28px; }
    }
  </style>
</head>
<body>

<div class="bg-panel">
  <div class="bg-panel__text">
    <h2>Manajemen Pengguna</h2>
    <p>Kelola akses dan hak pengguna sistem secara terpusat.</p>
  </div>
</div>

<div class="form-panel">

  <a href="listUser.php" class="back-link">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="2">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
    Kembali ke Daftar Pengguna
  </a>

  <div class="form-panel__heading">
    <h1>Edit Pengguna</h1>
    <p>Perbarui data akun pengguna di bawah ini.</p>
  </div>

<?php if (!empty($successMessage)): ?>
<div class="alert alert--success">
    <?= $successMessage ?>
</div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<div class="alert alert--error">
    <?= $errorMessage ?>
</div>
<?php endif; ?>

  <form method="POST" class="form-grid" novalidate>

    <input type="hidden" name="id" value="<?= htmlspecialchars($userId) ?>">

    <div class="form-group">
      <label for="nama_lengkap">Nama Lengkap</label>
      <input type="text" id="nama_lengkap" name="nama_lengkap"
             placeholder="Contoh: Budi Santoso"
             value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>"
             required />
    </div>

    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username"
             placeholder="Contoh: budi.santoso"
             value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>"
             autocomplete="off" required />
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"
             placeholder="Contoh: budi@perusahaan.co.id"
             value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>"
             required />
    </div>

    <!-- Role -->
    <div class="form-group">
    <label for="role">Role</label>
<?php
$roles = [
    'Super Admin',
    'Admin',
    'Manager',
    'Leasing Manager',
    'Finance Manager',
    'Finance Staff',
    'Purchasing Manager',
    'Purchasing Staff',
    'HR',
    'Facility Manager',
    'Facility Staff',
    'Teknisi',
    'Customer Service',
    'Pengunjung',
    'Petugas Parkir',
    'Event Manager',
    'Event Organizer',
    'Tenant Owner',
    'Tenant Staff'
];
$selectedRole = $currentUser['role'] ?? '';
?>

<select id="role" name="role" required>

    <option value="" disabled
        <?= empty($selectedRole) ? 'selected' : '' ?>>
        Pilih role pengguna
    </option>

    <?php foreach ($roles as $r): ?>

        <option
            value="<?= htmlspecialchars($r) ?>"
            <?= ($selectedRole === $r) ? 'selected' : '' ?>
        >
            <?= htmlspecialchars($r) ?>
        </option>

    <?php endforeach; ?>

</select>
</div>

    <!-- Konfirmasi password admin (pengganti info box default password) -->
    <div class="confirm-box">
      <label for="admin_password">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Konfirmasi Password Admin
      </label>
      <input type="password" id="admin_password" name="admin_password"
             placeholder="Masukkan password admin"
             autocomplete="off" required />
      <small>Masukkan password admin untuk mengonfirmasi perubahan data pengguna ini.</small>
    </div>

    <button type="submit" class="btn-submit">Simpan Perubahan</button>

  </form>
</div>

</body>
</html>