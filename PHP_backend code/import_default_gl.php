<?php

// Fetch user details from cookies
if (isset($_COOKIE['userdetails'])) {
    $userdetails = json_decode($_COOKIE['userdetails'], true);
    $abid = isset($userdetails['abid']) ? $userdetails['abid'] : "Key not found";
    if ($abid === "Key not found") {
        header("Location: http://localhost/example/ablogin.html");
        exit();
    }
} else {
    header("Location: http://localhost/example/ablogin.html");
    exit();
}

header('Content-Type: application/json');


// Get the POST body (default_gl_ids array)
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['default_gl_ids']) || !is_array($data['default_gl_ids'])) {
    echo json_encode(['message' => 'Invalid input.']);
    http_response_code(400);
    exit;
}

// Prepare data for the API request
$defaultGlIds = $data['default_gl_ids'];
$abid = $abid; // Replace with actual ABID value

$payload = json_encode([
    'abid' => $abid,
    'default_gl_ids' => $defaultGlIds,
]);

// Make cURL POST request to import endpoint
$ch = curl_init("http://localhost:8000/import-gl-master");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode(['message' => 'cURL Error: ' . curl_error($ch)]);
    http_response_code(500);
    curl_close($ch);
    exit;
}

// Get HTTP response code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Handle response
if ($http_code == 200) {
    echo json_encode(['message' => 'Data imported successfully.']);
} else {
    echo json_encode(['message' => 'Error importing data. HTTP Status Code: ' . $http_code]);
    http_response_code($http_code);
}
?>
