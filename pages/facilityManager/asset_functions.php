<?php
// Fungsi helper untuk Asset Management

function calculateDepreciation($purchaseValue, $purchaseDate, $usefulLifeYears) {
    $purchase = new DateTime($purchaseDate);
    $today = new DateTime();
    $ageYears = $today->diff($purchase)->days / 365.25;
    $annualDep = $purchaseValue / $usefulLifeYears;
    $accumulated = min($purchaseValue, $annualDep * $ageYears);
    $bookValue = max(0, $purchaseValue - $accumulated);
    return [
        'bookValue' => round($bookValue),
        'annualDep' => round($annualDep),
        'remainingYears' => max(0, $usefulLifeYears - $ageYears)
    ];
}

function generateAssetCode() {
    return 'AST' . date('Ymd') . rand(1000, 9999);
}
?>