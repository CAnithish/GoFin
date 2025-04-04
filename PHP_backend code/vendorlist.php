<!DOCTYPE html>
<html>
<head>
    <title>Vendor List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .vendor-card {
            background: linear-gradient(to bottom, #ffffff, #e6f7ff);
            border: none;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .vendor-card:hover {
            transform: scale(1.05);
        }
        .vendor-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
        }
        .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff5733;
        }
        .category {
            font-size: 1.0rem;
            font-weight: bold;
            color: #28a745;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

    </style>
</head>
<body>
   
    <?php
        // $abid = 1000000000; // Replace with the desired item ID

                //fetch cookie from JSON cookie:
        if (isset($_COOKIE['userdetails'])) {
            // Decode the JSON data
            $userdetails = json_decode($_COOKIE['userdetails'], true);

            // Access a specific key's value
            $abid = isset($userdetails['abid']) ? $userdetails['abid'] : "Key not found";
            $mobilenumber = isset($userdetails['mobilenumber']) ? $userdetails['mobilenumber'] : "Key not found";
            $zoho_org_id = isset($userdetails['zoho_org_id']) ? $userdetails['zoho_org_id'] : "Key not found";
            //$Billing_software = isset($userdetails['Billing_software']) ? $userdetails['Billing_software'] : "Key not found";

            if($abid==="Key not found"){
                header("Location: http://localhost/example/ablogin.html");
            }

            } else {
            echo "Cookie 'userdetails' is not set!";
            header("Location: http://localhost/example/ablogin.html");
            
            }
        $glUrl = "http://127.0.0.1:8000/glmaster?abid=" . $abid;

        // Fetch GL Master Data
        $chGl = curl_init();
        curl_setopt($chGl, CURLOPT_URL, $glUrl);
        curl_setopt($chGl, CURLOPT_RETURNTRANSFER, 1);
        $glResponse = curl_exec($chGl);
        $glHttpCode = curl_getinfo($chGl, CURLINFO_HTTP_CODE);
        curl_close($chGl);

        $glData = [];
        if ($glHttpCode == 200) {
            $glData = json_decode($glResponse, true);
        }
        // Create an associative array for easy GL lookup
        $glMap = [];
        foreach ($glData as $gl) {
            $glMap[$gl['gl_id']] = $gl['gl_name'] . " (" . $gl['gl_nature'] . ")"; 
        }
    ?>

    <div class="container mt-4">
        <h1 class="mb-4 text-center">Vendor List</h1>

        <div class="grid-container">
            <?php
            // $abid = 1000000000; // Replace with the desired item ID
            $url = "http://127.0.0.1:8000/vendorlist?abid=" . $abid;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode == 200) {
                $data = json_decode($response, true);
            } elseif ($httpCode == 404) {
                echo "<p class='alert alert-warning'>No vendors found.</p>";
            } else {
                echo "<p class='alert alert-danger'>Error fetching vendor data. HTTP Status Code: " . $httpCode . "</p>";
            }

            curl_close($ch);

            if ($httpCode == 200) {
                foreach ($data as $vendor) {
                    $lglNmDecoded = json_decode('"' . $vendor['lglNm'] . '"'); // Decoding Unicode characters
            ?>
                <div class="vendor-card" onclick="showVendorDetails(<?php echo htmlspecialchars(json_encode($vendor)); ?>)">
                    <div>
                        <h5 class="vendor-title"><?php echo htmlspecialchars($vendor['lglNm']); ?></h5>
                        <!-- <p class="text-muted mb-1">Vendor ID: <?php echo htmlspecialchars($vendor['vendorid']); ?></p> -->
                        <p class="text-muted mb-1">GSTIN: <?php echo htmlspecialchars($vendor['gstin']); ?></p>
                        <?php 
                        // Use GL Map for lookup
                        $tradeGlName = isset($glMap[$vendor['defaulttradegl']]) ? $glMap[$vendor['defaulttradegl']] : '';
                        $balanceGlName = isset($glMap[$vendor['defaultbalancegl']]) ? $glMap[$vendor['defaultbalancegl']] : ''; 
                        ?>
                        <p class="category"><?php echo $tradeGlName; ?></p>

                        <p class="text-primary"><?php echo $balanceGlName; ?></p>
                    </div>
                </div>
            <?php
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Modal for Vendor Details -->
    <div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vendorModalLabel">Vendor Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="vendorForm">
                        <div class="mb-3">
                            <label for="vendorName" class="form-label">Vendor Name</label>
                            <input type="text" class="form-control" id="vendorName" name="lglNm">
                        </div>
                        <div class="mb-3">
                            <label for="vendorId" class="form-label">Vendor ID</label>
                            <input type="text" class="form-control" id="vendorId" name="vendorid" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="gstin" class="form-label">GSTIN</label>
                            <input type="text" class="form-control" id="gstin" name="gstin">
                        </div>
                        <!-- Hidden input for original transaction category -->
                        <input type="hidden" id="olddefaulttradegl" name="olddefaulttradegl"  >
                        <div class="mb-3">
                            <label for="transactioncategory" class="form-label">Transaction Category</label>
                            <select class="form-select" id="transactioncategory" name="defaulttradegl">
                                <option value="">Select Transaction Category</option>
                                <?php foreach ($glData as $gl): ?>
                                    <option value="<?php echo htmlspecialchars($gl['gl_id']); ?>">
                                        <?php echo htmlspecialchars($gl['gl_name']) . ' (' . htmlspecialchars($gl['gl_nature']) . ')' ; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                            <!-- Inside your modal body, after balance category -->
                            <div class="mb-3" id="transactionCheckboxContainer" style="display: none;">
                                <input type="checkbox" id="updateTransactionCheckbox" name="updateTransactionCheckbox">
                                <label for="updateTransactionCheckbox">
                                    Update Transaction Category for all old bills.
                                    <span data-bs-toggle="tooltip" title="This will update all related bills and records for the transaction category.">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                </label>
                            </div>
                            <!-- Hidden input for original balance category -->
                            <input type="hidden" id="olddefaultbalancegl" name="olddefaultbalancegl"  >
                            <div class="mb-3">
                                <label for="balancecategory" class="form-label">Balance Category</label>
                                <select class="form-select" id="balancecategory" name="defaultbalancegl" required>
                                    <!-- <option value="">Select Balance Category</option> -->
                                    <?php foreach ($glData as $gl): ?>
                                        <?php if ($gl['gl_nature'] === 'asset' || $gl['gl_nature'] === 'liability'): ?>
                                            <option value="<?php echo htmlspecialchars($gl['gl_id']); ?>">
                                                <?php echo htmlspecialchars($gl['gl_name']) . ' (' . htmlspecialchars($gl['gl_nature']) . ')'; ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Inside your modal body, after balance category -->
                            <!-- Warning message -->
                            <div class="alert alert-warning" id="balanceWarningMessage" style="display: none;">
                                All records will be updated by default.
                            </div>

                            <!-- Hidden input to capture balance category change -->
                            <input type="hidden" id="updateBalanceCheckbox" name="updateBalanceCheckbox" value="off">
                        <div class="mb-3">
                            <label for="pan" class="form-label">PAN</label>
                            <input type="text" class="form-control" id="pan" name="pan">
                        </div>
                        <div class="mb-3">
                            <label for="tdssection" class="form-label">TDS section</label>
                            <input type="text" class="form-control" id="tdssection" name="tdssection">
                            <label for="tdsrate" class="form-label">TDS Rate</label>
                            <input type="text" class="form-control" id="tdsrate" name="tdsrate">
                        </div>
                        <div class="mb-3">
                            <label for="addr1" class="form-label">Address line 1</label>
                            <input type="text" class="form-control" id="addr1" name="addr1">
                        </div>
                        <div class="mb-3">
                            <label for="addr2" class="form-label">Address line 2</label>
                            <input type="text" class="form-control" id="addr2" name="addr2">
                        </div>
                        <div class="mb-3">
                            <label for="addr3" class="form-label">Address line 3</label>
                            <input type="text" class="form-control" id="addr3" name="addr3">
                        </div>
                        <div class="mb-3">
                            <label for="loc" class="form-label">City</label>
                            <input type="text" class="form-control" id="loc" name="loc">
                        </div>
                        <div class="mb-3">
                            <label for="pin" class="form-label">PIN/ZIP code</label>
                            <input type="text" class="form-control" id="pin" name="pin">
                        </div>
                        <div class="mb-3">
                            <label for="stcd" class="form-label">State code</label>
                            <input type="text" class="form-control" id="stcd" name="stcd">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone number</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="bankac" class="form-label">Bank account number</label>
                            <input type="text" class="form-control" id="bankac" name="bankac">
                        </div>
                        <div class="mb-3">
                            <label for="ifsc" class="form-label">IFSC code</label>
                            <input type="text" class="form-control" id="ifsc" name="ifsc">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="saveVendorDetails()">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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


            // Example usage:
            const abid = getCookieValue('userdetails', 'abid');


        function showVendorDetails(vendor) {
            document.getElementById('vendorName').value = vendor.lglNm;
            document.getElementById('vendorId').value = vendor.vendorid;
            document.getElementById('gstin').value = vendor.gstin;
            document.getElementById('pan').value = vendor.pan;
            document.getElementById('tdssection').value = vendor.tdssection;
            document.getElementById('tdsrate').value = vendor.tdsrate;
            document.getElementById('addr1').value = vendor.addr1;
            document.getElementById('addr2').value = vendor.addr2;
            document.getElementById('addr3').value = vendor.addr3;
            document.getElementById('loc').value = vendor.loc;
            document.getElementById('pin').value = vendor.pin;
            document.getElementById('stcd').value = vendor.stcd;
            document.getElementById('email').value = vendor.email;
            document.getElementById('phone').value = vendor.phone;
            document.getElementById('mobile').value = vendor.mobile;
            document.getElementById('bankac').value = vendor.bankac;
            document.getElementById('ifsc').value = vendor.ifsc;
            document.getElementById('olddefaulttradegl').value = vendor.defaulttradegl;
            document.getElementById('olddefaultbalancegl').value = vendor.defaultbalancegl;

            // Set Transaction Category
            const transactionSelect = document.getElementById('transactioncategory');
            transactionSelect.value = vendor.defaulttradegl || ''; // Set value directly
            // If you want to ensure it's visually selected, you could loop through options
            for (let option of transactionSelect.options) {
                if (option.value === vendor.defaulttradegl) {
                    option.selected = true; // Visually select the matching option
                    break;
                }
            }

            // Set Balance Category
            const balanceSelect = document.getElementById('balancecategory');
            balanceSelect.value = vendor.defaultbalancegl || ''; // Set value directly
            // Loop through options to visually select
            for (let option of balanceSelect.options) {
                if (option.value === vendor.defaultbalancegl) {
                    option.selected = true; // Visually select the matching option
                    break;
                }
            }

            const modal = new bootstrap.Modal(document.getElementById('vendorModal'));
            modal.show();
        }

        function saveVendorDetails() {
            const form = document.getElementById('vendorForm');
            const formData = new FormData(form);

            // Convert FormData to object
            const data = Object.fromEntries(formData.entries());
            // console.log(data)
            // Parse specific fields to match the expected types in FastAPI
            const formattedData = {
                abid: parseInt(abid), // Convert to integer
                vendorid: parseInt(data.vendorid), // Convert to integer
                is_trans_similar: data.is_trans_similar || null,
                defaultbalancegl: data.defaultbalancegl ? parseInt(data.defaultbalancegl) : null,
                defaulttradegl: data.defaulttradegl ? parseInt(data.defaulttradegl) : null,
                lglNm: data.lglNm || null,
                gstin: data.gstin || null,
                pan: data.pan || null,
                tdssection: data.tdssection || null,
                tdsrate: data.tdsrate ? parseFloat(data.tdsrate) : null,
                addr1: data.addr1 || null,
                addr2: data.addr2 || null,
                addr3: data.addr3 || null,
                loc: data.loc || null,
                pin: data.pin ? parseInt(data.pin) : null,
                stcd: data.stcd || null,
                email: data.email || null,
                mobile: data.mobile ? data.mobile : null,
                phone: data.phone ? data.phone : null,
                bankac: data.bankac || null,
                ifsc: data.ifsc || null,
                lowertdsrate: data.lowertdsrate ? parseFloat(data.lowertdsrate) : null,
                lowertdscertno: data.lowertdscertno || null,
                updateBalanceCheckbox: data.updateBalanceCheckbox || null,
                updateTransactionCheckbox:  data.updateTransactionCheckbox || null,
                olddefaulttradegl: data.olddefaulttradegl ? parseInt(data.olddefaulttradegl) : null,
                olddefualtbalancegl:  data.olddefaultbalancegl ? parseInt(data.olddefaultbalancegl) : null,
            };

            // console.log("Formatted Data:", formattedData);

            // console.log("Formatted Data:", JSON.stringify(formattedData));

            // $formattedData = {
            //     "abid":1000000000,
            //     "defaultbalancegl":100000000005,
            //     "defaulttradegl":100000000000,
            //     "gstin":"33NEWGSTIN123WW",
            //     "lglNm":"Updated Legal Name222",
            //     "vendorid":100000000015
            // }


            // Call the PHP proxy script using PUT

            fetch('updateVendor.php', {
                method: 'PUT', // Use PUT for updating resources
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formattedData), // Send properly formatted data
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json(); // Handle response properly
            })
            .then(data => {
                // console.log("Response from server:", data); // Log the entire response object

                // Extracting statuses from the response
                const vendorUpdateStatus = data.updatevendor ? data.updatevendor.message : 'No vendor update status available.';
                const transactionUpdateStatus = data.update_gl_in_journal_transaction ? data.update_gl_in_journal_transaction.message : 'No transaction update status available.';
                const balanceUpdateStatus = data.update_gl_in_journal_balance ? data.update_gl_in_journal_balance.message : 'No balance update status available.';
       
                // Displaying each status in an alert or on the webpage
                alert(`Vendor Update Status: ${vendorUpdateStatus}\n` +
                    `Transaction category: ${transactionUpdateStatus}\n` +
                    `Balance category: ${balanceUpdateStatus}`);

                // Optionally, you can reload the page or perform other actions
                location.reload();
            })
            .catch(error => console.error('Error:', error));
            
        }



        document.addEventListener('DOMContentLoaded', function () {
            const transactionCategory = document.getElementById('transactioncategory');
            const balanceCategory = document.getElementById('balancecategory');
            const transactionCheckboxContainer = document.getElementById('transactionCheckboxContainer');
            const balanceCheckboxContainer = document.getElementById('balanceCheckboxContainer');
            const updateTransactionCheckbox = document.getElementById('updateTransactionCheckbox');
            const updateBalanceCheckbox = document.getElementById('updateBalanceCheckbox');

            // Function to show/hide the respective checkbox based on changes
            function toggleTransactionCheckbox() {
                if (transactionCategory.value) {
                    transactionCheckboxContainer.style.display = 'block';
                    updateTransactionCheckbox.checked = true; // Check the checkbox
                } else {
                    transactionCheckboxContainer.style.display = 'none';
                    updateTransactionCheckbox.checked = false; // Uncheck if no value
                    
                }
            }

            function toggleBalanceCheckbox() {
                if (balanceCategory.value) {
                    balanceWarningMessage.style.display = 'block'; // Show warning message
                    updateBalanceCheckbox.value = 'on'; // Set hidden input value to "on"
                } else {
                    balanceWarningMessage.style.display = 'none'; // Hide warning message
                    updateBalanceCheckbox.value = 'off'; // Reset hidden input value
                }
            }

            // Add event listeners for change events
            transactionCategory.addEventListener('change', toggleTransactionCheckbox);
            balanceCategory.addEventListener('change', toggleBalanceCheckbox);

            // Initialize tooltips for info icons
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });




    </script>
</body>
</html>
