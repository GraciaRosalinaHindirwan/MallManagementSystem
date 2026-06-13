<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication</title>
    <link rel="stylesheet" href="asset/css/designSystem.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[url('/Mall/public/asset/images/background.png')] bg-cover bg-center">
    <div class="min-h-screen bg-black/30 flex items-center justify-end px-[40px] py-[40px]">
        <!-- login card -->
        <div class="bg-[var(--primary)] backdrop-blur-lg px-[40px] py-[100px] rounded-[16px] border-[1.5px] border-[var(--accent)]">
            <h1 class="text-[var(--text)] text-[36px] font-bold mb-[8px] text-center">
                Welcome Back!
            </h1>
            <p class="text-[var(--text-secondary)] text-center mb-[40px]">
                Selamat datang kembali. Masuk untuk melanjutkan ke dashboard.
            </p>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 rounded-lg bg-[var(--background)] border border-red-500 text-[var(--danger)] p-3">
                    <?= $_SESSION['error'] ?>
                </div>

                <?php
                unset($_SESSION['error']);

                elseif(isset($_SESSION['success'])): ?>
                <div class="mb-4 rounded-lg bg-[var(--background)] border border-green-500 text-[var(--success)] p-3">
                    <?= $_SESSION['success'] ?>
                </div>
                <?php
                unset($_SESSION['success']);
                endif;
            ?>

            <form method="POST" action="auth/loginProcess.php" class="space-y-4">
                <?php
                    $fields = [
                        ['type' => "text",
                        'name' => "username",
                        'placeholder' => "",
                        'label' => "username"],

                        ['type' => "password",
                        'name' => "password",
                        'placeholder' => "",
                        'label' => "password"]
                    ];
                
                ?>

                <?php foreach($fields as $field): ?>
                <div class="relative">
                    <input 
                    type = "<?php echo $field['type']; ?>"
                    name = "<?php echo $field['name']; ?>"
                    placeholder = "<?php echo $field['placeholder']; ?>"

                    class="peer w-full bg-transparent border-[1.5px] border-[var(--accent)] rounded-[8px] px-[16px] pt-[20px] pb-[16px] text-[var(--text)] focus:outline-none focus:border-[var(--accent)]"/>

                    <label
                    for = "<?php echo $field['label']; ?>"
                    class="
                    absolute left-4 top-1
                    text-sm text-[var(--text-secondary)] transition-all
                    peer-placeholder-shown:top-[16px]
                    peer-placeholder-shown:text-base
                    peer-placeholder-shown:text-[var(--text-secondary)]
                    peer-focus:top-1
                    peer-focus:text-sm
                    peer-focus:text-cyan-400">
                        <?php echo $field['label']; ?>
                    </label>
                </div>
                <?php endforeach; ?>

                <div class="flex justify-between items-center text-sm mt-[8px]">
                    <label class="text-[var(--text-secondary)] flex items-center gap-2">
                        <input type="checkbox"
                       style="accent-color: var(--accent)"
                        class="w-4 h-4">
                        Ingat pengguna
                    </label>

                    <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--accent)] transition-colors">
                        Lupa Password?
                    </a>
                </div>

                <div class="mb-4">
                    <label class="block text-[16px] text-[var(--text)] mb-2">
                        Kode Verifikasi
                    </label>
                    <div class="flex items-center gap-2 mb-2">
                        <img
                            id="captcha-img"
                            src="auth/captcha.php"
                            alt="Captcha"
                            class="h-12 rounded-lg border border-[var(--secondary)]"
                        >
                        <button
                            type="button"
                            onclick="refreshCaptcha()"
                            class="px-4 py-3 rounded-lg
                                border border-[var(--secondary)]
                                text-[var(--accent)]
                                hover:bg-[var(--secondary)]/20">
                            ↻
                        </button>
                    </div>
                    <input
                        type="text"
                        name="captcha"
                        required
                        placeholder="Masukkan kode captcha"
                        class="w-full rounded-lg
                            bg-[rgba(2,31,66,0.55)]
                            border border-[rgba(0,212,216,0.25)]
                            px-4 py-3
                            text-white
                            placeholder:text-white/40
                            focus:outline-none
                            focus:border-[var(--accent)]">
                </div>

                <button type="submit" class="w-full bg-[var(--secondary)] text-[var(--text)] font-bold py-[12px] rounded-[8px] hover:bg-[var(--secondary-dark)] transition-colors">
                    Masuk
                </button>
            </form>
        </div>
    </div>

<script>
function refreshCaptcha() {
    document.getElementById('captcha-img').src =
        'auth/captcha.php?' + Date.now();
}
</script>

</body>
</html>