<?php
header('Content-Type: application/json');

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
// $abid = 1000000000;
// Fetch `default_gl_master` data
$defaultGlMasterUrl = "http://127.0.0.1:8000/defaultglmaster";
$chDefault = curl_init();
curl_setopt($chDefault, CURLOPT_URL, $defaultGlMasterUrl);
curl_setopt($chDefault, CURLOPT_RETURNTRANSFER, true);
$defaultResponse = curl_exec($chDefault);
curl_close($chDefault);

// Fetch `gl_master` data
$glMasterUrl = "http://127.0.0.1:8000/glmaster?abid={$abid}";
$chGlMaster = curl_init();
curl_setopt($chGlMaster, CURLOPT_URL, $glMasterUrl);
curl_setopt($chGlMaster, CURLOPT_RETURNTRANSFER, true);
$glMasterResponse = curl_exec($chGlMaster);
curl_close($chGlMaster);

// Decode responses
$defaultGlMasterData = json_decode($defaultResponse, true);
$glMasterData = json_decode($glMasterResponse, true);

// Compare and add import status
if (!empty($defaultGlMasterData) && !empty($glMasterData)) {
    foreach ($defaultGlMasterData as &$defaultItem) {
        $isImported = false;

        foreach ($glMasterData as $glItem) {
            if ($defaultItem['default_gl_id'] == $glItem['default_gl_id']) {
                $isImported = true;
                break;
            }
        }

        $defaultItem['import_status'] = $isImported ? 'Already Imported' : 'Not Imported';
    }
}

echo json_encode($defaultGlMasterData);
?>
