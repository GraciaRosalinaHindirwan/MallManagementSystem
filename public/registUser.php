<?php
include '../config/konek.php';

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama       = trim($_POST['nama_lengkap'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = trim($_POST['role'] ?? '');

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

    $cekUsername = mysqli_prepare(
        $conn,
        "SELECT id FROM 09_users WHERE username = ?"
    );

    mysqli_stmt_bind_param(
        $cekUsername,
        "s",
        $username
    );

    mysqli_stmt_execute($cekUsername);

    $resultUsername =
        mysqli_stmt_get_result($cekUsername);

    if (mysqli_num_rows($resultUsername) > 0) {
        $errors[] = "Username sudah digunakan.";
    }

    $cekEmail = mysqli_prepare(
        $conn,
        "SELECT id FROM 09_users WHERE email = ?"
    );

    mysqli_stmt_bind_param(
        $cekEmail,
        "s",
        $email
    );

    mysqli_stmt_execute($cekEmail);

    $resultEmail =
        mysqli_stmt_get_result($cekEmail);

    if (mysqli_num_rows($resultEmail) > 0) {
        $errors[] = "Email sudah digunakan.";
    }

    if (empty($errors)) {
        $roleQuery = mysqli_prepare(
            $conn,
            "SELECT id
             FROM 09_role_pages
             WHERE role = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $roleQuery,
            "s",
            $role
        );

        mysqli_stmt_execute($roleQuery);

        $roleResult =
            mysqli_stmt_get_result($roleQuery);

        $roleData =
            mysqli_fetch_assoc($roleResult);

        if (!$roleData) {

            $errorMessage =
                "Role tidak ditemukan.";

        } else {

            $rolePageId =
                $roleData['id'];

            // password default
            $plainPassword =
                $username . "@123";

            $hashedPassword =
                password_hash(
                    $plainPassword,
                    PASSWORD_DEFAULT
                );

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO 09_users
                (
                    full_name,
                    username,
                    email,
                    password,
                    must_change_password,
                    role_page_id
                )
                VALUES
                (
                    ?, ?, ?, ?, 1, ?
                )"
            );

            mysqli_stmt_bind_param(
                $insert,
                "ssssi",
                $nama,
                $username,
                $email,
                $hashedPassword,
                $rolePageId
            );

            if (mysqli_stmt_execute($insert)) {

                $successMessage =
                "
                User berhasil ditambahkan.<br>
                Username : <b>$username</b><br>
                Password Default :
                <b>$plainPassword</b><br>
                Role :
                <b>$role</b>
                ";

                $_POST = [];

            } else {

                $errorMessage =
                "Gagal menambahkan user.";
            }
        }

    } else {

        $errorMessage =
            implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add users</title>
  <link rel="stylesheet" href="asset/css/designSystem.css">

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
      background: url('../public/asset/images/background.png') center center / cover no-repeat;
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

    .info-box {
      background: rgba(0, 212, 216, 0.08);
      border: 1px solid rgba(0, 212, 216, 0.25);
      border-radius: 8px;
      padding: 12px 16px;
      font-size: var(--caption);
      color: rgba(245,247,250,.7);
      display: flex;
      gap: 10px;
      align-items: flex-start;
      margin-top: 4px;
    }
    .info-box svg { flex-shrink: 0; margin-top: 1px; color: var(--accent); }

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

  <a href="../pages/admin/listUser.php" class="back-link">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="2">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
    Kembali ke Daftar Pengguna
  </a>

  <div class="form-panel__heading">
    <h1>Tambah Pengguna</h1>
    <p>Isi data di bawah untuk mendaftarkan akun pengguna baru.</p>
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

    <div class="form-group">
      <label for="nama_lengkap">Nama Lengkap</label>
      <input type="text" id="nama_lengkap" name="nama_lengkap"
             placeholder="Contoh: Budi Santoso"
             value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
             required />
    </div>

    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username"
             placeholder="Contoh: budi.santoso"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autocomplete="off" required />
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"
             placeholder="Contoh: budi@perusahaan.co.id"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
?>

<select id="role" name="role" required>

    <option value="" disabled
        <?= empty($_POST['role']) ? 'selected' : '' ?>>
        Pilih role pengguna
    </option>

    <?php foreach ($roles as $r): ?>

        <option
            value="<?= $r ?>"
            <?= (($_POST['role'] ?? '') === $r)
                ? 'selected'
                : '' ?>
        >
            <?= $r ?>
        </option>

    <?php endforeach; ?>

</select>
</div>

    <div class="info-box">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
           viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span>
        Sistem akan otomatis membuat <strong>password default</strong> dengan format <strong>username@123</strong>. Pengguna diwajibkan mengganti password saat pertama kali masuk ke sistem. Mohon untuk memberitahu pengguna dan selalu <strong>menjaga kerahasiaan.</strong>
      </span>
    </div>

    <button type="submit" class="btn-submit">Simpan Pengguna</button>

  </form>
</div>

</body>
</html>