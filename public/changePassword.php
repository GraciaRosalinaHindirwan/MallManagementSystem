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
    <div class="min-h-screen bg-black/30 flex items-center justify-center px-[40px] py-[40px]">
        <!-- login card -->
        <div class="bg-[var(--primary)] backdrop-blur-lg px-[40px] py-[100px] rounded-[16px] border-[1.5px] border-[var(--accent)]">
            <h1 class="text-[var(--text)] text-[36px] font-bold mb-[8px] text-center">
                Ganti Password mu
            </h1>
            <p class="text-[var(--text-secondary)] text-center mb-[40px]">
                Anda wajib mengganti password default.
            </p>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 rounded-lg bg-[var(--background)] border border-red-500 text-[var(--danger)] p-3">
                    <?= $_SESSION['error'] ?>
                </div>

                <?php
                unset($_SESSION['error']);
                endif;
            ?>

            <form method="POST" action="auth/changePasswordProcess.php" class="space-y-4">
                <?php
                    $fields = [
                        ['type' => "password",
                        'name' => "new_password",
                        'placeholder' => "",
<<<<<<< HEAD
                        'label' => "password baru"],
=======
                        'label' => "password baru",
                        'minlength' => '8',
                        'pattern' => '^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9])(?!.*[+{}]).{8,}$'],
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273

                        ['type' => "password",
                        'name' => "confirm_password",
                        'placeholder' => "",
                        'label' => "konfirmasi password"]
                    ];
                
                ?>

                <?php foreach($fields as $field): ?>
                <div class="relative">
                    <input 
                    type = "<?php echo $field['type']; ?>"
                    name = "<?php echo $field['name']; ?>"
                    placeholder = "<?php echo $field['placeholder']; ?>"
<<<<<<< HEAD
=======
                    <?= isset($field['minlength']) ? 'minlength="'.$field['minlength'].'"' : '' ?>
                    <?= isset($field['pattern']) ? 'pattern="'.$field['pattern'].'"' : '' ?>
                    required
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
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
<<<<<<< HEAD
=======

                    <?php if($field['name'] == 'new_password'): ?>
                        <p class="mt-1 text-xs text-[var(--text-secondary)]">
                            Min. 8 karakter, A-Z, 0-9, simbol (kecuali +, {, })
                        </p>
                    <?php endif; ?>
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
                </div>
                <?php endforeach; ?>
                <button type="submit" class="w-full bg-[var(--secondary)] text-[var(--text)] font-bold py-[12px] rounded-[8px] hover:bg-[var(--secondary-dark)] transition-colors">
                    Ubah Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>