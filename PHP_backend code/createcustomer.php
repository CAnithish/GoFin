<?php
// Get the abid from the query parameters
$abid = $_GET['abid'];

// Get the raw POST data from the request body
$customer = json_decode(file_get_contents('php://input'), true);

// Add the abid to the customer data array (if not already included)
$customer['abid'] = $abid;

// Prepare the payload to send to FastAPI
$payload = json_encode($customer);

// Send request to FastAPI endpoint
$url = 'http://127.0.0.1:8000/customerlist';  // FastAPI endpoint URL
$ch = curl_init($url);

// Set CURL options for a POST request
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Execute the CURL request
$response = curl_exec($ch);
curl_close($ch);

// Output response to the client (frontend)
echo $response;
?>
