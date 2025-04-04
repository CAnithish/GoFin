<?php
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
// Get IRN and ABID from URL parameters
$irn = isset($_GET['irn']) ? $_GET['irn'] : '';

// // Endpoint and parameters
// $irn = "9ee24b5e02efcb95b91bd29691d8dfb8703fccd24c1dc316ef8bd217cd407b4f";
// $abid = 1000000000;
$url = "http://127.0.0.1:8000/einvoice";
$params = http_build_query(['abid' => $abid, 'irn' => $irn]);

// cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url?$params");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    exit;
}
curl_close($ch);

// Parse JSON response
$json_data = json_decode($response, true);
$decoded_data_list = process_json_data($json_data);
$final_data = $decoded_data_list[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Invoice Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            font-size: 10px; /* Adjust the size as needed */
            background-color: #f8f9fa;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .sectionout {
            width: 210mm; /* Limit width to A4 */
            max-width: 100%; /* Prevent overflow on smaller screens */
            margin: 0 auto; /* Center the section horizontally */
            padding: 10px; /* Add some padding for better readability */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional: Add a subtle shadow */
            background-color: #fff; /* Optional: Set a background color */
            border: 1px solid #ccc; /* Optional: Add a border to resemble a printed sheet */
        }

        .section {
            width: 204mm; /* Limit width to A4 */
            max-width: 100%; /* Prevent overflow on smaller screens */
            margin-bottom: 10px;
            background: #ffffff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .details {
            display: flex;
            justify-content: space-between;
        }

        .details p {
            margin: 5px 0;
            font-weight: normal;
        }

        table {
            width: 100%; /* Make the table span the full width of its container */
            max-width: 100%; /* Prevent it from exceeding the container's width */
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10px; /* Smaller font for tables */
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            word-wrap: break-word; /* Allow wrapping of long text */
            overflow: hidden; /* Prevent overflow */
            white-space: normal; /* Allow text to wrap */
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f1f1f1;
        }

        .sub-section {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
        }

        .flex-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .flex-row p {
            margin: 5px 0;
        }
        
    </style>
