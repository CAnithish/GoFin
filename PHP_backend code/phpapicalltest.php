<?php
// // API endpoint
// $url = "http://localhost:8000/create_journal_einv"; // Replace with your actual server URL

// // Payload for the journal entry
// $payload = [
//     "journal_description" => "December journal entry",
//     "abid" => 1000000000,
//     "cptyid" => 100000000002,
//     "transaction_type" => "bill",
//     "cpty_gstin" => "33AFMPB5184R1ZX",
//     "cptyname" => "Neethu Timber and Saw Mills",
//     "ref_no" => "1367/24-25",
//     "irn" => "927cbccc17453dba76ce819d804c727d29266c5dfb936f45171a1596bba07218",
//     "doc_type" => "INV",
//     "version" => "1.1",
//     "pan" => "AFMPB5184R",
//     "lines" => [
//         [
//             "gl_id" => 100000000000,
//             "amount" => 10000.00,
//             "profit_center" => 101,
//             "cost_center" => 201,
//             "projectid" => 301,
//             "project_name" => "Project Sid",
//             "journalline_tag1" => "Terrace",
//             "journalline_tag2" => "Year End",
//             "journalline_tag3" => "2024",
//             "description" => "purchase of materials......."
//         ],
//         [
//             "gl_id" => 100000000001,
//             "amount" => 900.00,
//             "profit_center" => 101,
//             "cost_center" => 201,
//             "projectid" => 301,
//             "project_name" => "Project Sid",
//             "journalline_tag1" => "Terrace",
//             "journalline_tag2" => "Year End",
//             "journalline_tag3" => "2024",
//             "description" => "purchase of materials......."
//         ],
//         [
//             "gl_id" => 100000000002,
//             "amount" => 900.00,
//             "profit_center" => 101,
//             "cost_center" => 201,
//             "projectid" => 301,
//             "project_name" => "Project Sid",
//             "journalline_tag1" => "Terrace",
//             "journalline_tag2" => "Year End",
//             "journalline_tag3" => "2024",
//             "description" => "purchase of materials......."
//         ],
//         [
//             "gl_id" => 100000000005,
//             "amount" => -11800.00,
//             "profit_center" => 101,
//             "cost_center" => 201,
//             "projectid" => 301,
//             "project_name" => "Project Sid",
//             "journalline_tag1" => "Terrace",
//             "journalline_tag2" => "Year End",
//             "journalline_tag3" => "2024",
//             "description" => "purchase of materials......."
//         ]
//     ]
// ];

// // Initialize cURL
// $ch = curl_init($url);

// // Convert payload to JSON
// $jsonPayload = json_encode($payload);

// // Set cURL options
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     "Content-Type: application/json",
//     "Content-Length: " . strlen($jsonPayload)
// ]);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

// // Execute the request
// $response = curl_exec($ch);

