<?php

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

// PHP: Fetch data from API using cURL
$url = "http://127.0.0.1:8000/journal_header";
$journal_id = isset($_GET['journal_id']) ? $_GET['journal_id'] : '';

// Prepare the query parameters
$params = [
    "abid" => $abid,
    "journal_id" => $journal_id
];

$query = http_build_query($params);
$endpoint = "$url?$query";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute and get the response
$response = curl_exec($ch);
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);


// Output JavaScript code with the PHP data
echo "<script>";
// echo "console.log(" . json_encode($data) . ");";
echo "console.log(JSON.stringify(" . json_encode($data) . ", null, 2));";
echo "</script>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDIT payments & receipts with AJAX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif;font-size: 10px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="date"], select { border-radius: 5px;font-size: 10px;width: 100%; padding: 8px; box-sizing: border-box; }
        button {
            padding: 10px 20px;
            margin: 5px;
            border: 2px solid #ccc;
            border-radius: 5px;
            background-color: white;
            color: black;
            font-size: 10px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }
        button:hover {border-color: #28a745;color: #black;}
        .hidden { display: none; }
        .calendar-icon { cursor: pointer; margin-left: -30px; }
        .dropdown-filter { position: relative; }
        .filter-input { width: calc(100% - 30px); padding-right: 30px; }
        .dropdown-list { border-radius: 5px;position: absolute; z-index: 1000; background-color: white; border: 1px solid #ccc; width: 100%; max-height: 150px; overflow-y: auto; display: none; }
        .dropdown-list div { padding: 8px; cursor: pointer; }
        .dropdown-list div:hover { background-color: #f0f0f0; }
        .no-arrows::-webkit-outer-spin-button,
        .no-arrows::-webkit-inner-spin-button {
        -webkit-appearance: none; 
        margin: 0; 
        }
        /* Selected button style */
        .selected {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        .input-flex-container {
            display: flex; /* Use flexbox layout */
            align-items: center; /* Align items vertically centered */

        }

        .input-flex-container input[type="number"],
        .input-flex-container .filter-input {
            margin-right: 10px; /* Space between inputs */
            flex: 1; /* Allow inputs to grow and take available space */
        }

        .dropdown-filter {
            flex: 1; /* Ensure dropdown filter takes available space */
            
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            
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

        input {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
            border-radius: 5px;
            font-size: 10px;
        }

        /* .dropdown-filter {
            display: flex;
            flex-direction: column;
        } */

        /* .dropdown-list {
            max-height: 580px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            display: none;
        } */

        .filter-input {
            width: 90%;
            padding: 5px;
            box-sizing: border-box;
        }
        .form-group-checkbox {
            display: flex; /* Use flexbox layout */
            align-items: center; /* Align items vertically centered */
        }

        .form-group-checkbox label  {
            margin: 10; /* Remove margin from label */
            margin-bottom: 15px;
            display: flex; /* Ensure label is a flex container */
            align-items: center; /* Center items vertically */
            white-space: nowrap; /* Prevent text wrapping */
        }
        .grayed-out {
            opacity: 0.5; /* Makes the table appear greyed out */
            pointer-events: none; /* Disables interactions with the table */
        }
        /* CSS to left-align the table caption */
        #Tablecaption {
            text-align: left; /* Aligns caption text to the left */
            font-size: 10px; /* Increase font size */
            font-weight: bold; /* Make the text bold */
            margin-bottom: 15px;
        }
        /* Hide hidden columns on page load */
        .hidden-column {
            display: none;
        }
        /* Style when hidden columns are visible */
        .hidden-column-visible {
            display: table-cell; /* Makes it visible */
            background-color: #9ef7df; /* Light blue background */
            font-style: italic; /* Italic font */
            color: #333; /* Dark gray text */
            /* font-weight: bold;  */
        }
        /* Apply globally to all number inputs */
        input[type="number"] {
            -moz-appearance: textfield; /* For Firefox */
            -webkit-appearance: none;   /* For Chrome, Safari, Edge */
            appearance: none;           /* Standard property */
        }

        /* Additional for Chrome/Safari to remove spin buttons */
        input[type="number"]::-webkit-inner-spin-button, 
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>EDIT payments & receipts record</h2>
    <form id="transactionForm">
        <input type="hidden" name="transaction_type" id="transaction_type" value="bill">
        <input type="hidden" name="source" id="source" value="manual">
        <input type="hidden" name="abid" id="abid">

        <div class="form-group">
            <label for="posted_date">Posting Date</label>
            <input type="date" name="posted_date" id="posted_date" required>
            <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
        </div>
        <div id="type" class="form-group">
                <label>Categorise to?</label>
                <button type="button" id="vendor">Vendor</button>
                <button type="button" id="others" >others</button>
                <button type="button" id="customer" >Customer</button>
        </div>

        <div id="vendorSection" class="form-group" style="display: none;">
            <label for="cptyname">Vendor Name</label>
            <div class="dropdown-filter">
                <input type="text" id="cptyname_input" class="filter-input" placeholder="Search..." required>
                <div id="cptyname_list" class="dropdown-list"></div>
            </div>
            <input type="hidden" name="cptyname" id="cptyname" >
            <input type="hidden" name="cptyid" id="cptyid" >
            <input type="hidden" name="cpty_gstin" id="cpty_gstin" >
            <input type="hidden" name="pan" id="pan" >
        </div>
        <div class="form-group">
            <input type="hidden" name="bankgl" id="bankgl" required>
        </div>
        <div class="form-group">
            <!-- <label for="defaulttradegl">defaulttradegl</label> -->
            <input type="hidden" name="offsetgl" id="offsetgl" >
        </div>

        <div id="categorySection" class="form-group" style="display: none;">
            <label for="offsetgl">Category</label>
            <div class="dropdown-filter">
                <input type="text" id="offsetgl_input" class="filter-input" placeholder="Search...">
                <div id="offsetgl_list" class="dropdown-list"></div>
            </div>
            <input type="hidden" name="offsetgl" id="offsetgl" >
        </div>

        <div class="form-group">
            <label for="journal_description">Journal narration</label>
            <input type="text" name="journal_description" id="journal_description" >
            <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
        </div>

        <div class="form-group">
            <label for="payment_date">Payment Date</label>
            <input type="date" name="payment_date" id="payment_date" required readonly>
            <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
        </div>        


            <div class="form-group">
                <label for="paid_through">Bank account</label>
                <div class="dropdown-filter">
                    <input type="text" id="paid_through_input" class="filter-input" placeholder="Search..." required>
                    <div id="paid_through_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="paid_through" id="paid_through" >
            </div>
            <div class="form-group">
                <label for="paymentref">Payment Ref (UTR no)</label>
                <input type="text" name="paymentref" id="paymentref" required>
            </div>

            <div class="form-group">
                <label for="narration">Payment remarks/narration</label>
                <input type="text" name="narration" id="narration" readonly>
                <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
            </div>   

        <div class="form-group">
            <label for="amount">Amount</label>
            <div class="input-flex-container">
                <input type="number" name="amount" id="amount" required class="no-arrows" step="0.01" readonly>
                <input type="hidden" name="deposit" id="deposit">
                <input type="hidden" name="withdrawal" id="withdrawal">
                <input type="hidden" name="balance" id="balance">
                <input type="hidden" name="journal_id" id="journal_id">
            </div>
        </div>

        <button type="submit">Save</button>




        
        <div id="resultContainer" style="display: none;">
            <h3>Saved Data</h3>
            <table id="resultTable" border="1">
                <thead>
                    <tr>
                        <th>Category/Account</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="resultBody">
                    <!-- Rows will be added here dynamically -->
                </tbody>
            </table>
        </div>
    </form>

    <script>
        function getCookieValue(cookieName, key) {
            const cookies = document.cookie.split('; ');
            const cookie = cookies.find(c => c.startsWith(cookieName + '='));
            if (cookie) {
                const cookieValue = cookie.split('=')[1];
                const keyValuePairs = JSON.parse(decodeURIComponent(cookieValue));
                return keyValuePairs[key] || "Key not found";
            }
            return null;
        }
        //check cookie
        function checkCookie() {
            abid= getCookieValue('userdetails', 'abid');
            if (abid == null) {
                window.location.href = "http://localhost/example/ABlogin.html"; 
            }}

        //   use to check whether logged in or not:
        checkCookie();



        // Set current date as default for posted_date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('posted_date').value = today;

        document.getElementById('abid').value = getCookieValue('userdetails', 'abid');

// ------------------------------------------------------------------------------------------------------------------------------------------------
// Complete list of addEventListener:
 

        function fetchVendorList() {
            const abid = document.getElementById('abid').value;

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "fetch_vendor_for_bill_creation.php?abid=" + abid, true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const vendorData = JSON.parse(xhr.responseText);
                    const cptyNameList = document.getElementById('cptyname_list');
                    cptyNameList.innerHTML = ""; // Clear previous options
                    vendorData.forEach(item => {
                        const optionDiv = document.createElement("div");
                        optionDiv.textContent = `${item.lglNm} (${item.gstin})`;
                        optionDiv.dataset.value = item.vendorid;
                        optionDiv.onclick = function() {
                            // Pass additional vendor data to selectOption
                            selectOption(item.vendorid, `${item.lglNm} (${item.gstin})`, 'cptyid', 'cptyname_input', 'cptyname_list', item);
                        };
                        cptyNameList.appendChild(optionDiv);
                    });
                } else {
                    console.error("Error fetching Vendor List");
                }
            };

            xhr.send();
        }



        function showDropdown(inputField, listID) {
            const dropdownList = document.getElementById(listID);
            console.log(listID);
            dropdownList.style.display = 'block';
            inputField.addEventListener("blur", () => {
                setTimeout(() => dropdownList.style.display = 'none', 100); // Delay to allow click event
            });
        }

        function filterDropdown(searchTerm, listID) {
            const dropdownList = document.getElementById(listID);
            const options = dropdownList.querySelectorAll("div");
            options.forEach(option => {
                if (option.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                    option.style.display = "block";
                } else {
                    option.style.display = "none";
                }
            });
        }

        function selectOption(value, text, hiddenFieldID, inputFieldID, dropdownID, vendorData) {
            // Set values for the hidden fields based on the selected vendor data
            document.getElementById(hiddenFieldID).value = value; // This is for cptyname
            document.getElementById(inputFieldID).value = text; // This is for display in the input field

            // Populate additional hidden fields
            if (vendorData) {
                document.getElementById('cptyname').value = vendorData.lglNm; // For cptyid
                document.getElementById('cpty_gstin').value = vendorData.gstin; // For cpty_gstin
                document.getElementById('offsetgl').value = vendorData.defaultbalancegl; // For cpty_gstin
                document.getElementById('offsetgl_input').value = glEntryMap[vendorData.defaultbalancegl].gl_name+" || ("+glEntryMap[vendorData.defaultbalancegl].gl_nature+")"; // For cpty_gstin
                document.getElementById('pan').value = vendorData.pan; // For cpty_gstin
    



            }
       

        }



        let glDataCache = []; // Variable to store fetched GL data
        let defaultglEntryMap = {} //used further for is_itc_available event listener
        let glEntryMap = {} //used further for is_itc_available event listener

        // Fetch GL Master List on page load
        async function fetchGLMasterList() {
            return new Promise((resolve, reject) => {
                const abid = document.getElementById('abid').value;

                const xhr = new XMLHttpRequest();
                xhr.open("GET", "fetch_gl_for_bill_creation.php?abid=" + abid, true);

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        glDataCache = JSON.parse(xhr.responseText); // Store fetched data in cache
                        
                        populateDropdowns(); // Populate dropdowns after fetching
                        
                        assignDefaultGLValues(); // Call function to assign default 
                    } else {
                        console.error("Error fetching GL Master List");
                    }
                };

                xhr.send();
            });
        }


        // Assign default GL values based on glDataCache
        function assignDefaultGLValues() {
            // Create a mapping of default_gl_id to GL entries
            defaultglEntryMap = glDataCache.reduce((map, item) => {
                map[item.default_gl_id] = item;
                return map;
            }, {});
            glEntryMap = glDataCache.reduce((map, item) => {
                map[item.gl_id] = item;
                return map;
            }, {});
            const jsonResponse = <?php echo json_encode($data, JSON_PRETTY_PRINT); ?>;
            document.getElementById('paid_through_input').value = glEntryMap[jsonResponse.bankgl].gl_name+" || ("+glEntryMap[jsonResponse.bankgl].gl_nature+")" || '';
            document.getElementById('offsetgl_input').value = glEntryMap[jsonResponse.offsetgl].gl_name+" || ("+glEntryMap[jsonResponse.offsetgl].gl_nature+")" || '';

            const resultBody = document.getElementById('resultBody');
                resultBody.innerHTML = ''; // Clear previous results

                jsonResponse.lines.forEach(line => {
                    const row = document.createElement('tr');
                    const categoryCell = document.createElement('td');
                    const amountCell = document.createElement('td');

                    // Create category/account entry
                    categoryCell.textContent = `${glEntryMap[line.gl_id].gl_name} || (${glEntryMap[line.gl_id].gl_nature})`;
                    amountCell.textContent = line.amount.toFixed(2); // Format amount to two decimal places

                    row.appendChild(categoryCell);
                    row.appendChild(amountCell);
                    resultBody.appendChild(row);
                });

                // Show the result container
                document.getElementById('resultContainer').style.display = 'block';
        }
        





        // Function to populate the dropdown for a specific row
        function populateCategoryDropdown(rowCount) {
            const listElement = document.getElementById(`category_list_${rowCount}`);
            
            // Clear previous options
            // listElement.innerHTML = "";

            glDataCache.forEach(item => {
                const optionDiv = document.createElement("div");
                optionDiv.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDiv.dataset.value = item.gl_id;

                optionDiv.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", `category_hidden_${rowCount}`, `category_input_${rowCount}`, `category_list_${rowCount}`);
                };

                listElement.appendChild(optionDiv);
            });
        }

        

        // Populate dropdowns with cached data
        function populateDropdowns() {
            const paidThroughList = document.getElementById('paid_through_list');
            const offsetglList = document.getElementById('offsetgl_list');

            // Clear previous options
            paidThroughList.innerHTML = "";

            glDataCache.forEach(item => {
                const optionDivPaidThrough = document.createElement("div");
                optionDivPaidThrough.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivPaidThrough.dataset.value = item.gl_id;
                optionDivPaidThrough.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'paid_through', 'paid_through_input', 'paid_through_list');
                };
                paidThroughList.appendChild(optionDivPaidThrough);

            });

            // Clear previous options
            offsetglList.innerHTML = "";

            glDataCache.forEach(item => {
                const optionDivoffsetgl = document.createElement("div");
                optionDivoffsetgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivoffsetgl.dataset.value = item.gl_id;
                optionDivoffsetgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'offsetgl', 'offsetgl_input', 'offsetgl_list');
                };
                offsetglList.appendChild(optionDivoffsetgl);

            });
        }

        // Call fetchGLMasterList when the page loads
        document.addEventListener("DOMContentLoaded", fetchGLMasterList);


        // // Function to set up input event listeners for dropdowns
        // function setupDropdownListeners() {
        //     const dropdownIds = [
        //         'paid_through',
        //         'assvalgl',
        //         'cgstvalgl',
        //         'sgstvalgl',
        //         'igstvalgl',
        //         'cessvalgl',
        //         'rndoffamtgl',
        //         'othchrggl'
        //     ];

        //     dropdownIds.forEach(dropdownId => {
        //         const inputElement = document.getElementById(`${dropdownId}_input`);

        //         inputElement.onfocus = function() {
        //             showDropdown(this, `${dropdownId}_list`);
        //         };

        //         inputElement.addEventListener('input', function() {
        //             filterDropdown(this.value, `${dropdownId}_list`);
        //         });
        //     });
        // }

        // // Call the functions to populate dropdowns and set up listeners

        // setupDropdownListeners();
            // Show dropdown for Paid Through input field
            // Fetch Vendor List when the dropdown is clicked

            const cptyNameInput = document.getElementById('cptyname_input');
            cptyNameInput.onfocus = function() {
                fetchVendorList();
                showDropdown(cptyNameInput, 'cptyname_list');
            };

            cptyNameInput.addEventListener('input', function() {
                filterDropdown(this.value, 'cptyname_list');
            });

            document.getElementById('paid_through_input').onfocus = function() {
                showDropdown(this, 'paid_through_list');
            };
            // Filter dropdowns based on user input
            document.getElementById('paid_through_input').addEventListener('input', function() {
                filterDropdown(this.value, 'paid_through_list');
            });

            document.getElementById('offsetgl_input').onfocus = function() {
                showDropdown(this, 'offsetgl_list');
            };
            // Filter dropdowns based on user input
            document.getElementById('offsetgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'offsetgl_list');
            });            
         


        // document.getElementById('is_itemize').addEventListener('change', function() {
        //     const itemTable = document.getElementById('itemTable');
        //     const totalTable = document.getElementById('totalTable');
        //     const itemTableContainer = document.getElementById('itemTablecontainer');
        //     const categoryInputs = document.querySelectorAll('.category_input');
        //     const source = document.getElementById('source').value;
            
        //     if (this.checked) {
        //         totalTable.classList.add('grayed-out'); // Add class to grey out
        //         itemTableContainer.style.display = 'block'; // Show the item 

        //         if (source == "einvoice") {
        //             einvoice_itemize()
        //         }else{
        //             // Set 'required' attribute for each category input
        //             categoryInputs.forEach(input => {
        //                 input.setAttribute('required', 'required'); // Set required attribute
        //             });
        //             populateCategoryDropdown(1);
        //         }

        //     } else {
        //         totalTable.classList.remove('grayed-out'); // Remove class to restore
        //         itemTableContainer.style.display = 'none'; // Hide the item table
        //         // Remove 'required' attribute for each category input
        //         categoryInputs.forEach(input => {
        //             input.removeAttribute('required'); // Remove required attribute
        //         });
        //     }
        // });


      document.getElementById('transactionForm').onsubmit = function(event) {
          event.preventDefault();

          

          const jsonData = {
              transaction_type: document.getElementById('transaction_type').value || "",
              source: document.getElementById('source').value || "",
              abid: parseInt(document.getElementById('abid').value) || "",
              posted_date: document.getElementById('posted_date').value || "",
              
              journal_description: document.getElementById('journal_description').value || "",
              
              offsetgl: parseInt(document.getElementById('offsetgl').value) || '',
              bankgl: parseInt(document.getElementById('paid_through').value) || '',    
              narration: document.getElementById('narration').value ||'',
              journal_id: parseInt(document.getElementById('journal_id').value) ||'',
          };



            // Check which button is selected
            let selectedButton = document.querySelector('#type button.selected');
            
            if (selectedButton) {
                let selectedValue = selectedButton.id; // Get the id of the selected button
                
                // Perform actions based on the selected button
                switch (selectedValue) {
                    case 'vendor':
                        // Logic for saving vendor data
                        console.log('Vendor button is selected.');
                        jsonData.cptyname = document.getElementById('cptyname').value || "";
                        jsonData.cptyid = parseInt(document.getElementById('cptyid').value) || 0;  
                        jsonData.cpty_gstin = document.getElementById('cpty_gstin').value || "";
                        jsonData.pan = document.getElementById('pan').value || ""; 
                        break;
                    case 'others':
                        // Logic for saving other data
                        console.log('Others button is selected.');
                        break;
                    case 'customer':
                        // Logic for saving customer data
                        console.log('Customer button is selected.');
                        break;
                    default:
                        console.log('No valid selection.');
                }
            } else {
                alert('Please select a category before saving.');
            }
        
              jsonData.paid_through = parseInt(document.getElementById('paid_through').value) || 0;
              jsonData.paymentref = document.getElementById('paymentref').value || "";
              jsonData.payment_date = document.getElementById('payment_date').value || "";
            


              jsonData.withdrawal = parseFloat(document.getElementById('withdrawal').value) || 0;  
              jsonData.deposit = parseFloat(document.getElementById('deposit').value) || 0;   
              jsonData.balance = parseFloat(document.getElementById('balance').value) || 0;  




        //   console.log(JSON.stringify(jsonData));

        // Function to generate lines with summed amounts for same GL IDs
        function generateLines(data) {
            const linesMap = {};
            const glMappings = [];
            if (jsonData.transaction_type == "payment"){ 
                glMappings1 = [
                    { gl_id: data.bankgl, amount: -data.withdrawal, description: "bank debit" },
                    { gl_id: data.offsetgl, amount: data.withdrawal, description: "bank debit" }
                ];      
                glMappings.push(...glMappings1);// Append each element of rcm_lines to glMappings
            } else if (jsonData.transaction_type == "receipt"){
                glMappings1 = [
                    { gl_id: data.bankgl, amount: data.deposit, description: "bank credit" },
                    { gl_id: data.offsetgl, amount: -data.deposit, description: "bank credit" }
                ];  
                glMappings.push(...glMappings1);// Append each element of rcm_lines to glMappings
            }
            // Aggregate amounts by GL ID
            glMappings.forEach(mapping => {
                if (mapping.amount !== 0) { // Only consider non-zero amounts
                    if (!linesMap[mapping.gl_id]) {
                        // If this GL ID hasn't been added yet, initialize it
                        linesMap[mapping.gl_id] = {
                            gl_id: mapping.gl_id,
                            amount: mapping.amount,
                            description: mapping.description,
                            is_reversal_entry: "N", // Default value (can be updated as needed)
                            profit_center: null, // Default value (can be updated as needed)
                            cost_center: null, // Default value (can be updated as needed)
                            projectid: null, // Default value (can be updated as needed)
                            project_name: null, // Default value (can be updated as needed)
                            journalline_tag1: null, // Default value (can be updated as needed)
                            journalline_tag2: null, // Default value (can be updated as needed)
                            journalline_tag3: null // Default value (can be updated as needed)
                        };
                    } else {
                        // If this GL ID already exists, sum the amounts
                        linesMap[mapping.gl_id].amount += mapping.amount;
                        // linesMap[mapping.gl_id].description += ', ' + mapping.description;
                    }
                }
            });
            data.lines =Object.values(linesMap);
            return data;
        
        }

        // Generate the lines
        const payload = generateLines(jsonData);

        // Log the result
        console.log(JSON.stringify(payload));

          // Use AJAX to send data to server using PHP cURL
          const xhrSubmit = new XMLHttpRequest();
          xhrSubmit.open("POST", "edit_journal_bank.php", true);
          xhrSubmit.setRequestHeader("Content-Type", "application/json");



        xhrSubmit.onload = function() {
                // Parse the JSON response safely
        const response = JSON.parse(xhrSubmit.responseText);
            if (response.status_code === 200) {
                alert("Data saved successfully!");
                // Populate the result table
                const resultBody = document.getElementById('resultBody');
                resultBody.innerHTML = ''; // Clear previous results

                payload.lines.forEach(line => {
                    const row = document.createElement('tr');
                    const categoryCell = document.createElement('td');
                    const amountCell = document.createElement('td');

                    // Create category/account entry
                    categoryCell.textContent = `${glEntryMap[line.gl_id].gl_name} || (${glEntryMap[line.gl_id].gl_nature})`;
                    amountCell.textContent = line.amount.toFixed(2); // Format amount to two decimal places

                    row.appendChild(categoryCell);
                    row.appendChild(amountCell);
                    resultBody.appendChild(row);
                });

                // Show the result container
                document.getElementById('resultContainer').style.display = 'block';

            } else if (response.status_code === 403) {
                alert("Document with provided ref w.r.t this cpty already exist");
                // Populate the result table
                const resultBody = document.getElementById('resultBody');
                resultBody.innerHTML = ''; // Clear previous results

                payload.lines.forEach(line => {
                    const row = document.createElement('tr');
                    const categoryCell = document.createElement('td');
                    const amountCell = document.createElement('td');

                    // Create category/account entry
                    categoryCell.textContent = `${glEntryMap[line.gl_id].gl_name} || (${glEntryMap[line.gl_id].gl_nature})`;
                    amountCell.textContent = line.amount.toFixed(2); // Format amount to two decimal places

                    row.appendChild(categoryCell);
                    row.appendChild(amountCell);
                    resultBody.appendChild(row);
                });

                // Show the result container
                document.getElementById('resultContainer').style.display = 'block';

            }else {
                alert("Error saving data.");
                    
                // // Populate the result table
                // const resultBody = document.getElementById('resultBody');
                // resultBody.innerHTML = ''; // Clear previous results

                // payload.lines.forEach(line => {
                //     const row = document.createElement('tr');
                //     const categoryCell = document.createElement('td');
                //     const amountCell = document.createElement('td');

                //     // Create category/account entry
                //     categoryCell.textContent = `${glEntryMap[line.gl_id].gl_name} || (${glEntryMap[line.gl_id].gl_nature})`;
                //     amountCell.textContent = line.amount.toFixed(0); // Format amount to two decimal places

                //     row.appendChild(categoryCell);
                //     row.appendChild(amountCell);
                //     resultBody.appendChild(row);
                // });

                // // Show the result container
                // document.getElementById('resultContainer').style.display = 'block';
            }
        };
          
        xhrSubmit.send(JSON.stringify(jsonData));
    };



