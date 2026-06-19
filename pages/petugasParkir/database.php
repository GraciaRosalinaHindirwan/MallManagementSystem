<?php
session_start();

// Inisialisasi database di dalam session jika belum ada
if (!isset($_SESSION['parking'])) {
    $_SESSION['parking'] = [
        'occupied' => 0,
        'vehicles' => [],
        'members' => [],
        'subscriptions' => [],
        'transactions' => [],
        'stats' => [
            'entry' => 0,
            'exit' => 0,
            'revenue' => 0,
            'durations' => [],
        ],
        'last_ticket' => null,
        'last_receipt' => null,
    ];
}
?>