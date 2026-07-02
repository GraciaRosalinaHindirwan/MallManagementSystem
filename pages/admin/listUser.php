<?php
require_once '../../config/konek.php';

$users = [];

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.username,
        u.email,
        rp.role
    FROM 09_users u
    LEFT JOIN 09_role_pages rp
        ON u.role_page_id = rp.id
    ORDER BY u.full_name ASC
";

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    $users[] = $row;
}

// Ambil daftar role dari tabel 09_role_pages
$roleResult = $conn->query("SELECT id, role FROM 09_role_pages ORDER BY role ASC");
$roles = [];
while($row = $roleResult->fetch_assoc()){
    $roles[] = $row;
}

if(isset($_GET['delete'])){

    $id = (int)$_GET['delete'];

    $sql = "DELETE FROM 09_users WHERE id = $id";

    if(mysqli_query($conn, $sql)){
        header("Location: listUser.php");
        exit;
    }else{
        echo mysqli_error($conn);
    }
}
require_once '../../includes/navbarM09.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Kelola Pengguna</title>
    <link rel="stylesheet" href="../../public/asset/css/designSystem.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b1629; }
        .badge-active  { background: rgba(34,197,94,.15); color: #4ade80; border: 1px solid #16a34a; }
        .badge-blocked { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid #dc2626; }
        .table-row:hover td { background: rgba(0,212,216,.05); }

        /* ===== Modal konfirmasi hapus (2 tahap) ===== */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.6);
            display: none;
            align-items: center; justify-content: center;
            z-index: 50;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #0f1c33;
            border: 1px solid #1e3a5f;
            border-radius: 14px;
            width: 100%; max-width: 420px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,.5);
        }
        .modal-step { display: none; }
        .modal-step.active { display: block; }
    </style>
</head>
<body class="min-h-screen text-white p-6">

    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-white">Manajemen Akun Pengguna</h1>
        </div>

        <!-- Notifikasi -->
        <?php if(isset($_SESSION['success'])): ?>
        <div class="mb-4 rounded-lg border border-green-500 bg-green-500/10 text-green-400 p-3">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="mb-4 rounded-lg border border-red-500 bg-red-500/10 text-red-400 p-3">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Toolbar: Search + Filter -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <input
                type="text"
                id="searchInput"
                placeholder="Cari nama / username..."
                class="flex-1 bg-slate-900 border border-cyan-700 rounded-lg px-4 py-2 text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400"
                onkeyup="filterTable()"
            >
            <select
                id="roleFilter"
                class="bg-slate-900 border border-cyan-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-cyan-400"
                onchange="filterTable()"
            >
                <option value="">Semua Role</option>
                <?php foreach($roles as $r): ?>
                <option value="<?= htmlspecialchars($r['role']) ?>">
                    <?= htmlspecialchars($r['role']) ?>
                </option>
                <?php endforeach; ?>
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
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900/50">
                    <?php if(empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada data pengguna.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($users as $i => $u): ?>
                    <tr class="table-row transition-colors"
                        data-name="<?= strtolower($u['full_name']) ?>"
                        data-username="<?= strtolower($u['username']) ?>"
                        data-role="<?= htmlspecialchars($u['role']) ?>">
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
                            <div class="flex justify-center gap-2">

                                <a href="edit.php?id=<?= $u['id'] ?>"
                                   class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-500 text-white">
                                    Edit
                                </a>

                                <button type="button"
                                   onclick="openDeleteModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"
                                   class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-500 text-white">
                                    Hapus
                                </button>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pesan tidak ditemukan (search) -->
        <p id="noResult" class="hidden text-center text-slate-500 mt-4">Tidak ada pengguna yang sesuai dengan pencarian.</p>

    </div>

    <!-- ===== Modal Konfirmasi Hapus (2 tahap) ===== -->
    <div class="modal-overlay" id="deleteModalOverlay">
        <div class="modal-box">

            <!-- Step 1 -->
            <div class="modal-step active" id="modalStep1">
                <h2 class="text-lg font-bold text-white mb-2">Hapus pengguna ini?</h2>
                <p class="text-slate-400 text-sm mb-6">
                    Anda akan menghapus user
                    <span class="text-white font-semibold" id="targetUsernameStep1"></span>.
                    Tindakan ini akan menghapus akun secara permanen.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 text-sm rounded-lg bg-slate-700 hover:bg-slate-600 text-white">
                        Batal
                    </button>
                    <button type="button" onclick="goToStep2()"
                        class="px-4 py-2 text-sm rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-semibold">
                        Lanjutkan
                    </button>
                </div>
            </div>

            <!-- Step 2: konfirmasi akhir, ketik ulang username -->
            <div class="modal-step" id="modalStep2">
                <h2 class="text-lg font-bold text-red-400 mb-2">Konfirmasi Akhir</h2>
                <p class="text-slate-400 text-sm mb-3">
                    Ketik username <span class="text-white font-semibold" id="targetUsernameStep2"></span>
                    untuk memastikan Anda benar-benar ingin menghapusnya.
                </p>
                <input type="text" id="confirmUsernameInput" autocomplete="off"
                    class="w-full mb-2 bg-slate-900 border border-red-600 rounded-lg px-3 py-2 text-white focus:outline-none"
                    placeholder="Ketik username di sini...">
                <p class="text-xs text-red-400 mb-4 hidden" id="confirmMismatchMsg">Username tidak cocok.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="goToStep1()"
                        class="px-4 py-2 text-sm rounded-lg bg-slate-700 hover:bg-slate-600 text-white">
                        Kembali
                    </button>
                    <button type="button" onclick="confirmFinalDelete()"
                        class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-500 text-white font-semibold">
                        Hapus Permanen
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const role   = document.getElementById('roleFilter').value.toLowerCase();
            const rows   = document.querySelectorAll('#userTable tbody tr[data-name]');
            let visible  = 0;

            rows.forEach(row => {
                const nameMatch = row.dataset.name.includes(search) || row.dataset.username.includes(search);
                const roleMatch = role === '' || row.dataset.role.toLowerCase() === role;
                const show = nameMatch && roleMatch;
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            document.getElementById('noResult').classList.toggle('hidden', visible > 0);
        }

        // ===== Delete modal (2-step confirmation) =====
        let pendingDeleteId = null;
        let pendingDeleteUsername = null;

        function openDeleteModal(id, username) {
            pendingDeleteId = id;
            pendingDeleteUsername = username;
            document.getElementById('targetUsernameStep1').textContent = username;
            document.getElementById('targetUsernameStep2').textContent = username;
            document.getElementById('confirmUsernameInput').value = '';
            document.getElementById('confirmMismatchMsg').classList.add('hidden');
            goToStep1();
            document.getElementById('deleteModalOverlay').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModalOverlay').classList.remove('active');
            pendingDeleteId = null;
            pendingDeleteUsername = null;
        }

        function goToStep1() {
            document.getElementById('modalStep1').classList.add('active');
            document.getElementById('modalStep2').classList.remove('active');
        }

        function goToStep2() {
            document.getElementById('modalStep1').classList.remove('active');
            document.getElementById('modalStep2').classList.add('active');
        }

        function confirmFinalDelete() {
            const typed = document.getElementById('confirmUsernameInput').value.trim();
            if (typed !== pendingDeleteUsername) {
                document.getElementById('confirmMismatchMsg').classList.remove('hidden');
                return;
            }
          window.location.href ='listUser.php?delete=' + encodeURIComponent(pendingDeleteId);
        }

        // klik di luar box modal = batal
        document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>