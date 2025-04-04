<?php

// Set the content type to application/json for the response
header('Content-Type: application/json');

// Get the raw PUT data
$rawData = file_get_contents("php://input");

// Decode the JSON data into a PHP array
$data = json_decode($rawData, true);

// Define the endpoint URL
$url = "http://127.0.0.1:8000/edit_journal_bank";

// Encode the data to JSON format
$jsonData = json_encode($data);

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options for a PUT request
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Specify PUT method
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

// Execute the PUT request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo json_encode([
        'status_code' => 500,
        'message' => 'cURL Error: ' . curl_error($ch)
    ]);
} else {
    $responseData = json_decode($response, true);
    
    if (isset($responseData['status_code'])) {
        echo json_encode($responseData); // Return the original response from the second endpoint
    } else {
        echo json_encode([
            'status_code' => 500,
            'message' => 'Unexpected response format.'
        ]);
    }
}

// Close cURL session
curl_close($ch);

?>