const jsonResponse = <?php echo json_encode($data, JSON_PRETTY_PRINT); ?>;

// Add an event listener for when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Assign values from the JSON response to form elements
    // document.getElementById('abid').value = jsonResponse.abid;

    // Function to show the vendor section if cptyid is available
    function checkCptyid() {
        if (jsonResponse.cptyid) {
            document.getElementById('vendorSection').style.display = 'block';
            document.getElementById('cptyid').value = jsonResponse.cptyid; // Example of setting value
            // Add any other necessary logic to populate vendor details here
            document.getElementById('vendor').classList.add('selected'); // Highlight Vendor button
        } else {//later add elif for customer button
            document.getElementById('vendorSection').style.display = 'none';
            document.getElementById('others').classList.add('selected'); // Highlight Vendor button
            document.getElementById('categorySection').style.display = 'block';
        }
    }

    // Event listeners for buttons
    document.getElementById('vendor').addEventListener('click', function () {
        document.getElementById('vendorSection').style.display = 'block';
        document.getElementById('categorySection').style.display = 'none';
        this.classList.add('selected');
        document.getElementById('others').classList.remove('selected');
        document.getElementById('customer').classList.remove('selected');
    });

    document.getElementById('others').addEventListener('click', function () {
        document.getElementById('vendorSection').style.display = 'none';
        document.getElementById('categorySection').style.display = 'block';
        this.classList.add('selected');
        document.getElementById('vendor').classList.remove('selected');
        document.getElementById('customer').classList.remove('selected');
    });

    document.getElementById('customer').addEventListener('click', function () {
        // Handle customer button click if needed
        this.classList.add('selected');
        document.getElementById('vendor').classList.remove('selected');
        document.getElementById('others').classList.remove('selected');
    });

    // Check cptyid on page load
    checkCptyid();


    document.getElementById('posted_date').value = jsonResponse.posted_date;
    document.getElementById('source').value = jsonResponse.source;
    document.getElementById('transaction_type').value = jsonResponse.transaction_type;

    document.getElementById('amount').value = jsonResponse.withdrawal+jsonResponse.deposit;
    document.getElementById('deposit').value = jsonResponse.deposit;
    document.getElementById('withdrawal').value = jsonResponse.withdrawal;
    document.getElementById('balance').value = jsonResponse.balance;
    document.getElementById('journal_id').value = jsonResponse.journal_id;

        // document.getElementById('paid_through_input').setAttribute('required', 'required');
        // document.getElementById('paymentref').setAttribute('required', 'required');
        // document.getElementById('payment_date').setAttribute('required', 'required');

        // Remove required attributes for non-cash purchase fields
        // document.getElementById('cptyname').removeAttribute('required');


        // Assign cash purchase specific values if available


        document.getElementById('narration').value = jsonResponse.narration || '';
        document.getElementById('paid_through').value =  jsonResponse.bankgl || '';
        document.getElementById('paymentref').value = jsonResponse.paymentref || '';
        document.getElementById('payment_date').value = jsonResponse.payment_date || '';
        document.getElementById('offsetgl').value =  jsonResponse.offsetgl || '';






        // Assign vendor information if available
        document.getElementById('cptyname_input').value = jsonResponse.cptyname+" ("+jsonResponse.cpty_gstin+")" || '';
        document.getElementById('cptyname').value = jsonResponse.cptyname || '';
        document.getElementById('cptyid').value = jsonResponse.cptyid || '';
        document.getElementById('cpty_gstin').value = jsonResponse.cpty_gstin || '';
        document.getElementById('pan').value = jsonResponse.pan || '';


    // Assign other form values

    document.getElementById('journal_description').value = jsonResponse.journal_description;



    
    // // Populate the checkbox based on is_input_availed value
    // if (jsonResponse.is_input_availed === "Y") {
    //     document.getElementById('is_input_availed').checked = true; // Check the checkbox
    // } else {
    //     document.getElementById('is_input_availed').checked = false; // Uncheck the checkbox
    // }

    // if (jsonResponse.is_rev_charge_2b_or_einv === "Y") {
    //     document.getElementById('is_rev_charge_2b_or_einv').checked = true; // Check the checkbox
    // } else {
    //     document.getElementById('is_rev_charge_2b_or_einv').checked = false; // Uncheck the checkbox
    // }

    // if (jsonResponse.is_itemize === "Y") {
    //     document.getElementById('is_itemize').checked = true; // Check the checkbox
    // } else {
    //     document.getElementById('is_itemize').checked = false; // Uncheck the checkbox
    // }




    document.getElementById('offsetgl').value = jsonResponse.offsetgl;
    document.getElementById('bankgl').value = jsonResponse.bankgl;



});



    </script>
</div>

</body>
</html>
