<?php
// updateVendor.php

// Read the input data from the request
$input = file_get_contents('php://input');



// $input = json_encode([
//     "abid" => 1000000000,
//     "addr1" => null,
//     "addr2" => null,
//     "addr3" => null,
//     "bankac" => null,
//     "defaultbalancegl" => 100000000005,
//     "defaulttradegl" => 100000000000,
//     "email" => null,
//     "gstin" => "33AAEPG7063P1ZG",
//     "ifsc" => null,
//     "is_trans_similar" => 'y',
//     "lglNm" => "G.J.ENTERPRISES",
//     "loc" => null,
//     "lowertdscertno" => null,
//     "lowertdsrate" => null,
//     "mobile" => null,
//     "pan" => null,
//     "phone" => null,
//     "pin" => null,
//     "stcd" => null,
//     "tdsrate" => null,
//     "tdssection" => null,
//     "vendorid" => 100000000000,
//     "updateBalanceCheckbox" =>"on",
//     "updateTransactionCheckbox" =>"on",
//     "olddefualtbalancegl" => 100000000007,
//     "olddefaulttradegl" => 100000000006,
// ]);


// Define the FastAPI endpoint
$apiUrl = 'http://127.0.0.1:8000/update-vendor';

// Initialize a cURL session
$ch = curl_init($apiUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Use PUT method
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($input)
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input); // Forward the input data

// Execute the request and get the response
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $error]);
    exit;
}

// Close the cURL session
curl_close($ch);

// Forward the response back to the client
http_response_code($httpCode);
// echo $response;
// echo $httpCode;

$input_decoded = json_decode($input, true);

// Check if the response is successful (HTTP code 200)
if ($httpCode == 200) {
    

    // Prepare data for updating GL IDs based on checkboxes
    if (isset($input_decoded['updateTransactionCheckbox']) && $input_decoded['updateTransactionCheckbox'] === 'on') {
        $transactionUpdateData = [
            'abid' => $input_decoded['abid'], // Use abid from input
            'old_gl_id' => $input_decoded['olddefaulttradegl'], // Original GL ID to match
            'vendorid' => $input_decoded['vendorid'], // Vendor ID for filtering
            'new_gl_id' => $input_decoded['defaulttradegl'] // New GL ID to set
        ];

        // Define the endpoint for updating GL ID for transaction category
        $transactionApiUrl = 'http://127.0.0.1:8000/update_gl_in_journal';

        // Initialize a cURL session for transaction update
        $chTransaction = curl_init($transactionApiUrl);
        curl_setopt($chTransaction, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($chTransaction, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chTransaction, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($transactionUpdateData))
        ]);
        curl_setopt($chTransaction, CURLOPT_POSTFIELDS, json_encode($transactionUpdateData));

        // Execute the request and get the response for transaction update
        $transactionResponse = curl_exec($chTransaction);
        $transactionHttpCode = curl_getinfo($chTransaction, CURLINFO_HTTP_CODE);

        // if ($transactionHttpCode == 200) {
        //     echo json_encode(['message' => 'Transaction category updated successfully!']);
        // } else {
        //     echo json_encode(['error' => 'Failed to update transaction category.', 'details' => $transactionResponse]);
        // }

        curl_close($chTransaction); // Close transaction cURL session
    }

    // Check for balance update checkbox
    if (isset($input_decoded['updateBalanceCheckbox']) && $input_decoded['updateBalanceCheckbox'] === 'on') {
        $balanceUpdateData = [
            'abid' => $input_decoded['abid'], // Use abid from input
            'old_gl_id' => $input_decoded['olddefualtbalancegl'], // Original GL ID to match for balance category
            'vendorid' => $input_decoded['vendorid'], // Vendor ID for filtering
            'new_gl_id' => $input_decoded['defaultbalancegl'] // New GL ID to set for balance category
        ];

        // Define the endpoint for updating GL ID for balance category
        $balanceApiUrl = 'http://127.0.0.1:8000/update_gl_in_journal';

        // Initialize a cURL session for balance update
        $chBalance = curl_init($balanceApiUrl);
        curl_setopt($chBalance, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($chBalance, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chBalance, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($balanceUpdateData))
        ]);
        curl_setopt($chBalance, CURLOPT_POSTFIELDS, json_encode($balanceUpdateData));

        // Execute the request and get the response for balance update
        $balanceResponse = curl_exec($chBalance);
        $balanceHttpCode = curl_getinfo($chBalance, CURLINFO_HTTP_CODE);

        // if ($balanceHttpCode == 200) {
        //     echo json_encode(['message' => 'Balance category updated successfully!']);
        // } else {
        //     echo json_encode(['error' => 'Failed to update balance category.', 'details' => $balanceResponse]);
        // }

        curl_close($chBalance); // Close balance cURL session
    }
}

// Prepare final JSON response
$finalResponse = [
    'updatevendor' => json_decode($response),
    'update_gl_in_journal_transaction' => isset($transactionResponse) ? json_decode($transactionResponse) : null,
    'update_gl_in_journal_balance' => isset($balanceResponse) ? json_decode($balanceResponse) : null,
];

// Output the final JSON response
echo json_encode($finalResponse);
?>