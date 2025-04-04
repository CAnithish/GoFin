<?php

if (isset($_COOKIE['userdetails'])) {
    $userdetails = json_decode($_COOKIE['userdetails'], true);

    $abid = isset($userdetails['abid']) ? $userdetails['abid'] : "Key not found";
    if ($abid === "Key not found") {
        header("Location: http://localhost/example/ablogin.html");
    }
} else {
    header("Location: http://localhost/example/ablogin.html");
}

// Check if required parameters are provided
if (!isset($_GET['irn'])) {
    http_response_code(400); // Bad request
    echo json_encode(["error" => "Missing 'irn' parameter."]);
    exit;
}

// Extract parameters from the GET request
// $abid = $_GET['abid'];
$irn = $_GET['irn'];


function base64url_decode($encoded_string)
{
    // Replace Base64Url characters with standard Base64 characters
    $base64_string = str_replace(['-', '_'], ['+', '/'], $encoded_string);

    // Pad the string with '=' to make its length a multiple of 4
    $padded_string = $base64_string . str_repeat('=', 4 - (strlen($base64_string) % 4));

    return base64_decode($padded_string);
}

function process_json_data($json_data)
{
    $decoded_data_list = [];

    foreach ($json_data as $item) {
        try {
            $encoded_string = explode('.', $item['SignedInvoice'])[1];
            $decoded_string = base64url_decode($encoded_string);
            $cleandata = json_decode(json_decode($decoded_string, true)['data'], true);
            $cleandata['Status'] = $item['Status'];
            $decoded_data_list[] = $cleandata;
        } catch (Exception $e) {
            // Log or handle the error as needed
            continue;
        }
    }

    return $decoded_data_list;
}
#--------------------------------------

// Define the API URL and parameters
$url = "http://127.0.0.1:8000/einvoice";
$params = http_build_query(["abid" => $abid, "irn" => $irn]);

// Initialize cURL
$curl = curl_init();

// Set cURL options
curl_setopt_array($curl, [
    CURLOPT_URL => $url . "?" . $params, // Append parameters to the URL
    CURLOPT_RETURNTRANSFER => true,      // Return the response as a string
    CURLOPT_TIMEOUT => 10,               // Set a timeout for the request
    CURLOPT_HTTPGET => true,             // HTTP GET method
]);

// Execute the cURL request
$response = curl_exec($curl);

// Check for cURL errors
if (curl_errno($curl)) {
    http_response_code(500); // Internal server error
    echo json_encode(["error" => curl_error($curl)]);
    curl_close($curl);
    exit;
}

// Close the cURL session
curl_close($curl);

// Output the response from the API
header('Content-Type: application/json');
// echo $response;

// Parse JSON response
$json_data = json_decode($response, true);
$decoded_data_list = process_json_data($json_data);
$final_data = $decoded_data_list[0];
echo json_encode($final_data);

?>
