<?php
ob_start();

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

// Fetch data from FastAPI endpoint
$inv_report_url = "http://127.0.0.1:8000/inv_report?abid=" . urlencode($abid);
$inv_report_json = file_get_contents($inv_report_url);
$inv_report = json_decode($inv_report_json, true);
if (!is_array($inv_report)) {
    $inv_report = []; // Prevent errors if response is invalid
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .add-button {
            float: right;
            padding: 10px 15px;
            background-color: #28a745;
            /* Green color */
            color: white;
            border: none;
            cursor: pointer;
        }

        .modal-dialog {
            max-width: 90%;
            margin: 1.75rem auto;
        }

        .modal-content {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            padding: 10px;
            border-radius: 5px;
        }

        .input-group-text {
            padding: 10px;
            border-radius: 5px;
        }

        .modal-header {
            background-color: #f2f2f2;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .modal-footer {
            padding: 10px;
            border-top: 1px solid #ddd;
        }

        .modal-body {
            padding: 20px;
        }

        .table {
            width: 100%;
            margin-bottom: 15px;
        }

        .table th,
        .table td {
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .filter-input {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <h1>Inventory Report</h1>
    <button id="historyBtn" class="btn btn-primary mb-3">History</button>
    <!-- Button to open the modal -->
    <button type="button" class="btn add-button" data-toggle="modal" data-target="#itemModal">+</button>

    <form id="historyForm" action="invhistory.php" method="POST">
        <input type="hidden" name="selected_items" id="selected_items">
    </form>
 <!-- Item Modal -->
 <div class="modal fade" id="itemModal" tabindex="-1" role="dialog" aria-labelledby="itemModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Create New Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="itemForm">
                    <div class="form-group">
                        <label for="prdDesc">Product Description:</label>
                        <input type="text" class="form-control" id="prdDesc" name="PrdDesc" required>
                    </div>
                    <div class="form-group">
                        <label for="hsnCd">HSN Code:</label>
                        <input type="text" class="form-control" id="hsnCd" name="HsnCd">
                    </div>
                    <div class="form-group">
                        <label for="barcde">Barcode:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="barcde" name="Barcde" >
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="openScannerModal()">
                                    📷
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="form-group">
                        <label for="qty">Quantity:</label>
                        <input type="number" class="form-control" id="qty" name="Qty" step="0.0001" required>
                    </div> -->
                    <div class="form-group">
                        <label for="unit">Unit:</label>
                        <select class="form-control" id="unit" name="Unit" required>
                            <option value="" disabled selected>Select Unit</option>
                            <option value="BAG">BAG (BAGS)</option>
                            <option value="BAL">BAL (BALE)</option>
                            <option value="BDL">BDL (BUNDLES)</option>
                            <option value="BKL">BKL (BUCKLES)</option>
                            <option value="BOU">BOU (BILLION OF UNITS)</option>
                            <option value="BOX">BOX (BOX)</option>
                            <option value="BTL">BTL (BOTTLES)</option>
                            <option value="BUN">BUN (BUNCHES)</option>
                            <option value="CAN">CAN (CANS)</option>
                            <option value="CBM">CBM (CUBIC METERS)</option>
                            <option value="CCM">CCM (CUBIC CENTIMET</option>
                            <option value="CMS">CMS (CENTIMETERS)</option>
                            <option value="CTN">CTN (CARTONS)</option>
                            <option value="DOZ">DOZ (DOZENS)</option>
                            <option value="DRM">DRM (DRUMS)</option>
                            <option value="GGK">GGK (GREAT GROSS)</option>
                            <option value="GMS">GMS (GRAMMES)</option>
                            <option value="GRS">GRS (GROSS)</option>
                            <option value="GYD">GYD (GROSS YARDS)</option>
                            <option value="KGS">KGS (KILOGRAMS)</option>
                            <option value="KLR">KLR (KILOLITRE)</option>
                            <option value="KME">KME (KILOMETRE)</option>
                            <option value="LTR">LTR (LITRES)</option>
                            <option value="MLT">MLT (MILILITRE)</option>
                            <option value="MTR">MTR (METERS)</option>
                            <option value="MTS">MTS (METRIC TON)</option>
                            <option value="NOS">NOS (NUMBERS)</option>
                            <option value="OTH">OTH (OTHERS)</option>
                            <option value="PAC">PAC (PACKS)</option>
                            <option value="PCS">PCS (PIECES)</option>
                            <option value="PRS">PRS (PAIRS)</option>
                            <option value="QTL">QTL (QUINTAL)</option>
                            <option value="ROL">ROL (ROLLS)</option>
                            <option value="SET">SET (SETS)</option>
                            <option value="SQF">SQF (SQUARE FEET)</option>
                            <option value="SQM">SQM (SQUARE METERS)</option>
                            <option value="SQY">SQY (SQUARE YARDS)</option>
                            <option value="TBS">TBS (TABLETS)</option>
                            <option value="TGM">TGM (TEN GROSS)</option>
                            <option value="THD">THD (THOUSANDS)</option>
                            <option value="TON">TON (TONNES)</option>
                            <option value="TUB">TUB (TUBES)</option>
                            <option value="UGS">UGS (US GALLONS)</option>
                            <option value="UNT">UNT (UNITS)</option>
                            <option value="YDS">YDS (YARDS)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="unitPrice">Unit Price:</label>
                        <input type="number" class="form-control" id="unitPrice" name="UnitPrice" step="0.0001"
                            required onchange="computeValues()">
                    </div>
                    <div class="form-group">
                        <label for="gstRt">GST Rate (%):</label>
                        <input type="number" class="form-control" id="gstRt" name="GstRt" step="0.01" onchange="computeValues()">
                    </div>
                    <div class="form-group">
                        <label for="cesRt">CESS Rate (%):</label>
                        <input type="number" class="form-control" id="cesRt" name="CesRt" step="0.01" onchange="computeValues()">
                    </div>
                    <div class="form-group">
                        <label for="cesNonAdvlAmt">CESS Non-Adval Amt:</label>
                        <input type="number" class="form-control" id="cesNonAdvlAmt" name="CesNonAdvlAmt"
                            step="0.01" onchange="computeValues()">
                    </div>
                    <div class="form-group">
                        <label for="stateCesRt">State CESS Rate (%):</label>
                        <input type="number" class="form-control" id="stateCesRt" name="StateCesRt" step="0.01"
                            onchange="computeValues()">
                    </div>
                    <div class="form-group">
                        <label for="stateCesNonAdvlAmt">State CESS Non-Adval Amt:</label>
                        <input type="number" class="form-control" id="stateCesNonAdvlAmt"
                            name="StateCesNonAdvlAmt" step="0.01" onchange="computeValues()">
                    </div>
                    <div class="form-group">
                        <label for="prdSlNo">Product Serial No:</label>
                        <input type="text" class="form-control" id="prdSlNo" name="PrdSlNo">
                    </div>
                    <div class="form-group">
                        <label for="itemcode">Item Code:</label>
                        <input type="number" class="form-control" id="itemcode" name="itemcode">
                    </div>
                    <div class="form-group">
                        <label for="gl_id">GL ID:</label>
                        <input type="number" class="form-control" id="gl_id" name="gl_id" value=100000000115>
                    </div>
                    <div class="form-group">
                        <label for="marginPercent">Margin Percent:</label>
                        <input type="number" class="form-control" id="margin_percent" name="margin_percent"
                            value=0 onchange="computeValuesMargin()">
                    </div>
                    <div class="form-group">
                        <label for="sellingPrice">Selling Price:</label>
                        <input type="number" class="form-control" id="selling_price" name="selling_price" onchange="computeValuesSP()">
                    </div>
                    <!-- <div class="form-group">
                        <label for="sellingPrice">abid:</label>
                        <input type="number" class="form-control" id="abid" name="abid">
                    </div> -->

                    <button type="button" class="btn btn-primary" onclick="saveItem()">Save Item</button>
                </form>
            </div>
        </div>
    </div>
</div>
     <!-- Modal for the scanner -->
     <div id="scannerModal" class="modal">
        <div class="modal-content">
            <video id="video" autoplay playsinline></video>
            <button id="closeScannerButton" onclick="closeScannerModal()">Close Scanner</button>
        </div>
    </div>

    <!-- <span class="camera-icon" onclick="requestCameraPermission()">📷</span> -->
    
<table id="inventoryTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>Item group</th>
            <th>Item ID</th>
            <th>Total Quantity</th>
            <th>Product Description</th>
            <th>HSN Code</th>
            <th>Barcode</th>
            <th>Unit</th>
            <th>Unit Price</th>
            <th>Margin</th>
            <th>Selling Price</th>
            <th>GST Rate</th>
        </tr>
        <tr>
            <th></th>
            <th><input type="text" class="filter-input" placeholder="Search Item group"></th>
            <th><input type="text" class="filter-input" placeholder="Search Item ID"></th>
            <th><input type="text" class="filter-input" placeholder="Search Qty"></th>
            <th><input type="text" class="filter-input" placeholder="Search Desc"></th>
            <th><input type="text" class="filter-input" placeholder="Search HSN"></th>
            <th><input type="text" class="filter-input" placeholder="Search Barcode"></th>
            <th><input type="text" class="filter-input" placeholder="Search Unit"></th>
            <th><input type="text" class="filter-input" placeholder="Search Price"></th>
            <th><input type="text" class="filter-input" placeholder="Search Margin"></th>
            <th><input type="text" class="filter-input" placeholder="Search Selling"></th>
            <th><input type="text" class="filter-input" placeholder="Search GST"></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inv_report as $item): ?>
            <tr>
                <td><input type="checkbox" class="itemCheckbox" value="<?= htmlspecialchars($item['item_id']) ?>"></td>
                <td><?= htmlspecialchars($item['item_group_id']) ?></td>
                <td><?= htmlspecialchars($item['item_id']) ?></td>
                <td><?= htmlspecialchars($item['total_quantity']) ?></td>
                <td><?= htmlspecialchars($item['PrdDesc']) ?></td>
                <td><?= htmlspecialchars($item['HsnCd']) ?></td>
                <td><?= htmlspecialchars($item['Barcde']) ?></td>
                <td><?= htmlspecialchars($item['Unit']) ?></td>
                <td><?= htmlspecialchars($item['UnitPrice']) ?></td>
                <td><?= htmlspecialchars($item['margin_percent']) ?></td>
                <td><?= htmlspecialchars($item['selling_price']) ?></td>
                <td><?= htmlspecialchars($item['GstRt']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script type="module">
        import { BrowserMultiFormatReader } from 'https://cdn.jsdelivr.net/npm/@zxing/browser@latest/+esm';

        const codeReader = new BrowserMultiFormatReader();
        let streamRef = null;
        let scanningActive = false;

        const itemTable = document.getElementById('itemTable');
        const itemTableContainer = document.getElementById('itemTablecontainer');

        async function requestCameraPermission() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(track => track.stop());
                openScannerModal();
            } catch (error) {
                alert("Camera access is denied. Please allow access in browser settings.");
                console.error("Camera access error:", error);
            }
        }

        async function startScanner() {
            const video = document.getElementById('video');
            scanningActive = true;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                streamRef = stream;
                video.srcObject = stream;
                // Enable tap-to-focus
                video.addEventListener('click', () => focusCamera());

                codeReader.decodeFromVideoElement(video, (result, err) => {
                    if (result && scanningActive) {
                        scanningActive = false;
                        let barcodeValue = result.text; // Capture the barcode
                        document.getElementById('barcde').value = barcodeValue;
                    
                        // Hide video instantly to prevent flashing effect
                        video.style.display = "none";

                        // Stop scanner & close modal smoothly
                        fadeOutModal();
                        stopScanner();
                    }
                });

            } catch (err) {
                alert('Camera access is blocked. Please allow access in browser settings.');
                console.error(err);
                closeScannerModal();
            }
        }

        function stopScanner() {
            if (streamRef) {
                streamRef.getTracks().forEach(track => track.stop());
                streamRef = null;
            }
            codeReader.reset();
        }

        function openScannerModal() {
            const modal = document.getElementById('scannerModal');
            modal.style.display = 'block';
            modal.classList.remove('hidden');
            startScanner();
        }

        function fadeOutModal() {
            const modal = document.getElementById('scannerModal');
            modal.classList.add('hidden');
            // Wait for the fade-out transition, then fully hide the modal
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function closeScannerModal() {
            fadeOutModal();
            stopScanner();
        }

        async function focusCamera() {
            if (streamRef) {
                const track = streamRef.getVideoTracks()[0];
                if ('applyConstraints' in track) {
                    await track.applyConstraints({ advanced: [{ focusMode: "continuous" }] });
                }
            }
        }
        window.openScannerModal = openScannerModal;
        window.requestCameraPermission = requestCameraPermission;
        window.openScannerModal = openScannerModal;
        window.closeScannerModal = closeScannerModal;

</script>

<script>
$(document).ready(function() {
    var table = $('#inventoryTable').DataTable({
        initComplete: function() {
            this.api().columns().every(function() {
                var that = this;
                $('input', this.header()).on('keyup change', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });
            });
        }
    });

    $('#selectAll').on('click', function() {
        $('.itemCheckbox').prop('checked', this.checked);
    });

    $('#historyBtn').on('click', function() {
        var selectedItems = $('.itemCheckbox:checked').map(function() {
            return this.value;
        }).get();

        if (selectedItems.length === 0) {
            alert("Please select at least one item.");
            return;
        }

        $('#selected_items').val(JSON.stringify(selectedItems));
        $('#historyForm').submit();
    });
});
// Function to clear form fields when the modal is closed
$('#itemModal').on('hidden.bs.modal', function (e) {
    document.getElementById('itemForm').reset();
});

// Function to get the value of a specific key from the cookie(JSON cookie)
function getCookieValue(cookieName, key) {
    const cookies = document.cookie.split('; ');
    const cookie = cookies.find(c => c.startsWith(cookieName + '='));
    if (cookie) {
        const cookieValue = cookie.split('=')[1];
        const keyValuePairs = JSON.parse(decodeURIComponent(cookieValue));
        //return decodeURIComponent(cookieValue);//to log entire JSON cookie
        return keyValuePairs[key] || "Key not found";
    }
    return null;
}

const abid = getCookieValue('userdetails', 'abid');
// Function to save item data from the modal form
function saveItem() {
    const itemData = {
        abid: abid,
        PrdDesc: document.getElementById('prdDesc').value,
        HsnCd: document.getElementById('hsnCd').value,
        Barcde: document.getElementById('barcde').value,
        Unit: document.getElementById('unit').value,
        UnitPrice: parseFloat(document.getElementById('unitPrice').value),
        GstRt: parseFloat(document.getElementById('gstRt').value),
        CesRt: parseFloat(document.getElementById('cesRt').value),
        CesNonAdvlAmt: parseFloat(document.getElementById('cesNonAdvlAmt').value),
        StateCesRt: parseFloat(document.getElementById('stateCesRt').value),
        StateCesNonAdvlAmt: parseFloat(document.getElementById('stateCesNonAdvlAmt').value),
        PrdSlNo: document.getElementById('prdSlNo').value,
        itemcode: parseInt(document.getElementById('itemcode').value),
        gl_id: parseInt(document.getElementById('gl_id').value),
        margin_percent: parseFloat(document.getElementById('margin_percent').value),
        selling_price: parseFloat(document.getElementById('selling_price').value)
    };

    console.log(itemData);
    // Create an XMLHttpRequest object
    const xhr = new XMLHttpRequest();

    // Set the request method and URL
    xhr.open('POST', 'create_item_for_bill.php', true);

    // Set the request headers
    xhr.setRequestHeader('Content-Type', 'application/json');

    // Handle the response
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Handle success

            // Optionally, refresh the inventory report table
            // einvoiceData = JSON.parse(xhr.responseText); // Assign to global variable
            let response = JSON.parse(xhr.responseText);
            console.log(response);
            if (response.status_code === 403){
                alert('Item already exist with same (name/barcode) and unitprice!');  
            }else if (response.status_code === 200){
                alert('Item saved successfully!');
                $('#itemModal').modal('hide'); // Close the modal
            } else {
                // Handle other status codes (e.g., server errors)
                console.error("Server returned an unexpected status code:", response.status_code);
                alert('An error occurred while saving the item. Please try again later.'); // More generic message
            }
            // location.reload();
        } else {
            // Handle errors
            console.error(xhr.responseText);
            alert('An error occurred while saving the item.');
        }
    };

    // Handle network errors
    xhr.onerror = function() {
        console.error('Network error occurred.');
        alert('A network error occurred while saving the item.');
    };

    // Send the request
    xhr.send(JSON.stringify(itemData));
}
window.saveItem = saveItem;

