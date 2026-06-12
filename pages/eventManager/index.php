<?php
require_once 'event_data.php';

$semua   = getPengajuan();
$pending = array_filter($semua, fn($p) => $p['status'] === 'pending');
$approved = array_filter($semua, fn($p) => $p['status'] === 'approved');
$analytics = $_SESSION['event_analytics_extended'] ?? [];
$totalRev = array_sum(array_map(fn($a) => $a['revenue_sewa']+$a['revenue_tiket']+$a['revenue_sponsor'], $analytics));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO MALL - Dashboard Event Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/asset/css/designSystem.css" rel="stylesheet">
    <style>
        body { background: var(--background); color: var(--text); font-family: var(--font-family); min-height: 100vh; }

        .hero {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 60%, #0D4859 100%);
            padding: 3rem 2rem 2.5rem; border-radius: 16px; margin-bottom: 2rem; position: relative; overflow: hidden;
        }
        .hero::after {
            content: '';
            position: absolute; right: -60px; top: -60px;
            width: 300px; height: 300px; border-radius: 50%;
            background: radial-gradient(circle, rgba(0,212,216,.12) 0%, transparent 70%);
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(0,212,216,.15); border: 1px solid rgba(0,212,216,.25);
            border-radius: 20px; padding: 4px 14px; font-size: 11px; color: var(--accent);
            margin-bottom: 1rem;
        }

        .kpi-mini {
            background: rgba(255,255,255,.07); border-radius: 10px;
            padding: .85rem 1rem; text-align: center;
        }
        .kpi-mini .val { font-size: 1.3rem; font-weight: 700; }
        .kpi-mini .lbl { font-size: 10px; opacity: .55; text-transform: uppercase; letter-spacing: .05em; }

        .feature-card {
            background: var(--primary);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px; padding: 1.5rem;
            text-decoration: none; color: var(--text);
            display: block; transition: all .2s; height: 100%;
        }
        .feature-card:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,.3);
            color: var(--text);
        }
        .feature-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 1rem;
        }
        .feature-card h6 { font-weight: 700; margin-bottom: .35rem; }
        .feature-card p { font-size: var(--caption); opacity: .6; margin: 0; line-height: 1.5; }

        .badge-notify {
            background: var(--danger); color: #fff; font-size: 10px;
            border-radius: 10px; padding: 1px 7px; margin-left: 6px;
        }

        .pbi-table td, .pbi-table th { font-size: var(--caption); border-color: rgba(255,255,255,.06); vertical-align: middle; padding: .5rem .75rem; }
        .pbi-table thead th { background: var(--primary-dark); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; opacity: .7; }

        .activity-item { padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.05); }
        .activity-item:last-child { border: none; }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

    <div class="hero">
        <div class="hero-badge"><i class="bi bi-calendar-event"></i>SISFO MALL - EVENT</div>
        <h2 class="fw-bold mb-1">Event Management</h2>
        <div class="row g-2" style="max-width:600px">
            <div class="col-3"><div class="kpi-mini">
                <div class="val"><?= count($semua) ?></div>
                <div class="lbl">Total Pengajuan</div>
            </div></div>
            <div class="col-3"><div class="kpi-mini">
                <div class="val" style="color:#fde68a"><?= count($pending) ?></div>
                <div class="lbl">Pending Review</div>
            </div></div>
            <div class="col-3"><div class="kpi-mini">
                <div class="val" style="color:#86efac"><?= count($approved) ?></div>
                <div class="lbl">Event Approved</div>
            </div></div>
            <div class="col-3"><div class="kpi-mini">
                <div class="val" style="color:var(--text-accent)">
                    <?= $totalRev > 0 ? 'Rp '.number_format($totalRev/1000000,0).'jt' : '—' ?>
                </div>
                <div class="lbl">Revenue Event</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <!-- PBI-01: Booking Form -->
        <div class="col-md-6 col-lg-3">
            <a href="../eventOrganizer/event_booking_form.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(0,212,216,.15); color:var(--accent)">
                    <i class="bi bi-calendar-plus"></i>
                </div>
                <div style="font-size:10px; color:var(--accent); margin-bottom:4px">PBI-M04-03-01</div>
                <h6>Form Pengajuan Booking
                    <?php if (count($pending) > 0): ?>
                    <span class="badge-notify"><?= count($pending) ?></span>
                    <?php endif; ?>
                </h6>
                <p>EO/Tenant ajukan booking area event. Conflict-check otomatis berjalan saat submit.</p>
                <div class="mt-3" style="font-size:var(--caption); color:var(--accent)">
                    <i class="bi bi-arrow-right me-1"></i>Buka Form
                </div>
            </a>
        </div>

        <!-- PBI-01: Status -->
        <div class="col-md-6 col-lg-3">
            <a href="../eventOrganizer/event_booking_status.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(255,182,42,.12); color:var(--text-accent)">
                    <i class="bi bi-list-check"></i>
                </div>
                <div style="font-size:10px; color:var(--text-accent); margin-bottom:4px">PBI-M04-03-01</div>
                <h6>Status Pengajuan</h6>
                <p>Pantau progres semua pengajuan dengan timeline visual: pending → approved → kontrak.</p>
                <div class="mt-3" style="font-size:var(--caption); color:var(--text-accent)">
                    <i class="bi bi-arrow-right me-1"></i>Lihat Status
                </div>
            </a>
        </div>

        <!-- PBI-02: Kalender + Approval -->
        <div class="col-md-6 col-lg-3">
            <a href="event_calendar.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(22,126,128,.2); color:#67e8f9">
                    <i class="bi bi-calendar2-week"></i>
                </div>
                <div style="font-size:10px; color:#67e8f9; margin-bottom:4px">PBI-M04-03-02</div>
                <h6>Kalender & Approval
                    <?php if (count($pending) > 0): ?>
                    <span class="badge-notify"><?= count($pending) ?></span>
                    <?php endif; ?>
                </h6>
                <p>Kalender visual per area event + conflict-check + workflow approve / tolak / revisi.</p>
                <div class="mt-3" style="font-size:var(--caption); color:#67e8f9">
                    <i class="bi bi-arrow-right me-1"></i>Buka Kalender
                </div>
            </a>
        </div>

        <!-- PBI-03: Vendor Ticketing Sponsorship -->
        <div class="col-md-6 col-lg-3">
            <a href="event_vendor_ticketing.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(139,92,246,.2); color:#c4b5fd">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div style="font-size:10px; color:#c4b5fd; margin-bottom:4px">PBI-M04-03-03</div>
                <h6>Vendor · Ticketing · Sponsorship</h6>
                <p>Database vendor event, setup tiket digital per event, manajemen sponsor & settlement.</p>
                <div class="mt-3" style="font-size:var(--caption); color:#c4b5fd">
                    <i class="bi bi-arrow-right me-1"></i>Kelola Koordinasi
                </div>
            </a>
        </div>

        <!-- PBI-04: Analytics -->
        <div class="col-12 col-md-6">
            <a href="event_analytics.php" class="feature-card" style="display:flex; gap:1.5rem; align-items:center">
                <div class="feature-icon" style="background:rgba(34,197,94,.15); color:#86efac; min-width:48px">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <div>
                    <div style="font-size:10px; color:#86efac; margin-bottom:4px">PBI-M04-03-04</div>
                    <h6 class="mb-1">Post-Event Analytics Dashboard</h6>
                    <p>Laporan pengunjung, revenue (sewa + tiket + sponsor), traffic impact, dan rating kepuasan per event untuk evaluasi &amp; perencanaan ke depan.</p>
                    <div class="mt-2" style="font-size:var(--caption); color:#86efac">
                        <i class="bi bi-arrow-right me-1"></i>Buka Analytics
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6">
            <div class="feature-card" style="pointer-events:none">
                <div style="font-size:10px; color:var(--accent); margin-bottom:.75rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600">
                    <i class="bi bi-activity me-1"></i>Aktivitas Terkini
                </div>
                <?php foreach (array_slice(array_reverse($semua), 0, 4) as $p): ?>
                <div class="activity-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong style="font-size:var(--label)"><?= $p['id'] ?></strong>
                        <span style="font-size:var(--caption); opacity:.6; margin-left:.5rem"><?= $p['tipe_event'] ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:var(--caption); opacity:.4"><?= $p['created_at'] ?></span>
                        <?= statusBadge($p['status']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($semua)): ?>
                <div style="text-align:center; opacity:.4; padding:1rem; font-size:var(--caption)">Belum ada pengajuan.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mt-3 text-center" style="font-size:var(--caption); opacity:.35">
        Mall ERP · SISFO MALL · 2026
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
