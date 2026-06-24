<?php
require_once '../../config/konek.php';

function getAreas() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT ea.*, f.floor_number, b.name AS building_name
        FROM 04_event_areas ea
        LEFT JOIN 01_floors f ON ea.floor_id = f.id_floors
        LEFT JOIN 01_buildings b ON f.building_id = b.id_buildings
        WHERE ea.status = 'aktif'
        ORDER BY ea.nama_area
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAreaById($id) {
    global $conn;
    $id = (int)$id;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_areas WHERE id_area = $id");
    return mysqli_fetch_assoc($result);
}

function getBookings($filter_status = null) {
    global $conn;
    $where = $filter_status ? "WHERE b.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'" : "";
    $result = mysqli_query($conn, "
        SELECT b.*, a.nama_area, a.kapasitas,
               u.full_name AS nama_pemohon
        FROM 04_event_booking b
        LEFT JOIN 04_event_areas a ON b.id_area = a.id_area
        LEFT JOIN 09_users u ON b.id_user = u.id
        $where
        ORDER BY b.id_booking DESC
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getBookingById($id) {
    global $conn;
    $id = (int)$id;
    $result = mysqli_query($conn, "
        SELECT b.*, a.nama_area, a.kapasitas, a.fasilitas,
               u.full_name AS nama_pemohon, u.email AS email_pemohon
        FROM 04_event_booking b
        LEFT JOIN 04_event_areas a ON b.id_area = a.id_area
        LEFT JOIN 09_users u ON b.id_user = u.id
        WHERE b.id_booking = $id
    ");
    return mysqli_fetch_assoc($result);
}

function addBooking($data) {
    global $conn;
    $id_area   = (int)$data['id_area'];
    $id_user   = (int)($data['id_user'] ?? 1);
    $nama      = mysqli_real_escape_string($conn, $data['nama_event']);
    $tipe      = mysqli_real_escape_string($conn, $data['tipe_event']);
    $mulai     = mysqli_real_escape_string($conn, $data['tanggal_mulai']);
    $selesai   = mysqli_real_escape_string($conn, $data['tanggal_selesai']);
    $estimasi  = (int)$data['estimasi_pengunjung'];

    $sql = "INSERT INTO 04_event_booking
            (id_area, id_user, nama_event, tipe_event, tanggal_mulai, tanggal_selesai, estimasi_pengunjung, status)
            VALUES ($id_area, $id_user, '$nama', '$tipe', '$mulai', '$selesai', $estimasi, 'pending')";
    mysqli_query($conn, $sql);
    return mysqli_insert_id($conn);
}

function updateBookingStatus($id, $status, $catatan = '') {
    global $conn;
    $id     = (int)$id;
    $status = mysqli_real_escape_string($conn, $status);
    $cat    = mysqli_real_escape_string($conn, $catatan);
    mysqli_query($conn, "UPDATE 04_event_booking SET status='$status', catatan_admin='$cat' WHERE id_booking=$id");
}

function deleteBooking($id) {
    global $conn;
    $id = (int)$id;
    mysqli_query($conn, "DELETE FROM 04_event_booking WHERE id_booking=$id");
}

function checkConflict($id_area, $tanggal_mulai, $tanggal_selesai, $exclude_id = null) {
    global $conn;
    $id_area  = (int)$id_area;
    $mulai    = mysqli_real_escape_string($conn, $tanggal_mulai);
    $selesai  = mysqli_real_escape_string($conn, $tanggal_selesai);
    $excl     = $exclude_id ? "AND id_booking != " . (int)$exclude_id : "";
    $result   = mysqli_query($conn, "
        SELECT * FROM 04_event_booking
        WHERE id_area = $id_area
          AND status NOT IN ('rejected')
          AND tanggal_mulai <= '$selesai'
          AND tanggal_selesai >= '$mulai'
          $excl
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getVendorsByBooking($id_booking) {
    global $conn;
    $id = (int)$id_booking;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_booking_vendor WHERE id_booking=$id");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAllVendors() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT v.*, b.nama_event
        FROM 04_event_booking_vendor v
        LEFT JOIN 04_event_booking b ON v.id_booking = b.id_booking
        ORDER BY v.id DESC
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function addVendor($id_booking, $nama, $kategori, $kontak) {
    global $conn;
    $id_booking = (int)$id_booking;
    $nama       = mysqli_real_escape_string($conn, $nama);
    $kategori   = mysqli_real_escape_string($conn, $kategori);
    $kontak     = mysqli_real_escape_string($conn, $kontak);
    mysqli_query($conn, "INSERT INTO 04_event_booking_vendor (id_booking, nama_vendor, kategori, kontak)
                         VALUES ($id_booking, '$nama', '$kategori', '$kontak')");
}

function deleteVendor($id) {
    global $conn;
    mysqli_query($conn, "DELETE FROM 04_event_booking_vendor WHERE id=" . (int)$id);
}

function getTiketByBooking($id_booking) {
    global $conn;
    $id = (int)$id_booking;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_tiket WHERE id_booking=$id ORDER BY id_tiket");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAllTiket() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT t.*, b.nama_event, b.tipe_event
        FROM 04_event_tiket t
        LEFT JOIN 04_event_booking b ON t.id_booking = b.id_booking
        ORDER BY t.id_tiket
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function addTiket($id_booking, $tipe, $kuota, $harga) {
    global $conn;
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM 04_event_tiket");
    $cnt = mysqli_fetch_assoc($res)['c'] + 1;
    $id_tiket   = 'TKT-' . str_pad($cnt, 3, '0', STR_PAD_LEFT);
    $id_booking = (int)$id_booking;
    $tipe       = mysqli_real_escape_string($conn, $tipe);
    $kuota      = (int)$kuota;
    $harga      = (float)$harga;
    mysqli_query($conn, "INSERT INTO 04_event_tiket (id_tiket, id_booking, tipe, kuota, terjual, harga, pendapatan)
                         VALUES ('$id_tiket', $id_booking, '$tipe', $kuota, 0, $harga, 0)");
}

function deleteTiket($id_tiket) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id_tiket);
    mysqli_query($conn, "DELETE FROM 04_event_tiket WHERE id_tiket='$id'");
}

function getSponsorByBooking($id_booking) {
    global $conn;
    $id = (int)$id_booking;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_sponsorship WHERE id_booking=$id");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAllSponsors() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT s.*, b.nama_event
        FROM 04_event_sponsorship s
        LEFT JOIN 04_event_booking b ON s.id_booking = b.id_booking
        ORDER BY s.id_sponsor
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function addSponsor($id_booking, $sponsor, $paket, $nilai) {
    global $conn;
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM 04_event_sponsorship");
    $cnt = mysqli_fetch_assoc($res)['c'] + 1;
    $id_sponsor = 'SPO-' . str_pad($cnt, 3, '0', STR_PAD_LEFT);
    $id_booking = (int)$id_booking;
    $sponsor    = mysqli_real_escape_string($conn, $sponsor);
    $paket      = mysqli_real_escape_string($conn, $paket);
    $nilai      = (float)str_replace(['.', ',', ' '], '', $nilai);
    mysqli_query($conn, "INSERT INTO 04_event_sponsorship (id_sponsor, id_booking, sponsor, paket, nilai, status_bayar)
                         VALUES ('$id_sponsor', $id_booking, '$sponsor', '$paket', $nilai, 'belum')");
}

function settleSponsor($id_sponsor) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id_sponsor);
    mysqli_query($conn, "UPDATE 04_event_sponsorship SET status_bayar='lunas' WHERE id_sponsor='$id'");
}

function deleteSponsor($id_sponsor) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id_sponsor);
    mysqli_query($conn, "DELETE FROM 04_event_sponsorship WHERE id_sponsor='$id'");
}

function getAnalytics() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT an.*, b.nama_event, b.tipe_event, b.tanggal_mulai, b.tanggal_selesai,
               a.nama_area
        FROM 04_event_analytics an
        LEFT JOIN 04_event_booking b ON an.id_booking = b.id_booking
        LEFT JOIN 04_event_areas a ON b.id_area = a.id_area
        ORDER BY an.id DESC
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function statusBadge($status) {
    $map = [
        'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'revision' => '<span class="badge bg-info text-dark">Revisi</span>',
    ];
    return $map[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function payBadge($status) {
    return $status === 'lunas'
        ? '<span class="badge bg-success">Lunas</span>'
        : '<span class="badge bg-danger">Belum</span>';
}