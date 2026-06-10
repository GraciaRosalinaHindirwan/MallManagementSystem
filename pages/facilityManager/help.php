<?php
$title = "Work Order";
$page = "work_order";

include '../Includes/head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HELP DESK</title>
</head>
<body>
    <?php include '../Includes/sidebar.php'; ?>
    <?php include '../Includes/topbar.php'; ?>
<main class="mt-16 p-gutter h-[calc(100vh-64px)] overflow-y-auto custom-scrollbar">

    <!-- HEADER -->
    <div class="mb-8">
        <h2 class="font-headline-h2 text-headline-h2 text-text-main mb-2">
            Help Center
        </h2>

        <p class="text-on-surface-variant">
            Facility Management System Documentation & User Guide
        </p>
    </div>

    <!-- SEARCH -->
    <div class="glass-card rounded-xl p-5 mb-8">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-4 top-3 text-on-surface-variant">
                search
            </span>

            <input
                id="helpSearch"
                type="text"
                placeholder="Search documentation, workflows, troubleshooting..."
                class="w-full pl-12 pr-4 py-3 rounded-lg bg-primary-dark border border-glass-stroke">
        </div>
    </div>

    <!-- SYSTEM OVERVIEW -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                📖 System Overview
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <p class="text-on-surface-variant leading-relaxed mb-4">
                Facility Management System digunakan untuk mengelola proses pelaporan kerusakan,
                penugasan teknisi, monitoring pekerjaan, hingga penyelesaian maintenance dalam satu
                platform terintegrasi.
            </p>

            <div class="glass-card p-4 rounded-lg">
                <h4 class="font-semibold mb-3">
                    Main Workflow
                </h4>

                <div class="space-y-2 text-on-surface-variant">
                    <p>1. Customer Service membuat Damage Report</p>
                    <p>2. Ticket masuk ke Damage List</p>
                    <p>3. Facility Manager melakukan review</p>
                    <p>4. Work Order dibuat</p>
                    <p>5. Teknisi ditugaskan</p>
                    <p>6. Teknisi melakukan perbaikan</p>
                    <p>7. Progress dicatat pada Activity Timeline</p>
                    <p>8. Facility Manager melakukan validasi</p>
                    <p>9. Ticket ditutup (Closed)</p>
                </div>
            </div>

        </div>
    </div>

    <!-- DAMAGE REPORT -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                🎫 Damage Report Management
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <p class="mb-4 text-on-surface-variant">
                Damage Report digunakan untuk mencatat kerusakan fasilitas yang dilaporkan oleh pengguna atau customer service.
            </p>

            <h4 class="font-semibold mb-2">
                Required Information
            </h4>

            <ul class="list-disc pl-6 space-y-1 text-on-surface-variant mb-5">
                <li>Asset Name</li>
                <li>Asset Category</li>
                <li>Location</li>
                <li>Floor</li>
                <li>Damage Type</li>
                <li>Priority</li>
                <li>Description</li>
                <li>Photo Evidence</li>
            </ul>

            <div class="glass-card p-4 rounded-lg">
                <h4 class="font-semibold mb-2">
                    Ticket Status
                </h4>

                <div class="space-y-2 text-on-surface-variant">
                    <p><strong>Open</strong> → Ticket baru dibuat.</p>
                    <p><strong>Assigned</strong> → Sudah memiliki teknisi.</p>
                    <p><strong>In Progress</strong> → Sedang dikerjakan.</p>
                    <p><strong>Resolved</strong> → Perbaikan selesai.</p>
                    <p><strong>Closed</strong> → Ticket ditutup oleh Facility Manager.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- WORK ORDER -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                🛠 Work Order Management
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <p class="text-on-surface-variant mb-4">
                Work Order digunakan untuk menugaskan teknisi yang bertanggung jawab terhadap sebuah kerusakan.
            </p>

            <h4 class="font-semibold mb-2">
                Assignment Process
            </h4>

            <ol class="list-decimal pl-6 space-y-2 text-on-surface-variant">
                <li>Pilih Ticket.</li>
                <li>Tentukan Required Skill.</li>
                <li>Tentukan Priority.</li>
                <li>Set SLA Target.</li>
                <li>Pilih teknisi yang direkomendasikan.</li>
                <li>Klik Assign Task.</li>
            </ol>

            <div class="glass-card p-4 rounded-lg mt-4">
                <h4 class="font-semibold mb-2">
                    Generated Information
                </h4>

                <ul class="list-disc pl-6 text-on-surface-variant space-y-1">
                    <li>Work Order Number</li>
                    <li>Assigned Technician</li>
                    <li>Priority Level</li>
                    <li>SLA Target</li>
                    <li>Due Date</li>
                </ul>
            </div>

        </div>
    </div>

    <!-- TECHNICIAN -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                👷 Technician Management
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <h4 class="font-semibold mb-2">
                Technician Status
            </h4>

            <div class="space-y-3 text-on-surface-variant">

                <p>
                    <strong>Available</strong><br>
                    Technician siap menerima pekerjaan baru.
                </p>

                <p>
                    <strong>On-Duty</strong><br>
                    Technician sedang menangani Work Order.
                </p>

                <p>
                    <strong>Offline</strong><br>
                    Technician tidak tersedia.
                </p>

            </div>

            <div class="glass-card p-4 rounded-lg mt-4">
                <h4 class="font-semibold mb-2">
                    Technician Profile Data
                </h4>

                <ul class="list-disc pl-6 text-on-surface-variant space-y-1">
                    <li>Employee ID</li>
                    <li>Specialization</li>
                    <li>Skill List</li>
                    <li>Proficiency Level</li>
                    <li>Availability Status</li>
                </ul>
            </div>

        </div>
    </div>

    <!-- MATCHING -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                🧠 Intelligent Technician Matching
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <p class="text-on-surface-variant mb-4">
                Sistem secara otomatis menghitung rekomendasi teknisi berdasarkan beberapa faktor.
            </p>

            <div class="glass-card p-4 rounded-lg">
                <h4 class="font-semibold mb-3">
                    Matching Factors
                </h4>

                <div class="space-y-2 text-on-surface-variant">
                    <p>✓ Skill Match</p>
                    <p>✓ Proficiency Level</p>
                    <p>✓ Availability Status</p>
                    <p>✓ Active Workload</p>
                </div>
            </div>

            <p class="mt-4 text-on-surface-variant">
                Semakin tinggi Match Score, semakin direkomendasikan teknisi tersebut.
            </p>

        </div>
    </div>

    <!-- SLA -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                ⏱ SLA Monitoring
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <p class="text-on-surface-variant mb-4">
                SLA digunakan untuk mengukur batas waktu penyelesaian pekerjaan.
            </p>

            <div class="space-y-3">

                <div class="glass-card p-4 rounded-lg">
                    🟢 Green : More than 50% remaining time
                </div>

                <div class="glass-card p-4 rounded-lg">
                    🟡 Yellow : Less than 50% remaining time
                </div>

                <div class="glass-card p-4 rounded-lg">
                    🔴 Red : Less than 20% remaining time
                </div>

            </div>

            <p class="mt-4 text-on-surface-variant">
                Countdown dihitung dari Assigned Time hingga SLA Target.
            </p>

        </div>
    </div>

    <!-- TROUBLESHOOTING -->
    <div class="help-section glass-card rounded-xl overflow-hidden mb-4">
        <button class="accordion-btn w-full p-5 flex justify-between items-center text-left">
            <span class="font-semibold text-lg">
                🔧 Troubleshooting & FAQ
            </span>

            <span class="material-symbols-outlined accordion-icon">
                expand_more
            </span>
        </button>

        <div class="accordion-content hidden px-5 pb-5">

            <div class="space-y-5">

                <div>
                    <h4 class="font-semibold">
                        Technician tidak muncul pada rekomendasi?
                    </h4>
                    <p class="text-on-surface-variant">
                        Pastikan skill teknisi sesuai dengan Required Skill dan statusnya Available.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold">
                        Ticket tidak dapat diassign?
                    </h4>
                    <p class="text-on-surface-variant">
                        Pastikan Required Skill dan Technician sudah dipilih.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold">
                        SLA tidak muncul?
                    </h4>
                    <p class="text-on-surface-variant">
                        Periksa apakah Work Order sudah memiliki SLA Target.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold">
                        Mengapa ticket hilang dari Damage List?
                    </h4>
                    <p class="text-on-surface-variant">
                        Ticket dengan status Closed tidak ditampilkan pada daftar aktif.
                    </p>
                </div>

            </div>

        </div>
    </div>

</main>

<script>

document.querySelectorAll('.accordion-btn')
.forEach(btn => {

    btn.addEventListener('click', () => {

        const content =
        btn.nextElementSibling;

        const icon =
        btn.querySelector('.accordion-icon');

        content.classList.toggle('hidden');

        if(content.classList.contains('hidden')){

            icon.innerText = 'expand_more';

        }else{

            icon.innerText = 'expand_less';

        }

    });

});

document.getElementById('helpSearch')
.addEventListener('keyup', function(){

    const keyword =
    this.value.toLowerCase();

    document.querySelectorAll('.help-section')
    .forEach(section => {

        section.style.display =
        section.innerText.toLowerCase().includes(keyword)
        ? ''
        : 'none';

    });

});

</script>
<script src="../Asset/sidebar.js"></script>
</body>
</html>