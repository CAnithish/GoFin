<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Read the raw POST data from JavaScript
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['abid']) || empty($data['abid']) || !isset($data['Barcde']) || empty($data['Barcde'])) {
    echo json_encode(["error" => "Missing abid or barcode"]);
    exit;
}

$payload = json_encode(["abid" => (int)$data['abid'], "Barcde" => $data['Barcde']]);
$api_url = "http://127.0.0.1:8000/invmaster_v2_item_barcode";

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => "cURL error: " . curl_error($ch)]);
    exit;
}

curl_close($ch);

// Decode and return the response
$data = json_decode($response, true);
echo json_encode($data ?: ["error" => "Invalid JSON response"]);
?>
