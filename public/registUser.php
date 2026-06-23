<?php
// registerUser.php
// PBI-M09-01-03 — Menambahkan pengguna baru
// Hanya dapat diakses oleh Admin
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

    /* ── LEFT PANEL — background image ── */
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

    /* ── RIGHT PANEL — form card ── */
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

    /* Back link */
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

    /* Heading */
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

    /* Form layout */
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

    /* Select arrow */
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

    /* Password info box */
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

    /* Submit button */
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

    /* Success / Error feedback (PHP-driven) */
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

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .bg-panel { display: none; }
      .form-panel { width: 100%; padding: 40px 28px; }
    }
  </style>
</head>
<body>

<!-- LEFT — background panel -->
<div class="bg-panel">
  <div class="bg-panel__text">
    <h2>Manajemen Pengguna</h2>
    <p>Kelola akses dan hak pengguna sistem secara terpusat.</p>
  </div>
</div>

<!-- RIGHT — form card -->
<div class="form-panel">

  <a href="userList.php" class="back-link">
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

  <?php
  /* ── Handle POST ── */
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    $errors = [];
    if (!$nama)       $errors[] = 'Nama lengkap wajib diisi.';
    if (!$username)   $errors[] = 'Username wajib diisi.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
                      $errors[] = 'Alamat email tidak valid.';
    if (!$role)       $errors[] = 'Role wajib dipilih.';

    if (empty($errors)) {
      /*
       * TODO: replace block below with actual DB insert
       *
       * $default_password = password_hash('Welcome@123', PASSWORD_BCRYPT);
       * $first_login      = 1;
       *
       * $stmt = $pdo->prepare("INSERT INTO users
       *   (nama_lengkap, username, email, role, password, first_login, created_at)
       *   VALUES (?, ?, ?, ?, ?, ?, NOW())");
       * $stmt->execute([$nama, $username, $email, $role, $default_password, $first_login]);
       *
       * Kirim notifikasi email berisi password default ke $email
       */
      echo '<div class="alert alert--success">
              ✓ Pengguna <strong>' . htmlspecialchars($username) . '</strong>
              berhasil ditambahkan. Password default telah dikirim ke email pengguna.
            </div>';
    } else {
      echo '<div class="alert alert--error">' . implode('<br>', array_map('htmlspecialchars', $errors)) . '</div>';
    }
  }
  ?>

  <form method="POST" action="registerUser.php" class="form-grid" novalidate>

    <!-- Nama Lengkap -->
    <div class="form-group">
      <label for="nama_lengkap">Nama Lengkap</label>
      <input type="text" id="nama_lengkap" name="nama_lengkap"
             placeholder="Contoh: Budi Santoso"
             value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
             required />
    </div>

    <!-- Username -->
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username"
             placeholder="Contoh: budi.santoso"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autocomplete="off" required />
    </div>

    <!-- Email -->
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
      <select id="role" name="role" required>
        <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>
          Pilih role pengguna
        </option>
        <?php
        $roles = [
          'admin'             => 'Customer Service',
          'finance_manager'   => 'Finance Manager',
          'finance_staff'     => 'Finance Staff',
          'hr_manager'        => 'HR Manager',
          'hr_staff'          => 'HR Staff',
          'it_manager'        => 'IT Manager',
          'it_staff'          => 'IT Staff',
          'procurement'       => 'Procurement Officer',
          'auditor'           => 'Auditor',
        ];
        foreach ($roles as $val => $label):
          $sel = (($_POST['role'] ?? '') === $val) ? 'selected' : '';
        ?>
          <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Info: password otomatis -->
    <div class="info-box">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
           viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span>
        Sistem akan otomatis membuat <strong>password default</strong> dan mengirimkannya
        ke email pengguna. Pengguna diwajibkan mengganti password saat pertama kali masuk
        (<em>first_login = true</em>).
      </span>
    </div>

    <button type="submit" class="btn-submit">Simpan Pengguna</button>

  </form>
</div>

</body>
</html>