
<?php
$abid = $_GET['abid'];
// $abid=1000000000;
$glUrl = "http://127.0.0.1:8000/glmaster?abid=".$abid;

$chGl = curl_init();
curl_setopt($chGl, CURLOPT_URL, $glUrl);
curl_setopt($chGl, CURLOPT_RETURNTRANSFER, 1);
$glResponse = curl_exec($chGl);
$glHttpCode = curl_getinfo($chGl, CURLINFO_HTTP_CODE);
$gldata = json_decode($glResponse, true);
curl_close($chGl);

// header('Content-Type: application/json');
echo json_encode($gldata);
?>