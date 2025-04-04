<!DOCTYPE html>
<html>
<head>
    <title>Customer List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .customer-card {
            background: linear-gradient(to bottom, #ffffff, #e6f7ff);
            border: none;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .customer-card:hover {
            transform: scale(1.05);
        }
        .customer-title {
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0 text-center">Customer List</h1>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                <i class="bi bi-plus-circle"></i> Add Customer
            </button>
        </div>

        <div class="grid-container">
            <?php
            // $abid = 1000000000; // Replace with the desired item ID
            $url = "http://127.0.0.1:8000/customerlist?abid=" . $abid;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode == 200) {
                $data = json_decode($response, true);
            } elseif ($httpCode == 404) {
                echo "<p class='alert alert-warning'>No customers found.</p>";
            } else {
                echo "<p class='alert alert-danger'>Error fetching customer data. HTTP Status Code: " . $httpCode . "</p>";
            }

            curl_close($ch);

            if ($httpCode == 200) {
                foreach ($data as $customer) {
                    $lglNmDecoded = json_decode('"' . $customer['lglNm'] . '"'); // Decoding Unicode characters
            ?>
                <div class="customer-card" onclick="showcustomerDetails(<?php echo htmlspecialchars(json_encode($customer)); ?>)">
                    <div>
                        <h5 class="customer-title"><?php echo htmlspecialchars($customer['lglNm']); ?></h5>
                        <p class="text-muted mb-1">customer ID: <?php echo htmlspecialchars($customer['customerid']); ?></p>
                        <p class="text-muted mb-1">GSTIN: <?php echo htmlspecialchars($customer['gstin']); ?></p>
                        <?php
                        // Use GL Map for lookup
                        // $tradeGlName = isset($glMap[$customer['defaulttradegl']]) ? $glMap[$customer['defaulttradegl']] : '';
                        $balanceGlName = isset($glMap[$customer['defaultbalancegl']]) ? $glMap[$customer['defaultbalancegl']] : '';
                        ?>
                        <p class="text-primary"><?php echo $balanceGlName; ?></p>
                    </div>
                </div>
            <?php
                }
            }
            ?>
        </div>
    </div>

    <!-- Modal for customer Details (Edit Existing Customer) -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalLabel">customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="customerForm">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">customer Name</label>
                            <input type="text" class="form-control" id="customerName" name="lglNm">
                        </div>
                        <div class="mb-3">
                            <label for="customerId" class="form-label">customer ID</label>
                            <input type="text" class="form-control" id="customerId" name="customerid" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="gstin" class="form-label">GSTIN</label>
                            <input type="text" class="form-control" id="gstin" name="gstin">
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
                        <button type="button" class="btn btn-primary" onclick="savecustomerDetails()">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

     <!-- Modal for Create New customer -->
    <div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCustomerModalLabel">Create New customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createCustomerForm">
                        <div class="mb-3">
                            <label for="newcustomerName" class="form-label">customer Name</label>
                            <input type="text" class="form-control" id="newcustomerName" name="lglNm" required>
                        </div>
                        <div class="mb-3">
                            <label for="newgstin" class="form-label">GSTIN</label>
                            <input type="text" class="form-control" id="newgstin" name="gstin" required>
                            <button type="button" class="btn btn-sm btn-primary" onclick="fetchGstData()">Fetch Data</button>
                        </div>
                        <div class="mb-3">
                            <label for="txptype" class="form-label">Taxpayer Type</label>
                            <!-- <input type="text" class="form-control" id="txptype" name="txptype" required> -->
                            <select name="txptype" id="txptype" required>
                                <option value="REG" >Registered - Regular</option>
                                <option value="UNREG" selected>Unregistered</option>
                                <option value="SEZ">SEZ</option>
                                <option value="COM">Registered - Composition</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="newbalancecategory" class="form-label">Balance Category</label>
                            <select class="form-select" id="newbalancecategory" name="defaultbalancegl" required>
                                <!-- <option value="">Select Balance Category</option> -->
                                <?php foreach ($glData as $gl): ?>
                                    <?php if ($gl['gl_nature'] === 'asset' || $gl['gl_nature'] === 'liability'): ?>
                                        <option value="<?php echo htmlspecialchars($gl['gl_id']); ?>">
                                            <?php echo htmlspecialchars($gl['gl_name']) . ' (' . htmlspecialchars($gl['gl_nature']) . ')'; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="newpan" class="form-label">PAN</label>
                                <input type="text" class="form-control" id="newpan" name="pan">
                            </div>
                            <div class="mb-3">
                                <label for="newtdssection" class="form-label">TDS section</label>
                                <input type="text" class="form-control" id="newtdssection" name="tdssection">
                                <label for="newtdsrate" class="form-label">TDS Rate</label>
                                <input type="text" class="form-control" id="newtdsrate" name="tdsrate">
                            </div>
                            <div class="mb-3">
                                <label for="newaddr1" class="form-label">Address line 1</label>
                                <input type="text" class="form-control" id="newaddr1" name="addr1">
                            </div>
                            <div class="mb-3">
                                <label for="newaddr2" class="form-label">Address line 2</label>
                                <input type="text" class="form-control" id="newaddr2" name="addr2">
                            </div>
                            <div class="mb-3">
                                <label for="newaddr3" class="form-label">Address line 3</label>
                                <input type="text" class="form-control" id="newaddr3" name="addr3">
                            </div>
                            <div class="mb-3">
                                <label for="newloc" class="form-label">City</label>
                                <input type="text" class="form-control" id="newloc" name="loc">
                            </div>
                            <div class="mb-3">
                                <label for="newpin" class="form-label">PIN/ZIP code</label>
                                <input type="text" class="form-control" id="newpin" name="pin">
                            </div>
                            <div class="mb-3">
                                <label for="newstcd" class="form-label">State code</label>
                                <input type="text" class="form-control" id="newstcd" name="stcd">
                            </div>
                            <div class="mb-3">
                                <label for="newemail" class="form-label">Email</label>
                                <input type="text" class="form-control" id="newemail" name="email">
                            </div>
                            <div class="mb-3">
                                <label for="newmobile" class="form-label">Mobile number</label>
                                <input type="text" class="form-control" id="newmobile" name="mobile">
                            </div>
                            <div class="mb-3">
                                <label for="newphone" class="form-label">Phone number</label>
                                <input type="text" class="form-control" id="newphone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="newbankac" class="form-label">Bank account number</label>
                                <input type="text" class="form-control" id="newbankac" name="bankac">
                            </div>
                            <div class="mb-3">
                                <label for="newifsc" class="form-label">IFSC code</label>
                                <input type="text" class="form-control" id="newifsc" name="ifsc">
                            </div>
                        <button type="button" class="btn btn-success" onclick="createcustomerDetails()">Create</button>
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
        let abid = getCookieValue('userdetails', 'abid');
        if (abid==="Key not found"){
                window.location.href = "http://localhost/example/ablogin.html"; // Redirect
            }


            function fetchGstData() {
                const gstin = document.getElementById('newgstin').value;
                abid = parseInt(abid); // Assuming abid is defined

                fetch('fetchgstdata.php?gstin=' + gstin + '&abid=' + abid)
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data); // Log the response here

                    if (data && data.data) {
                        document.getElementById('newcustomerName').value = data.data.LegalName;
                        document.getElementById('txptype').value = data.data.TxpType;
                        document.getElementById('newaddr1').value = data.data.AddrBno + ' ' + data.data.AddrBnm + ' ' + data.data.AddrFlno;
                        document.getElementById('newaddr2').value = data.data.AddrSt;
                        document.getElementById('newloc').value = data.data.AddrLoc;
                        document.getElementById('newpin').value = data.data.AddrPncd;
                    } else {
                        alert('Failed to fetch GST data.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to fetch GST data.');
                });
            }



        function showcustomerDetails(customer) {
            document.getElementById('customerName').value = customer.lglNm;
            document.getElementById('customerId').value = customer.customerid;
            document.getElementById('gstin').value = customer.gstin;
            document.getElementById('pan').value = customer.pan;
            document.getElementById('tdssection').value = customer.tdssection;
            document.getElementById('tdsrate').value = customer.tdsrate;
            document.getElementById('addr1').value = customer.addr1;
            document.getElementById('addr2').value = customer.addr2;
            document.getElementById('addr3').value = customer.addr3;
            document.getElementById('loc').value = customer.loc;
            document.getElementById('pin').value = customer.pin;
            document.getElementById('stcd').value = customer.stcd;
            document.getElementById('email').value = customer.email;
            document.getElementById('mobile').value = customer.mobile;
            document.getElementById('phone').value = customer.phone;
            document.getElementById('bankac').value = customer.bankac;
            document.getElementById('ifsc').value = customer.ifsc;

            // Set the selected option in the dropdown

            document.getElementById('balancecategory').value = customer.defaultbalancegl;

            //Hidden Old DefaulttradeGL and Balance GLs to check update

            document.getElementById('olddefaultbalancegl').value = customer.defaultbalancegl;

            //If Balance Category is changed Show and hide warnings.
            if (customer.defaultbalancegl!=null){
            document.getElementById('updateBalanceCheckbox').value = "on";
            document.getElementById('balanceWarningMessage').style.display = "block";
            }else{
            document.getElementById('updateBalanceCheckbox').value = "off";
            document.getElementById('balanceWarningMessage').style.display = "none";
            }
        


            var customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
            customerModal.show();
        }
        function savecustomerDetails() {
            const form = document.getElementById('customerForm');
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
                olddefualtbalancegl:  data.olddefaultbalancegl ? parseInt(data.olddefaultbalancegl) : null,
            };



            // Call the PHP proxy script using PUT

            fetch('updateCustomer.php', {
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
        // function savecustomerDetails() {
        //     // Collect data from the form
        //     var formData = new FormData(document.getElementById('customerForm'));

        //     // Add ABID to the form data
        //     formData.append('abid', parseInt(abid));

        //      // Convert the FormData to a JSON object
        //     const formDataJson = {};
        //         formData.forEach((value, key) => {
        //         formDataJson[key] = value;
        //         });
        //     // Make AJAX request
        //     fetch('http://127.0.0.1:8000/customerlist', {
        //         method: 'PUT', // or 'POST' depending on your API
        //         body: JSON.stringify(formDataJson),  // Send as JSON
        //         headers: {
        //             'Content-Type': 'application/json'  // Specify content type
        //         }
        //     })
        //     .then(response => response.json())
        //     .then(data => {
        //         // Handle response from the server
        //         console.log('Success:', data);
        //         alert('customer details saved successfully!');
        //         window.location.reload(); // Refresh the page
        //     })
        //     .catch(error => {
        //         // Handle errors
        //         console.error('Error:', error);
        //         alert('Failed to save customer details.');
        //     });

        //     // Hide the modal
        //     var customerModal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
        //     customerModal.hide();
        // }
        function createcustomerDetails() {
            // Collect data from the form
            var formData = new FormData(document.getElementById('createCustomerForm'));

            // Convert FormData to object
            const data = Object.fromEntries(formData.entries());
            // console.log(data)
            // Parse specific fields to match the expected types in FastAPI
            const formattedData = {
                abid: parseInt(abid), // Convert to integer
                defaultbalancegl: data.defaultbalancegl ? parseInt(data.defaultbalancegl) : null,
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
                lowertdscertno: data.lowertdscertno || null
            };


            // Make AJAX request to the PHP script, not FastAPI directly
            fetch('createcustomer.php?abid=' + abid, {
                method: 'POST',
                body: JSON.stringify(formattedData),
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())  // Parse the response as JSON
            .then(data => {
                // Handle response from the PHP script (which forwards to FastAPI)
                console.log('Success:', data);
                if (data.status_code === 200) {
                    alert('Customer created successfully!');
                    window.location.reload(); // Refresh the page
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                // Handle errors
                console.error('Error:', error);
                alert('Failed to save customer details.');
                window.location.reload(); // Refresh the page
            });

            // Hide the modal if necessary
            var createCustomerModal = bootstrap.Modal.getInstance(document.getElementById('createCustomerModal'));
            createCustomerModal.hide();
        }




        // Prevent form submission on Enter key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });


    </script>

</body>
</html>
