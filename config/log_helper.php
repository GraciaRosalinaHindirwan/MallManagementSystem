<?php

function simpanLog($username, $aktivitas)
{
    $file = __DIR__ . '/audit_log.json';

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }

    $logs = json_decode(file_get_contents($file), true);

    if (!$logs) {
        $logs = [];
    }

    $logs[] = [
        'username' => $username,
        'aktivitas' => $aktivitas,
        'tanggal' => date('Y-m-d H:i:s')
    ];

    // simpan hanya 30 hari terakhir
    $batasBackend = strtotime('-30 days');

    $logs = array_filter($logs, function ($log) use ($batasBackend) {
        return strtotime($log['tanggal']) >= $batasBackend;
    });

    file_put_contents(
        $file,
        json_encode(array_values($logs), JSON_PRETTY_PRINT)
    );
}