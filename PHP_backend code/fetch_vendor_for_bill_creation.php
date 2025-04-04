
<?php
$abid = $_GET['abid'];
// $abid=1000000000;
$url = "http://127.0.0.1:8000/vendorlist?abid=".$abid;

$chVendor = curl_init();
curl_setopt($chVendor, CURLOPT_URL, $url);
curl_setopt($chVendor, CURLOPT_RETURNTRANSFER, 1);
$responseVendor = curl_exec($chVendor);
$httpCodeVendor = curl_getinfo($chVendor, CURLINFO_HTTP_CODE);
$vendordata = json_decode($responseVendor, true);
curl_close($chVendor);

// header('Content-Type: application/json');
echo json_encode($vendordata);
?>