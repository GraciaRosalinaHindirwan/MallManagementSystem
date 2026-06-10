<?php
// session_start();

// if(!isset($_SESSION['user_id'])){

//     header("Location: Login.php");
//     exit();
// }

$title = "Settings";
$page = "settings";

include '../Config/konek.php';
include '../Includes/head.php';

$sql = "
SELECT *
FROM users
WHERE role='Facility Manager'
LIMIT 1
";

$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

$user_id = $user['user_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $full_name = mysqli_real_escape_string(
        $conn,
        $_POST['full_name']
    );

    $email = mysqli_real_escape_string(
        $conn,
        $_POST['email']
    );

    mysqli_query(
        $conn,
        "
        UPDATE users
        SET
            full_name='$full_name',
            email='$email'
        WHERE user_id=$user_id
        "
    );

    if(
        !empty($_POST['new_password'])
    ){

        if(
            password_verify(
                $_POST['current_password'],
                $user['password_hash']
            )
        ){

            if(
                $_POST['new_password']
                ==
                $_POST['confirm_password']
            ){

                $hash = password_hash(
                    $_POST['new_password'],
                    PASSWORD_DEFAULT
                );

                mysqli_query(
                    $conn,
                    "
                    UPDATE users
                    SET password_hash='$hash'
                    WHERE user_id=$user_id
                    "
                );
            }
        }
    }

    header(
        "Location: Setting.php?success=1"
    );

    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
</head>

<body>
    <?php include '../Includes/sidebar.php'; ?>
    <?php include '../Includes/topbar.php'; ?>
    <form method="POST">
        <main class="mt-16 p-4 md:p-container-padding min-h-screen bg-surface-dim">
            <?php if (isset($_GET['success'])): ?>
                <div class="max-w-5xl mx-auto mb-6">
                    <div class="success-alert mb-6 bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl px-4 py-3">
                        Pengaturan berhasil disimpan.
                    </div>
                </div>
            <?php endif; ?>
            <div class="max-w-5xl mx-auto space-y-8">
                <div>
                    <h2 class="font-headline-h1 text-headline-h1 text-text-main">
                        Pengaturan Akun
                    </h2>
                    <p class="text-on-surface-variant">
                        Kelola informasi akun Facility Manager.
                    </p>
                </div>
                <!-- PROFILE -->
                <section class="glass-card rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-accent">
                            account_circle
                        </span>
                        <h3 class="font-subheading text-subheading">
                            Profil
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 flex justify-center">
                            <img
                                src="https://ui-avatars.com/api/?name=FM"
                                class="w-32 h-32 rounded-2xl border border-glass-stroke">

                        </div>
                        <div>
                            <label class="block mb-2 text-on-surface-variant">
                                Nama Lengkap
                            </label>
                            <input
                                name="full_name"
                                type="text"
                                value="<?= htmlspecialchars($user['full_name']) ?>"
                                class="w-full bg-primary-dark/30 border border-glass-stroke rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block mb-2 text-on-surface-variant">
                                Jabatan
                            </label>
                            <input
                                disabled
                                value="<?= htmlspecialchars($user['role']) ?>"
                                class="w-full bg-surface-container-low/50 border border-glass-stroke rounded-xl px-4 py-3">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-on-surface-variant">
                                Email
                            </label>
                            <input
                                name="email"
                                type="email"
                                value="<?= htmlspecialchars($user['email']) ?>"
                                class="w-full bg-primary-dark/30 border border-glass-stroke rounded-xl px-4 py-3">
                        </div>
                    </div>
                </section>

                <!-- SECURITY -->
                <section class="glass-card rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-accent">
                            lock
                        </span>
                        <h3 class="font-subheading text-subheading">
                            Keamanan
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block mb-2 text-on-surface-variant">
                                Password Lama
                            </label>
                            <input
                                name="current_password"
                                type="password"
                                class="w-full bg-primary-dark/30 border border-glass-stroke rounded-xl px-4 py-3">
                        </div>

                        <div>
                            <label class="block mb-2 text-on-surface-variant">
                                Password Baru
                            </label>
                            <input
                                name="new_password"
                                type="password"
                                class="w-full bg-primary-dark/30 border border-glass-stroke rounded-xl px-4 py-3">
                        </div>

                        <div>
                            <label class="block mb-2 text-on-surface-variant">
                                Konfirmasi Password
                            </label>
                            <input
                                name="confirm_password"
                                type="password"
                                class="w-full bg-primary-dark/30 border border-glass-stroke rounded-xl px-4 py-3">
                        </div>
                    </div>
                </section>

                <!-- ACCOUNT INFO -->
                <section class="glass-card rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-accent">
                            info
                        </span>
                        <h3 class="font-subheading text-subheading">
                            Informasi Akun
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-on-surface-variant text-sm">
                                Role
                            </p>
                            <p class="font-semibold">
                                <?= htmlspecialchars($user['role']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant text-sm">
                                Status
                            </p>
                            <p class="font-semibold text-green-400">

                                <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>

                            </p>
                        </div>

                        <div>
                            <p class="text-on-surface-variant text-sm">
                                Bergabung
                            </p>
                            <p class="font-semibold">
                                <?= date(
                                    'd M Y',
                                    strtotime($user['created_at'])
                                ) ?>
                            </p>
                        </div>
                    </div>
                </section>
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-10 py-3 rounded-xl font-bold bg-accent text-primary-dark shadow-[0_4px_20px_rgba(0,212,216,0.3)]">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </main>
    </form>
    <script src="../Public/Asset/sidebar.js"></script>
     <script>
        setTimeout(() => {
            const alertBox =
                document.querySelector('.success-alert');
            if (alertBox) {
                alertBox.remove();
            }
        }, 5000);
    </script>
</body>
</html>