// // Check for errors
// if (curl_errno($ch)) {
//     echo "cURL error: " . curl_error($ch);
// } else {
//     // Parse the response
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     if ($httpCode == 200) {
//         echo "Response:\n";
//         echo $response; // Display the response from the API
//     } else {
//         echo "Request failed with HTTP code: $httpCode\n";
//         echo "Response:\n$response";
//     }
// }

// // Close the cURL session
// curl_close($ch);

# #=================================================================================================================================================

// // The endpoint and parameters
// $url = "http://127.0.0.1:8000/journal_header"; // Replace with your actual endpoint
// $journal_id = 100000000098; // Replace with the actual journal_id you want to query
// $abid = 1000000000; // Replace with the actual abid value

// // Build the query string for GET parameters
// $params = [
//     "abid" => $abid,
//     "journal_id" => $journal_id
// ];
// $queryString = http_build_query($params);

// // Append query string to the URL
// $urlWithParams = $url . "?" . $queryString;

// // Initialize cURL
// $ch = curl_init($urlWithParams);

// // Set cURL options
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // To return the response as a string
// curl_setopt($ch, CURLOPT_HTTPGET, true); // Specify GET request

// // Execute the GET request
// $response = curl_exec($ch);

// // Check for errors
// if (curl_errno($ch)) {
//     echo "cURL error: " . curl_error($ch);
// } else {
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     if ($httpCode == 200) {
//         // Parse and display the response
//         $journalHeaders = json_decode($response, true); // Decode JSON response into an associative array
//         echo "Journal Headers:\n";
//         print_r($journalHeaders);
//     } else {
//         echo "Request failed with HTTP code: $httpCode\n";
//         echo "Response:\n$response";
//     }
// }

// // Close cURL session
// curl_close($ch);
# #=================================================================================================================================================

// $abid = 1000000000;  // Replace with the desired item ID

// // Initialize cURL
// $ch = curl_init();

// // Set the URL for the GET request
// curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/glmaster?abid={$abid}");
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string

// // Execute the GET request
// $response = curl_exec($ch);

// // Check for cURL errors
// if (curl_errno($ch)) {
//     echo 'cURL Error: ' . curl_error($ch);
// }

// // Get HTTP response code
// $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// // Close cURL session
// curl_close($ch);

// // Handle the response based on the HTTP status code
// if ($http_code == 200) {
//     // Decode JSON response and print it
//     $data = json_decode($response, true);
//     echo json_encode($data, JSON_PRETTY_PRINT); // Print the response data as JSON
// } elseif ($http_code == 404) {
//     echo "NO records found"; // Print message if no records found
// } else {
//     echo "Unexpected error: HTTP Status Code {$http_code}\n";
// }
# #=================================================================================================================================================


// Define the API URL
$apiUrl = "http://localhost:8000/einvoice";

// Optional query parameters
$abid = 1000000000; // Replace with your actual `abid` value
$irn = "87e3fc73dbe528439a53dc920c6e911f4f48dcbeea6cddd9b26ca175c13dedc7"; // Replace with your actual `Irn` value

// Build the query string if parameters are provided
$queryParams = http_build_query([
    'abid' => $abid,
    'irn' => $irn,
]);

// Final API URL with query parameters
$fullUrl = $apiUrl . '?' . $queryParams;

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
]);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    curl_close($ch);
    exit;
}

// Close the cURL session
curl_close($ch);

// Decode the JSON response
$responseData = json_decode($response, true);

// Check for valid response and handle data
if (isset($responseData) && is_array($responseData)) {
    echo "E-invoices retrieved successfully:\n";
    print_r($responseData);
} else {
    echo "Error or no data returned from the API:\n";
    echo $response;
}

# #=================================================================================================================================================

// $abid = 1000000000;  // Replace with the desired item ID


// // Define the API endpoint
// $url = 'http://127.0.0.1:8000/glmaster'; // Replace with your actual API URL

// // Prepare the data to be sent in JSON format
// $data = [
//     'gl_name' => 'Sample GL',
//     'gl_nature' => 'php sampleNature',
//     'gl_grouping' => 'php Grouping sample',
//     'description' => 'php Sample description',
//     'abid' => 1000000000,
//     'type' => 'custom'
// ];

// // Initialize cURL session
// $ch = curl_init($url);

// // Set cURL options
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     'Content-Type: application/json',
// ]);

// // Execute the POST request
// $response = curl_exec($ch);

// // Check for errors
// if (curl_errno($ch)) {
//     echo 'cURL Error: ' . curl_error($ch);
// } else {
//     // Decode the JSON response
//     $responseData = json_decode($response, true);
    
//     // Check HTTP status code
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
//     if ($httpCode === 200) {
//         echo "Success: ";
//         print_r($responseData);
//     } elseif ($httpCode === 400) {
//         echo "Error (Duplication): ";
//         print_r($responseData);
//     } else {
//         echo "Error: HTTP Status Code $httpCode";
//         print_r($responseData);
//     }
// }

// // Close cURL session
// curl_close($ch);

?>