</head>
<body>
<div class="sectionout">    
    <h1>e-Invoice Details</h1>

    <div class="section">
        <h2>1. e-Invoice Details</h2>
        <div class="details">
            <p><span class="label">IRN:</span> <?php echo htmlspecialchars($final_data['Irn']); ?></p>
            <p><span class="label">Ack No:</span> <?php echo htmlspecialchars($final_data['AckNo']); ?></p>
            <p><span class="label">Ack Date:</span> <?php echo htmlspecialchars($final_data['AckDt']); ?></p>
        </div>
    </div>

    <div class="section">
        <h2>2. Transaction Details</h2>
        <div class="flex-row">
            <p><span class="label">Supply Type Code:</span> <?php echo htmlspecialchars($final_data['TranDtls']['SupTyp']); ?></p>
            <p><span class="label">Place of Supply:</span> <?php echo htmlspecialchars($final_data['BuyerDtls']['Pos']); ?></p>

        </div>
        <div class="flex-row">
            <p><span class="label">Document No:</span> <?php echo htmlspecialchars($final_data['DocDtls']['No']); ?></p>
            <p><span class="label">Document Date:</span> <?php echo htmlspecialchars($final_data['DocDtls']['Dt']); ?></p>
            <p><span class="label">Document Type:</span> <?php echo htmlspecialchars($final_data['DocDtls']['Typ']); ?></p>
        </div>
        <div class="flex-row">
            <p><span class="label">IGST Applicable Despite Supplier & Recipient Located In Same State:</span> <?php echo isset($final_data['TranDtls']['IgstOnIntra']) ? htmlspecialchars($final_data['TranDtls']['IgstOnIntra']) : 'N'; ?></p>
            <p><span class="label">Whether tax liability payable under reverse charge:</span> <?php echo isset($final_data['TranDtls']['RegRev']) ? htmlspecialchars($final_data['TranDtls']['RegRev']) : 'N'; ?></p>
        </div>
    </div>

    <div class="section">
    <h2>3. Party Details</h2>
    <div class="details">
        <div class="sub-section">
            <p><span class="label">Supplier:</span></p>
            <p>
                <?php echo htmlspecialchars($final_data['SellerDtls']['LglNm']); ?><br>
                <?php echo htmlspecialchars($final_data['SellerDtls']['Gstin']); ?><br>
                <?php if (isset($final_data['SellerDtls']['Addr1']) && !empty($final_data['SellerDtls']['Addr1'])): ?>
                    <?php echo htmlspecialchars($final_data['SellerDtls']['Addr1']); ?><br>
                <?php endif; ?>
                <?php if (isset($final_data['SellerDtls']['Addr2']) && !empty($final_data['SellerDtls']['Addr2'])): ?>
                    <?php echo htmlspecialchars($final_data['SellerDtls']['Addr2']); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars($final_data['SellerDtls']['Loc']); ?><br>
                <?php echo htmlspecialchars($final_data['SellerDtls']['Pin']); ?><br>
                <?php echo htmlspecialchars($final_data['SellerDtls']['Stcd']); ?><br>
            </p>
        </div>
        <div class="sub-section">
            <p><span class="label">Recipient:</span></p>
            <p>
                <?php echo htmlspecialchars($final_data['BuyerDtls']['LglNm']); ?><br>
                <?php echo htmlspecialchars($final_data['BuyerDtls']['Gstin']); ?><br>
                <?php if (isset($final_data['BuyerDtls']['Addr1']) && !empty($final_data['BuyerDtls']['Addr1'])): ?>
                    <?php echo htmlspecialchars($final_data['BuyerDtls']['Addr1']); ?><br>
                <?php endif; ?>
                <?php if (isset($final_data['BuyerDtls']['Addr2']) && !empty($final_data['BuyerDtls']['Addr2'])): ?>
                    <?php echo htmlspecialchars($final_data['BuyerDtls']['Addr2']); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars($final_data['BuyerDtls']['Loc']); ?><br>
                <?php echo htmlspecialchars($final_data['BuyerDtls']['Pin']); ?><br>
                <?php echo htmlspecialchars($final_data['BuyerDtls']['Stcd']); ?><br>
            </p>
        </div>
    </div>
    </div>
    
    <div class="section">
        <h2>4. Details of Goods / Services</h2>
        <table>
            <thead>
                <tr>
                    <th>SlNo</th>
                    <th>Item Description</th>
                    <th>HSN Code</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Discount (Rs)</th>
                    <th>Taxable Amount (Rs)</th>
                    <th>Tax Rate</th>
                    <th>Other Charges</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($final_data['ItemList'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['SlNo']); ?></td>
                        <td><?php echo htmlspecialchars($item['PrdDesc']); ?></td>
                        <td><?php echo htmlspecialchars($item['HsnCd']); ?></td>
                        <td><?php echo htmlspecialchars($item['Qty']); ?></td>
                        <td><?php echo htmlspecialchars($item['UnitPrice']); ?></td>
                        <td><?php echo isset($item['Discount']) ? htmlspecialchars($item['Discount']) : 0;?></td>
                        <td><?php echo htmlspecialchars($item['AssAmt']); ?></td>
                        <td><?php echo htmlspecialchars($item['GstRt']); ?></td>
                        <td><?php echo isset($item['OthChrg']) ? htmlspecialchars($item['OthChrg']) : 0;?></td>
                        <td><?php echo htmlspecialchars($item['TotItemVal']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <table>
            <thead>
                <tr>
                    <th>Tax'ble Amt</th>
                    <th>CGST Amt</th>
                    <th>SGST Amt</th>
                    <th>IGST Amt</th>
                    <th>CESS Amt</th>
                    <th>State CESS Amt</th>
                    <th>Discount</th>
                    <th>Other Charges</th>
                    <th>Round off Amt</th>
                    <th>Total Inv. Amt</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($final_data['ValDtls']['AssVal']); ?></td>
                    <td><?php echo isset($final_data['ValDtls']['CgstVal']) ? htmlspecialchars($final_data['ValDtls']['CgstVal']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['SgstVal']) ? htmlspecialchars($final_data['ValDtls']['SgstVal']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['IgstVal']) ? htmlspecialchars($final_data['ValDtls']['IgstVal']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['CesVal']) ? htmlspecialchars($final_data['ValDtls']['CesVal']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['StCesVal']) ? htmlspecialchars($final_data['ValDtls']['StCesVal']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['Discount']) ? htmlspecialchars($final_data['ValDtls']['Discount']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['OthChrg']) ? htmlspecialchars($final_data['ValDtls']['OthChrg']) : 0;?></td>
                    <td><?php echo isset($final_data['ValDtls']['RndOffAmt']) ? htmlspecialchars($final_data['ValDtls']['RndOffAmt']) : 0;?></td>
                    <td><?php echo htmlspecialchars($final_data['ValDtls']['TotInvVal']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>    
</body>
</html>