function computeValues() {
            let unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
            let margin_percent = parseFloat(document.getElementById('margin_percent').value) || 0;
            let selling_price = parseFloat(document.getElementById('selling_price').value) || 0;

            // If margin_percent is 0, calculate selling_price
            if (margin_percent === 0) {
                selling_price = unitPrice * (1 + (margin_percent / 100));
                document.getElementById('selling_price').value = selling_price.toFixed(2);
            }
            // If selling_price is changed, calculate margin_percent
            else if (selling_price !== 0) {
                margin_percent = ((selling_price - unitPrice) / unitPrice) * 100;
                document.getElementById('margin_percent').value = margin_percent.toFixed(2);
            }

         // Update other computed values
         /*   const qty = parseFloat(document.getElementById('qty').value) || 0;
            const gstRt = parseFloat(document.getElementById('gstRt').value) || 0;
            const cesRt = parseFloat(document.getElementById('cesRt').value) || 0;
            const cesNonAdvlAmt = parseFloat(document.getElementById('cesNonAdvlAmt').value) || 0;
            const stateCesRt = parseFloat(document.getElementById('stateCesRt').value) || 0;
            const stateCesNonAdvlAmt = parseFloat(document.getElementById('stateCesNonAdvlAmt').value) || 0; */
        }
window.computeValues = computeValues;
function computeValuesMargin() {
            let unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
            let margin_percent = parseFloat(document.getElementById('margin_percent').value) || 0;
            let selling_price = parseFloat(document.getElementById('selling_price').value) || 0;

                selling_price = unitPrice * (1 + (margin_percent / 100));
                document.getElementById('selling_price').value = selling_price.toFixed(2);
        }
window.computeValuesMargin = computeValuesMargin;

function computeValuesSP() {
            let unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
            let margin_percent = parseFloat(document.getElementById('margin_percent').value) || 0;
            let selling_price = parseFloat(document.getElementById('selling_price').value) || 0;


                margin_percent = ((selling_price - unitPrice) / unitPrice) * 100;
                document.getElementById('margin_percent').value = margin_percent.toFixed(2);

        }
window.computeValuesSP = computeValuesSP;

window.openScannerModal = openScannerModal;
window.requestCameraPermission = requestCameraPermission;
window.openScannerModal = openScannerModal;
window.closeScannerModal = closeScannerModal;
</script>

</body>
</html>
