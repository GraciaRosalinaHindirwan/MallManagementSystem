<?php
// asset_process.php
$root = dirname(__DIR__, 2); // naik ke root proyek
require_once '../MallManagementSystem/config/konek.php';
require_once 'asset_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: asset-management.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_asset') {
    $asset_code = generateAssetCode();
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $value = (float) $_POST['value'];
    $purchaseDate = $_POST['purchaseDate'];
    $usefulLife = (int) $_POST['usefulLife'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $isVital = isset($_POST['isVital']) ? 1 : 0;

    $sql = "INSERT INTO 03_assets (asset_code, name, category, purchase_value, purchase_date, useful_life, current_location, is_vital, status) 
            VALUES ('$asset_code', '$name', '$category', $value, '$purchaseDate', $usefulLife, '$location', $isVital, 'active')";
    if (mysqli_query($conn, $sql)) {
        header('Location: asset-management.php?success=add');
    } else {
        header('Location: asset-management.php?error=add');
    }
    exit;
}

if ($action === 'mutation') {
    $asset_id = (int) $_POST['asset_id'];
    $newLocation = mysqli_real_escape_string($conn, $_POST['new_location']);

    $result = mysqli_query($conn, "SELECT current_location, name FROM 03_assets WHERE asset_id = $asset_id");
    $asset = mysqli_fetch_assoc($result);
    if ($asset) {
        $oldLoc = $asset['current_location'];
        mysqli_query($conn, "UPDATE 03_assets SET current_location = '$newLocation', last_mutation_date = NOW() WHERE asset_id = $asset_id");
        mysqli_query($conn, "INSERT INTO 03_asset_mutations (asset_id, old_location, new_location, mutation_date) VALUES ($asset_id, '$oldLoc', '$newLocation', NOW())");
        header('Location: asset-management.php?success=mutation');
    } else {
        header('Location: asset-management.php?error=mutation');
    }
    exit;
}

header('Location: asset-management.php');
exit;
?>