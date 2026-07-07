<?php
session_start();
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../config/konek.php';

$userRepository = new UserRepository();
$users = $userRepository->getAllUsersWithRole();

// Ambil daftar role dari tabel 09_role_pages
$roleResult = $conn->query("SELECT id, role FROM 09_role_pages ORDER BY role ASC");
$roles = [];
while ($row = $roleResult->fetch_assoc()) {
    $roles[] = $row;
}
require_once '../../includes/navbarM09.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Manajemen Akun Pengguna</title>
    <link rel="stylesheet" href="../../public/asset/css/designSystem.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0b1629;
        }

        .badge-active {
            background: rgba(34, 197, 94, .15);
            color: #4ade80;
            border: 1px solid #16a34a;
        }

        .badge-blocked {
            background: rgba(239, 68, 68, .15);
            color: #f87171;
            border: 1px solid #dc2626;
        }

        .table-row:hover td {
            background: rgba(0, 212, 216, .05);
        }
    </style>
</head>

<body class="min-h-screen text-white p-6">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-white">Manajemen Akun Pengguna</h1>
            <div
                class="mt-4 p-4 rounded-lg border border-cyan-700 bg-cyan-900/30 text-cyan-200 text-sm leading-relaxed">
                Sistem akan otomatis membuat password default dengan format <strong>username@123</strong>. Pengguna
                diwajibkan mengganti password saat pertama kali masuk ke sistem. Mohon untuk memberitahu pengguna dan
                selalu menjaga kerahasiaan.
            </div>
        </div>

        <!-- Notifikasi -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 rounded-lg border border-green-500 bg-green-500/10 text-green-400 p-3">
                <?= htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 rounded-lg border border-red-500 bg-red-500/10 text-red-400 p-3">
                <?= htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Toolbar: Search + Filter -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <input type="text" id="searchInput" placeholder="Cari nama / username..."
                class="flex-1 bg-slate-900 border border-cyan-700 rounded-lg px-4 py-2 text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400"
                onkeyup="filterTable()">
            <select id="roleFilter"
                class="bg-slate-900 border border-cyan-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-cyan-400"
                onchange="filterTable()">
                <option value="">Semua Role</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= htmlspecialchars($r['role']) ?>">
                        <?= htmlspecialchars($r['role']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="statusFilter"
                class="bg-slate-900 border border-cyan-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-cyan-400"
                onchange="filterTable()">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="terblokir">Terblokir</option>
            </select>
        </div>

        <!-- Tabel -->
        <div class="overflow-x-auto rounded-xl border border-slate-700 shadow-lg">
            <table class="w-full text-sm text-left" id="userTable">
                <thead class="text-xs uppercase tracking-wider text-cyan-400 bg-slate-900 border-b border-slate-700">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Full Name</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900/50">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">Tidak ada data pengguna.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $i => $u): ?>
                            <?php $statusLabel = $u['is_blocked'] ? 'terblokir' : 'aktif'; ?>
                            <tr class="table-row transition-colors" data-name="<?= strtolower($u['full_name']) ?>"
                                data-username="<?= strtolower($u['username']) ?>"
                                data-role="<?= htmlspecialchars($u['role']) ?>" data-status="<?= $statusLabel ?>">
                                <td class="px-4 py-3 text-slate-400"><?= $i + 1 ?></td>
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs border border-cyan-700 text-cyan-300">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($u['is_blocked']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold badge-blocked">Terblokir</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold badge-active">Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form action="adminResetPasswordProcess.php" method="POST"
                                        onsubmit="return confirm('Reset password pengguna <?= htmlspecialchars($u['username']) ?> ke Password Default <?= htmlspecialchars($u['username']) ?>@123 ?')">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-cyan-700 hover:bg-cyan-500 text-white transition-colors">
                                            Reset Password
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pesan tidak ditemukan (search) -->
        <p id="noResult" class="hidden text-center text-slate-500 mt-4">Tidak ada pengguna yang sesuai dengan pencarian.
        </p>

    </div>

    <script>
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const role = document.getElementById('roleFilter').value.toLowerCase();
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#userTable tbody tr[data-name]');
            let visible = 0;

            rows.forEach(row => {
                const nameMatch = row.dataset.name.includes(search) || row.dataset.username.includes(search);
                const roleMatch = role === '' || row.dataset.role.toLowerCase() === role;
                const statusMatch = status === '' || row.dataset.status === status;
                const show = nameMatch && roleMatch && statusMatch;
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            document.getElementById('noResult').classList.toggle('hidden', visible > 0);
        }
    </script>
</body>

</html>