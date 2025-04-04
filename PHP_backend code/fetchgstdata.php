<?php
// // $gstin = $_GET['gstin'];
// // $abid = $_GET['abid'];

// $gstin = "33AADCS4885K2ZX";
// // Static parameters
// $url = "https://apisandbox.whitebooks.in/einvoice/type/GSTNDETAILS/version/V1_03";
// $headers = [
//     "accept" => "*/*",
//     "ip_address" => "43",  // Replace with actual IP address if necessary
//     "client_id" => "EINS83b22c67-08b6-4277-996d-2365adafdaf6",
//     "client_secret" => "EINSc497f087-35c3-4f14-9788-27bab1dfcf9d",
//     "username" => "BVMGSP",
//     "auth-token" => "1l64sOUOhCReXvfTQLBELm7uw",
//     "gstin" => "29AAGCB1286Q000"  // GSTIN
// ];

// // Parameters
// $params = [
//     "param1" => $gstin,
//     "email" => "nithish.kumar99@gmail.com"
// ];

// // Send the GET request
// $ch = curl_init($url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

// $response = curl_exec($ch);
// curl_close($ch);

// // Output response
// echo json_encode(json_decode($response, true));




// Define the URL and parameters
$url = "https://apisandbox.whitebooks.in/einvoice/type/GSTNDETAILS/version/V1_03";
$params = [
    'param1' => '33AADCS4885K2ZX',  // GSTIN or some other parameter
    'email' => 'nithish.kumar99@gmail.com'  // The email parameter
];

// Create query string from params
$queryString = http_build_query($params);

// Define the headers
$headers = [
    "accept: */*",
    "ip_address: 43",  // Replace with actual IP address if necessary
    "client_id: EINS83b22c67-08b6-4277-996d-2365adafdaf6",
    "client_secret" => "EINSc497f087-35c3-4f14-9788-27bab1dfcf9d",
    "username" => "BVMGSP",
    "auth-token" => "1l64sOUOhCReXvfTQLBELm7uw",
    "gstin" => "29AAGCB1286Q000"  // GSTIN
];

// Path to the SSL certificate (replace with your actual certificate path)
$sslCertPath = "C:/xampp/htdocs/example/gofin/whitebooks.crt";  // Path to your .crt file

// Initialize cURL session
$ch = curl_init();

// Set the URL with query parameters
curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryString);

// Set the request headers
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Return the response as a string instead of outputting it
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Set SSL certificate for verification
curl_setopt($ch, CURLOPT_CAINFO, $sslCertPath);  // Point to your SSL certificate file

// Execute the cURL session
$response = curl_exec($ch);

// Check for errors in cURL request
if(curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    exit();
}

// Close the cURL session
curl_close($ch);

// Decode the JSON response
$responseData = json_decode($response, true);

// Check if the status_code in the response is "1"
if (isset($responseData['status_code']) && $responseData['status_code'] == "1") {
    echo "Response received successfully!\n";
    print_r($responseData);  // Display the response
} else {
    echo "Request failed with status code: " . (isset($responseData['status_code']) ? $responseData['status_code'] : 'N/A') . "\n";
    echo "Error Message: " . $response . "\n";  // Show the full response text in case of error
}



?>
