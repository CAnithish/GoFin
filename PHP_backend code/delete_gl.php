<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get gl_id and abid from POST request
    $gl_id = $_POST['gl_id'];
    $abid = $_POST['abid'];

    // Define the API endpoint URL
    $url = "http://localhost:8000/glmaster"; // Adjust port if necessary

    // Prepare parameters for deletion
    $params = [
        "gl_id" => $gl_id,
        "abid" => $abid
    ];

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params)); // Append parameters to URL
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); // Specify DELETE request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json' // Set content type to JSON
    ]);

    // Execute the DELETE request
    $response = curl_exec($ch);
    $decodedResponse = json_decode($response,true);
    // Get HTTP response code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Handle response based on HTTP status code
    if ($decodedResponse['status_code'] == 200) {
        echo $response;
        $_SESSION['success_message'] = "Record deleted successfully."; // Set success message
        header("Location: glmaster.php"); // Redirect to glmaster.php
        exit();
    } elseif ($decodedResponse['status_code'] == 403) {
        echo $response;
        $_SESSION['failure_message'] = "This category is already in use. Hence unable to delete this category."; // Set success message
        header("Location: glmaster.php"); // Redirect to glmaster.php
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => "Error: HTTP Status Code " . $http_code]);
    }


    // Close cURL session
    curl_close($ch);
}
?>
