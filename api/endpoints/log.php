<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$out = new stdClass();
$out->success = false;

if (!isset($_POST['content'])) {
    $out->error = "Required POST argument 'content' not found.";
    echo json_encode($out);
    exit;
}

if (empty($_POST['content'])) {
    $out->error = "Required POST argument 'content' is empty.";
    echo json_encode($out);
    exit;
}

$content = $_POST['content'];

// Get expiration options
$noResetTimer = isset($_POST['no_reset_timer']) && $_POST['no_reset_timer'] === '1';
$expiryDays = isset($_POST['expiry_days']) ? intval($_POST['expiry_days']) : null;

// Validate expiry_days doesn't exceed STORAGE_TIME
if ($expiryDays !== null && $expiryDays > 0) {
    $storageConfig = Config::Get('storage');
    $maxDays = floor($storageConfig['storageTime'] / 86400);
    if ($expiryDays > $maxDays) {
        $out->error = "Expiry days cannot exceed $maxDays days";
        echo json_encode($out);
        exit;
    }
}

$log = new Log();
$id = $log->put($content, $noResetTimer, $expiryDays);

$urls = Config::Get('urls');

$out->success = true;
$out->id = $id->get();
$out->url = $urls['baseUrl'] . "/" . $out->id;
$out->raw = $urls['apiBaseUrl'] . "/1/raw/" . $out->id;

echo json_encode($out);
