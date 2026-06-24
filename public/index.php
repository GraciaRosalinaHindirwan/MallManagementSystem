<?php
/**
 * Mall ERP — Parking (M04) — Router utama
 *
 * ?role=petugas|pengunjung|admin|manajer  → tampilkan view
 * ?action=...                              → AJAX JSON response
 */

require_once __DIR__ . '/parking.php';

/* ── JSON helper ─────────────────────────────────────────────────────────── */
function respondJson(array $data): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── POST body helper ────────────────────────────────────────────────────── */
function getBody(): array {
    $body = $_POST;
    $ct   = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ct,'application/json')) {
        $json = json_decode(file_get_contents('php://input'), true);
        if (is_array($json)) $body = array_merge($body, $json);
    }
    return $body;
}

/* ── AJAX handler ────────────────────────────────────────────────────────── */
$action = $_GET['action'] ?? null;

if ($action !== null) {
    $b = getBody();

    switch ($action) {

        case 'state':
            respondJson(['success'=>true, 'state'=>getParkingState()]);

        case 'entry':
            respondJson(processEntry(
                plate:         $b['plate']          ?? '',
                tipeUser:      $b['tipe_user']      ?? 'umum',
                tipeKendaraan: $b['tipe_kendaraan'] ?? 'mobil',
                zonaId:        (int)($b['zona_id']  ?? 1),
                parkingSlot:   $b['parking_slot']   ?? '',
                idMember:      isset($b['id_member']) && $b['id_member'] !== ''
                                 ? (int)$b['id_member'] : null
            ));

        case 'exit':
            respondJson(processExit(
                plate:         $b['plate']          ?? '',
                paymentMethod: $b['payment_method'] ?? 'cash'
            ));

        case 'member_add':
            respondJson(addMember(
                platNomor:      $b['plat_nomor']      ?? ($b['plate'] ?? ''),
                tipeKendaraan:  $b['tipe_kendaraan']  ?? 'mobil',
                membershipType: $b['membership_type'] ?? 'Reguler',
                tenantId:       isset($b['tenant_id']) && $b['tenant_id'] !== ''
                                  ? (int)$b['tenant_id'] : null
            ));

        case 'member_delete':
            respondJson(deleteMember($b['plat_nomor'] ?? ($b['plate'] ?? '')));

        case 'zona_add':
            respondJson(addZona(
                namaZona:  $b['name']   ?? ($b['nama_zona'] ?? ''),
                totalSlot: (int)($b['slots'] ?? ($b['total_slot'] ?? 0)),
                floorId:   isset($b['floor_id']) && $b['floor_id'] !== '' ? (int)$b['floor_id'] : null
            ));

        case 'zona_delete':
            respondJson(deleteZona((int)($b['zona_id'] ?? 0)));

        case 'tarif_add':
            respondJson(addTarif(
                tipeKendaraan:   $b['tipe_kendaraan']    ?? 'mobil',
                tipeUser:        $b['tipe_user']         ?? 'umum',
                tarifJ1:         (float)($b['tarif_jam_pertama'] ?? 0),
                tarifPjm:        (float)($b['tarif_per_jam']     ?? 0),
                tarifMaks:       isset($b['tarif_harian_max']) && $b['tarif_harian_max'] !== '' ? (float)$b['tarif_harian_max'] : null,
                berlakuDari:     $b['berlaku_dari']     ?? date('Y-m-d'),
                berlakuSampai:   isset($b['berlaku_sampai']) && $b['berlaku_sampai'] !== '' ? $b['berlaku_sampai'] : null
            ));

        case 'tarif_delete':
            respondJson(deleteTarif((int)($b['id_tarif'] ?? 0)));

        case 'stats_reset':
            respondJson(resetStats());

        case 'transactions_clear':
            respondJson(clearTransactions());

        default:
            respondJson(['success'=>false, 'message'=>'Aksi tidak dikenal: '.$action]);
    }
}

/* ── Route ke view ───────────────────────────────────────────────────────── */
$allowed = ['petugas','pengunjung','admin','manajer'];
$role    = in_array($_GET['role'] ?? '', $allowed, true) ? $_GET['role'] : 'petugas';
$state   = getParkingState();

$viewFile = __DIR__ . "/views/{$role}.php";
if (!file_exists($viewFile)) { http_response_code(404); die("View tidak ditemukan."); }

require_once $viewFile;
