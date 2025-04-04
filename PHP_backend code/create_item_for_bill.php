<?php
// Set the API endpoint URL
$api_url = "http://127.0.0.1:8000/invmaster"; // Replace with your actual API URL

// Get the JSON data from the request
$json_data = file_get_contents('php://input');

// Decode the JSON data
$data = json_decode($json_data, true);

// Check if decoding was successful
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo "Error: Invalid JSON data received.";
    exit;
}

// Initialize cURL session
$ch = curl_init($api_url);

// Configure cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));

// Execute the cURL request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    http_response_code(500);
    echo "Error: cURL error - " . curl_error($ch);
    curl_close($ch);
    exit;
}

// Get HTTP status code
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL session
curl_close($ch);

// Set HTTP response code based on the API response
http_response_code($http_status);

// Echo the API response
echo $response;
?>
