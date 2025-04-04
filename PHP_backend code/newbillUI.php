<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create bill with AJAX</title>
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
    <h2>New bill</h2>
    <form id="transactionForm">
        <input type="hidden" name="transaction_type" id="transaction_type" value="bill">
        <input type="hidden" name="source" id="source" value="manual">
        <input type="hidden" name="abid" id="abid">

        <div class="form-group">
            <label for="posted_date">Posting Date</label>
            <input type="date" name="posted_date" id="posted_date" required>
            <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
        </div>

        <div id="cashPurchaseGroup" class="form-group">
            <label>Is this a cash purchase?</label>
            <button type="button" id="yesButton">Yes</button>
            <button type="button" id="noButton" >No</button>
        </div>

        <div id="cashFields" class="hidden">
            <div class="form-group">
                <label for="paid_through">Paid Through</label>
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
                <label for="payment_date">Payment Date</label>
                <input type="date" name="payment_date" id="payment_date" required>
                <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
            </div>
        </div>

        <div id="nonCashFields" class="hidden">
            <!-- Non-cash purchase fields -->
            <div class="form-group">
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
                <input type="hidden" name="defaultbalancegl" id="defaultbalancegl" required>
            </div>
            <div class="form-group">
                <!-- <label for="defaulttradegl">defaulttradegl</label> -->
                <input type="hidden" name="defaulttradegl" id="defaulttradegl" >
            </div>
            

            <!-- Checkboxes -->
            <div class="form-group-checkbox">
                <label><input type="checkbox" id="is_rev_charge_2b_or_einv" name="is_rev_charge_2b_or_einv"> Is tax payable under reverse charge?</label>
            </div>
            <div class="form-group-checkbox">
                <label><input type="checkbox" id="is_input_availed" name="is_input_availed" checked> Are you availing input tax credit (ITC)?</label>
            </div>

            <div class="form-group">
                <div class='dropdown-filter'>
                    <label for="supplierstate_input">Supplier state</label>
                    <input type='text' id='supplierstate_input' class='filter-input' placeholder='Search state...' onfocus="showDropdown(this, 'supplierstate_list')"required >
                    <div id='supplierstate_list' class='dropdown-list'></div>
                    <input type='hidden' name='supplierstate' id='supplierstate'>
                </div> 
            </div>

            <div class="form-group">
                <div class='dropdown-filter'>
                    <label for="pos_input">Place of supply</label>
                    <input type='text' id='pos_input' class='filter-input' placeholder='Search state...' onfocus="showDropdown(this, 'pos_list')"required>
                    <div id='pos_list' class='dropdown-list'></div>
                    <input type='hidden' name='pos' id='pos'>
                </div> 
            </div>


            <!-- Additional fields for non-cash purchase -->
            <!-- Add other fields here as needed -->
        </div>

        <div class="form-group">
            <label for="ref_no">Doc no/Inv no:<span style="color: red;"> (Case sensitive!)</span></i></label>
            <input type="text" name="ref_no" id="ref_no" required>
        </div>
        <div class="form-group">
            <label for="doc_type">Document type</label>
            <select name="doc_type" id="doc_type" required>
                <option value="INV" selected>Purchase Invoice</option>
                <option value="CRN">Credit Note (purchase return)</option>
                <option value="DBN">Debit Note (some additional expense raised after bill)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="doc_date">Document date</label>
            <input type="date" name="doc_date" id="doc_date" >
            <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
        </div>
        <div class="form-group">
            <label for="irn">IRN</label>
            <input type="text" name="irn" id="irn" >
        </div>
        <div class="form-group">
            <label for="journal_description">Remarks/narration</label>
            <input type="text" name="journal_description" id="journal_description" >
            <!-- <i class="fas fa-calendar-alt calendar-icon"></i> -->
        </div>

        <!-- Checkboxes -->
        <div class="form-group-checkbox">
            <label><input type="checkbox" id="is_itemize" name="is_itemize">Do you want to categorise item wise?</label>
        </div>
<!-- 
        <div class="form-group">
            <label for="assval">Taxable value</label>
            <div class="input-flex-container">
                <input type="number" name="assval" id="assval" required class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="assvalgl_input" class="filter-input" placeholder="Search GL..." required>
                    <div id="assvalgl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="assvalgl" id="assvalgl">
            </div>
        </div>
        <div class="form-group">
            <label for="cgstval">CGST amount</label>
            <div class="input-flex-container">
                <input type="number" name="cgstval" id="cgstval"  class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="cgstvalgl_input" class="filter-input" placeholder="Search GL..." >
                    <div id="cgstvalgl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="cgstvalgl" id="cgstvalgl">
            </div>
        </div>

        <div class="form-group">
            <label for="sgstval">SGST amount</label>
            <div class="input-flex-container">
                <input type="number" name="sgstval" id="sgstval"  class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="sgstvalgl_input" class="filter-input" placeholder="Search GL..." >
                    <div id="sgstvalgl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="sgstvalgl" id="sgstvalgl">
            </div>
        </div>

        <div class="form-group">
            <label for="igstval">IGST amount</label>
            <div class="input-flex-container">
                <input type="number" name="igstval" id="igstval"  class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="igstvalgl_input" class="filter-input" placeholder="Search GL..." >
                    <div id="igstvalgl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="igstvalgl" id="igstvalgl">
            </div>
        </div>

        <div class="form-group">
            <label for="cessval">Cess amount</label>
            <div class="input-flex-container">
                <input type="number" name="cessval" id="cessval" class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="cessvalgl_input" class="filter-input" placeholder="Search GL..." >
                    <div id="cessvalgl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="cessvalgl" id="cessvalgl">
            </div>
        </div>

        <div class="form-group">
            <label for="rndoffamt">Roundoff amount</label>
            <div class="input-flex-container">
                <input type="number" name="rndoffamt" id="rndoffamt" class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="rndoffamtgl_input" class="filter-input" placeholder="Search GL..." >
                    <div id="rndoffamtgl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="rndoffamtgl" id="rndoffamtgl">
            </div>
        </div>
  
        <div class="form-group">
            <label for="othchrg">Other charges<i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="Like delivery charges or reimburesement of expenses which is not included in value of supply."></i></label>
            <div class="input-flex-container">
                <input type="number" name="othchrg" id="othchrg" class="no-arrows" step="0.01">
                <div class="dropdown-filter">
                    <input type="text" id="othchrggl_input" class="filter-input" placeholder="Search GL..." >
                    <div id="othchrggl_list" class="dropdown-list"></div>
                </div>
                <input type="hidden" name="othchrggl" id="othchrggl">
            </div>
        </div>

        <div class="form-group">
            <label for="totinvval">Total invoice value</label>
            <input type="number" name="totinvval" id="totinvval" class="no-arrows"step="0.01" readonly>
        </div> -->

<div class="form-group" id="itemTablecontainer" style="display: none;">
    <table class="form-table" id="itemTable" >
        <caption id="Tablecaption">Item Details: </caption> <!-- Table Title -->
        <thead>
            <tr>
                <th>SlNo</th>
                <th>Item Description</th>
                <th>HSN Code</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Unit Price</th>
                <th>Discount (Rs)</th>
                <th>Taxable Amount (Rs)</th>
                <th>Tax Rate (%)<button type="button" id="toggleColumns" onclick="toggleHiddenColumns()">+</button></th>
                <!-- Hidden Column Headers -->
                <th class="hidden-column">IGST Amt</th>
                <th class="hidden-column">CGST Amt</th>
                <th class="hidden-column">SGST Amt</th>
                <th class="hidden-column">CESS Rate</th>
                <th class="hidden-column">CESS Amt</th>
                <th class="hidden-column">Non-Advl CESS Amt</th>
                <th class="hidden-column">State CESS Rate</th>
                <th class="hidden-column">State CESS Amt</th>
                <th class="hidden-column">State Non-Advl CESS Amt</th>
                <th>Other Charges</th>
                <th>Total (Rs)</th>
                <th>Category</th>
                <!-- <th>
                    <button type="button" id="toggleColumns" onclick="toggleHiddenColumns()">+</button>
                </th> -->

            </tr>
        </thead>
        <tbody>
            <!-- Initial Row -->
            <tr class="item-row">
                <td>
                    <span class="SlNo" >1</span>
                    <button type="button" class="delete-row" onclick="deleteRow(this)">🗑</button>
                </td>
                <td><input type="text" class="item-description" ></td>
                <td><input type="text" class="hsn-code" ></td>
                <td><input type="number" class="quantity"  onchange="calculateTotal(this)"step="0.0001"></td>
                <td><input type="text" class="unit"  class="unit" ></td>
                <td><input type="number" class="unit-price"  onchange="calculateTotal(this)" step="0.0001"></td>
                <td><input type="number" class="discount"  onchange="calculateTotal(this)" step="0.01"></td>
                <td><input type="number" class="taxable-amount"  onchange="calculateTotal(this)" step="0.01"></td>
                <td><input type="number" class="tax-rate"  onchange="calculateTotal(this)" step="0.01"></td>

                <!-- Hidden Columns -->
                <td class="hidden-column"><input type="number"  class="IgstAmt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="CgstAmt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="SgstAmt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="CesRT" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="CesAmt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="CesNonAdvlAmt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="StateCesRt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="StateCesAmt" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="hidden-column"><input type="number" class="StateCesNonAdvlAmt" onchange="calculateTotal(this)"step="0.01"></td>

                <td><input type="number" class="other-charges" onchange="calculateTotal(this)"step="0.01"></td>
                <td class="total-cell"><input type="text" class="total" readonly></td>
                <!-- Dropdown for Category -->
                <td class="dropdown-filter">
                    <input type="text" class = 'category_input' id="category_input_1" placeholder="Select Category" onfocus="showDropdown(this, 'category_list_1')" oninput="filterDropdown(this.value, 'category_list_1')" >
                    <div id="category_list_1" class="dropdown-list"></div>
                    <input type="hidden" name="category_hidden_1" id="category_hidden_1">
                </td>

            </tr>
        </tbody>
    </table>

    <button type="button" class="add-row" onclick="addRow()">+ Add another row</button>
</div>

<table class="form-table" id="totalTable"> 
    <caption id="Tablecaption">Bill summary: </caption> <!-- Table Title -->
    <thead>
        <tr>
            <th>Particulars</th>
            <th>Amount</th>
            <th>Category/Account</th>
        </tr>
    </thead>
    <tbody>
        <!-- Row for Taxable Amount -->
        <tr>
            <td>Taxable Amount</td>
            <td><input type="number" name="assval" id="assval" required class="no-arrows" step="0.01"></td>
            <td>
                <div class="dropdown-filter">
                    <input type="text" id="assvalgl_input" class="filter-input" placeholder="Search GL..." required>
                    <div id="assvalgl_list" class="dropdown-list"></div>
                    <input type="hidden" name="assvalgl" id="assvalgl">
                </div>
            </td>
        </tr>
        <!-- Row for CGST Amount -->
        <tr>
            <td>CGST Amount</td>
            <td><input type="number" name="cgstval" id="cgstval" class="no-arrows" step="0.01"></td>
            <td>
                <div class="dropdown-filter">
                    <input type="text" id="cgstvalgl_input" class="filter-input" placeholder="Search GL...">
                    <div id="cgstvalgl_list" class="dropdown-list"></div>
                    <input type="hidden" name="cgstvalgl" id="cgstvalgl">
                </div>
            </td>
        </tr>
        <!-- Row for SGST Amount -->
        <tr>
            <td>SGST Amount</td>
            <td><input type="number" name="sgstval" id="sgstval" class="no-arrows" step="0.01"></td>
            <td>
                <div class="dropdown-filter">
                    <input type="text" id="sgstvalgl_input" class="filter-input" placeholder="Search GL...">
                    <div id="sgstvalgl_list" class="dropdown-list"></div>
                    <input type="hidden" name="sgstvalgl" id="sgstvalgl">
                </div>
            </td>
        </tr>
        <!-- Row for IGST Amount -->
        <tr>
            <td>IGST Amount</td>
            <td><input type="number" name="igstval" id="igstval" class="no-arrows" step="0.01"></td>
            <td>
                <div class="dropdown-filter">
                    <input type="text" id="igstvalgl_input" class="filter-input" placeholder="Search GL...">
                    <div id="igstvalgl_list" class="dropdown-list"></div>
                    <input type="hidden" name="igstvalgl" id="igstvalgl">
                </div>
            </td>
        </tr>
        <!-- Row for CESS Amount -->
        <tr>
            <td>CESS Amount</td>
            <td><input type="number" name="cessval" id="cessval" class="no-arrows" step="0.01"></td>
            <td>
                <div class="dropdown-filter">
                    <input type="text" id="cessvalgl_input" class="filter-input" placeholder="Search GL...">
                    <div id="cessvalgl_list" class="dropdown-list"></div>
                    <input type="hidden" name="cessvalgl" id="cessvalgl">
                </div>
            </td>
        </tr>
        <!-- Row for Other Charges -->
        <tr>
            <td>Other Charges</td>
            <td><input type="number" name="othchrg" id="othchrg" class="no-arrows" step=".01"></td>
            <td>
                <div class='dropdown-filter'>
                    <input type='text' id='othchrggl_input' class='filter-input' placeholder='Search GL...'>
                    <div id='othchrggl_list' class='dropdown-list'></div>
                    <input type='hidden' name='othchrggl' id='othchrggl'>
                </div> 
            </td> 
        </tr> 
        <!-- Row for Round Off Amount -->
        <tr> 
            <td>Round Off Amount</td> 
            <td><input type='number' name='rndoffamt' id='rndoffamt' class='no-arrows' step='.01'></td> 
            <td> 
                <div class='dropdown-filter'> 
                    <input type='text' id='rndoffamtgl_input' class='filter-input' placeholder='Search GL...'> 
                    <div id='rndoffamtgl_list' class='dropdown-list'></div> 
                    <input type='hidden' name='rndoffamtgl' id='rndoffamtgl'> 
                </div> 
            </td> 
        </tr> 
        <!-- Row for Total Invoice Amount (readonly) -->
        <tr> 
            <td>Total Invoice Amount</td> 
            <td><input type='number' name='totinvval' id='totinvval' class='no-arrows' step='.01' readonly></td> 
            <!-- No dropdown for Total Invoice Amount --> 
            <td></td> 
        </tr> 
    </tbody> 
</table>


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

        document.getElementById('yesButton').onclick = function() {
            document.getElementById('cashFields').classList.remove('hidden');
            document.getElementById('nonCashFields').classList.add('hidden');

            // Set required attributes for cash purchase fields
            // document.getElementById('paid_through').setAttribute('required', 'required');
            document.getElementById('paid_through_input').setAttribute('required', 'required');
            document.getElementById('paymentref').setAttribute('required', 'required');
            document.getElementById('payment_date').setAttribute('required', 'required');

            // Remove required attributes for non-cash purchase fields
            // document.getElementById('cptyname').removeAttribute('required');
            document.getElementById('cptyname_input').removeAttribute('required');

            // // Fetch GL Master List when the dropdown is clicked
            // const paidThroughInput = document.getElementById('paid_through_input');
            // paidThroughInput.onfocus = function() {
            //     fetchGLMasterList();
            //     showDropdown(paidThroughInput, 'paid_through_list');
            // };
            
            // paidThroughInput.addEventListener('input', function() {
            //     filterDropdown(this.value, 'paid_through_list');
            // });
            
        };

        document.getElementById('noButton').onclick = function() {
            document.getElementById('nonCashFields').classList.remove('hidden');
            document.getElementById('cashFields').classList.add('hidden');

            // Set required attributes for non-cash purchase fields
            // document.getElementById('cptyname').setAttribute('required', 'required');
            document.getElementById('cptyname_input').setAttribute('required', 'required');

            // Remove required attributes for cash purchase fields
            // document.getElementById('paid_through').removeAttribute('required');
            document.getElementById('paid_through_input').removeAttribute('required');
            document.getElementById('paymentref').removeAttribute('required');
            document.getElementById('payment_date').removeAttribute('required');

            // // Fetch Vendor List when the dropdown is clicked
            // const cptyNameInput = document.getElementById('cptyname_input');
            // cptyNameInput.onfocus = function() {
            //     fetchVendorList();
            //     showDropdown(cptyNameInput, 'cptyname_list');
            // };

            // cptyNameInput.addEventListener('input', function() {
            //     filterDropdown(this.value, 'cptyname_list');
            // });
            
        };


        // const assvalglinput = document.getElementById('assvalgl_input');
        // assvalglinput.onfocus = function() {
        //     fetchGLMasterListForFields();
        //     showDropdown(assvalglinput, 'assvalgl_list');
        // };
        
        // assvalglinput.addEventListener('input', function() {
        //     filterDropdown(this.value, 'assvalgl_list');
        // });



// ------------------------------------------------------------------------------------------------------------------------------------------------
// Complete list of addEventListener:
        const cashPurchaseGroup = document.getElementById('cashPurchaseGroup');
        const yesButton = document.getElementById('yesButton');
        const noButton = document.getElementById('noButton');

        // Add event listeners
        yesButton.addEventListener('click', () => toggleSelection(yesButton, noButton));
        noButton.addEventListener('click', () => toggleSelection(noButton, yesButton));

        function toggleSelection(selected, other) {
            selected.classList.add('selected'); // Highlight selected button
            other.classList.remove('selected'); // Remove highlight from the other button
        }

        // Function to compute total invoice value
        function computeTotalInvoiceValue() {
            // Parse input values, default to 0 if the input is empty
            const assval = parseFloat(document.getElementById('assval').value) || 0;
            const cgstval = parseFloat(document.getElementById('cgstval').value) || 0;
            const sgstval = parseFloat(document.getElementById('sgstval').value) || 0;
            const igstval = parseFloat(document.getElementById('igstval').value) || 0;
            const cessval = parseFloat(document.getElementById('cessval').value) || 0;
            const rndoffamt = parseFloat(document.getElementById('rndoffamt').value) || 0;
            const othchrg = parseFloat(document.getElementById('othchrg').value) || 0;

            // Calculate total
            const total = assval + cgstval + sgstval + igstval + cessval + rndoffamt + othchrg;

            // Update the readonly total field
            document.getElementById('totinvval').value = total.toFixed(2);
        }

        // Attach event listeners to input fields
        const fields = ['assval', 'cgstval', 'sgstval', 'igstval', 'cessval', 'rndoffamt', 'othchrg'];
        fields.forEach(field => {
            document.getElementById(field).addEventListener('input', computeTotalInvoiceValue);
        });



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
                document.getElementById('defaulttradegl').value = vendorData.defaulttradegl; // For cpty_gstin
                document.getElementById('assvalgl').value = vendorData.defaulttradegl; // For cpty_gstin
                document.getElementById('defaultbalancegl').value = vendorData.defaultbalancegl; // For cpty_gstin
                document.getElementById('pan').value = vendorData.pan; // For cpty_gstin
                const stcd = parseInt(vendorData.gstin.slice(0, 2), 10).toString();
                document.getElementById('supplierstate').value = stcd; // For cpty_gstin vendorData.stcd
                document.getElementById('supplierstate_input').value = statesdict[stcd]; // For cpty_gstin
                
                if(document.querySelector('input[name="is_itemize"]').checked){
                    updateRows()
                    calculateSummary()
                }

                
                // Find corresponding GL data from glDataCache using defaulttradegl
                const glEntry = glDataCache.find(item => item.gl_id === vendorData.defaulttradegl);
                
                if (glEntry) {
                    const formattedAssvalgl = `${glEntry.gl_name} || (${glEntry.gl_nature})`;
                    document.getElementById('assvalgl_input').value = formattedAssvalgl; // Update display for assvalgl_input
                } else {
                    // document.getElementById('assvalgl_input').value = ""; // Clear if not found
                }

                const glEntry5 = glDataCache.find(item => item.gl_id === vendorData.defaulttradegl);
                if (glEntry5) {
                    const formattedRndoffamtgl = `${glEntry5.gl_name} || (${glEntry5.gl_nature})`;
                    document.getElementById('rndoffamtgl_input').value = formattedRndoffamtgl; // Update display for cgstvalgl_input
                    document.getElementById('rndoffamtgl').value = glEntry5.gl_id; // Update display for cgstvalgl_input
                } else {
                    // document.getElementById('cgstvalgl_input').value = ""; // Clear if not found
                }
            }

            if (inputFieldID === "pos_input" || inputFieldID === "supplierstate_input") {
                if(document.querySelector('input[name="is_itemize"]').checked){
                    updateRows()
                    calculateSummary()
                    console.log("hello")
                }
                // console.log("hello")
            }

        }



        let glDataCache = []; // Variable to store fetched GL data
        let defaultglEntryMap = {} //used further for is_itc_available event listener
        let glEntryMap = {} //used further for is_itc_available event listener

        // Fetch GL Master List on page load
        function fetchGLMasterList() {
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
            // console.log(defaultglEntryMap)
            // Define the default GL IDs and their corresponding input fields
            const defaultGLMappings = [
                { id: 100000000006, inputId: 'assvalgl_input', hiddenId: 'assvalgl' },
                { id: 100000000001, inputId: 'cgstvalgl_input', hiddenId: 'cgstvalgl' },
                { id: 100000000002, inputId: 'sgstvalgl_input', hiddenId: 'sgstvalgl' },
                { id: 100000000003, inputId: 'igstvalgl_input', hiddenId: 'igstvalgl' },
                { id: 100000000004, inputId: 'cessvalgl_input', hiddenId: 'cessvalgl' },
                { id: 100000000006, inputId: 'rndoffamtgl_input', hiddenId: 'rndoffamtgl' }, // Corrected duplicate ID
                { id: 100000000021, inputId: 'othchrggl_input', hiddenId: 'othchrggl' }
            ];

            // Iterate over the mappings and assign values
            defaultGLMappings.forEach(mapping => {
                const glEntry = defaultglEntryMap[mapping.id];
                
                if (glEntry) {
                    const formattedValue = `${glEntry.gl_name} || (${glEntry.gl_nature})`;
                    document.getElementById(mapping.inputId).value = formattedValue; // Update display for input field
                    document.getElementById(mapping.hiddenId).value = glEntry.gl_id; // Update hidden field
                } else {
                    // document.getElementById(mapping.inputId).value = ""; // Clear if not found
                    // document.getElementById(mapping.hiddenId).value = ""; // Clear hidden field if not found
                }
            });
        }



        // // Function to populate dropdowns with cached data
        // function populateDropdowns() {
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
        //         const listElement = document.getElementById(`${dropdownId}_list`);
                
        //         // Clear previous options
        //         listElement.innerHTML = "";

        //         glDataCache.forEach(item => {
        //             const optionDiv = document.createElement("div");
        //             optionDiv.textContent = `${item.gl_name} || (${item.gl_nature})`;
        //             optionDiv.dataset.value = item.gl_id;

        //             optionDiv.onclick = function() {
        //                 selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", dropdownId, `${dropdownId}_input`, `${dropdownId}_list`);
        //             };

        //             listElement.appendChild(optionDiv);
        //         });
        //     });
        // }

        // Function to populate the dropdown for a specific row
        function populateCategoryDropdown(rowCount) {
            const listElement = document.getElementById(`category_list_${rowCount}`);
            
            // Clear previous options
            listElement.innerHTML = "";

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
            const assvalglList = document.getElementById('assvalgl_list');
            const cgstvalglList = document.getElementById('cgstvalgl_list');
            const sgstvalglList = document.getElementById('sgstvalgl_list');
            const igstvalglList = document.getElementById('igstvalgl_list');
            const cessvalglList = document.getElementById('cessvalgl_list');
            const rndoffamtglList = document.getElementById('rndoffamtgl_list');
            const othchrgglList = document.getElementById('othchrggl_list');

            // Clear previous options
            paidThroughList.innerHTML = "";
            assvalglList.innerHTML = "";
            cgstvalglList.innerHTML = "";
            sgstvalglList.innerHTML = "";
            igstvalglList.innerHTML = "";
            cessvalglList.innerHTML = "";
            rndoffamtglList.innerHTML = "";
            othchrgglList.innerHTML = "";

            glDataCache.forEach(item => {
                const optionDivPaidThrough = document.createElement("div");
                optionDivPaidThrough.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivPaidThrough.dataset.value = item.gl_id;
                optionDivPaidThrough.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'paid_through', 'paid_through_input', 'paid_through_list');
                };
                paidThroughList.appendChild(optionDivPaidThrough);

                const optionDivAssvalgl = document.createElement("div");
                optionDivAssvalgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivAssvalgl.dataset.value = item.gl_id;
                optionDivAssvalgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'assvalgl', 'assvalgl_input', 'assvalgl_list');
                    // Trigger an event to notify listeners
                    const event = new Event('input');
                    document.getElementById('assvalgl_input').dispatchEvent(event);
                };

                assvalglList.appendChild(optionDivAssvalgl);

                const optionDivCgstvalgl = document.createElement("div");
                optionDivCgstvalgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivCgstvalgl.dataset.value = item.gl_id;
                optionDivCgstvalgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'cgstvalgl', 'cgstvalgl_input', 'cgstvalgl_list');
                };
                cgstvalglList.appendChild(optionDivCgstvalgl);

                const optionDivSgstvalgl = document.createElement("div");
                optionDivSgstvalgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivSgstvalgl.dataset.value = item.gl_id;
                optionDivSgstvalgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'sgstvalgl', 'sgstvalgl_input', 'sgstvalgl_list');
                };
                sgstvalglList.appendChild(optionDivSgstvalgl);

                const optionDivIgstvalgl = document.createElement("div");
                optionDivIgstvalgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivIgstvalgl.dataset.value = item.gl_id;
                optionDivIgstvalgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'igstvalgl', 'igstvalgl_input', 'igstvalgl_list');
                };
                igstvalglList.appendChild(optionDivIgstvalgl);

                const optionDivCessvalgl = document.createElement("div");
                optionDivCessvalgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivCessvalgl.dataset.value = item.gl_id;
                optionDivCessvalgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'cessvalgl', 'cessvalgl_input', 'cessvalgl_list');
                };
                cessvalglList.appendChild(optionDivCessvalgl);

                const optionDivRndoffamtgl = document.createElement("div");
                optionDivRndoffamtgl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivRndoffamtgl.dataset.value = item.gl_id;
                optionDivRndoffamtgl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'rndoffamtgl', 'rndoffamtgl_input', 'rndoffamtgl_list');
                };
                rndoffamtglList.appendChild(optionDivRndoffamtgl);

                const optionDivOthchrggl = document.createElement("div");
                optionDivOthchrggl.textContent = `${item.gl_name} || (${item.gl_nature})`;
                optionDivOthchrggl.dataset.value = item.gl_id;
                optionDivOthchrggl.onclick = function() {
                    selectOption(item.gl_id, item.gl_name + " || (" + item.gl_nature + ")", 'othchrggl', 'othchrggl_input', 'othchrggl_list');
                };
                othchrgglList.appendChild(optionDivOthchrggl);
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

            // Fetch Vendor List when the dropdown is clicked
            const cptyNameInput = document.getElementById('cptyname_input');
            cptyNameInput.onfocus = function() {
                fetchVendorList();
                showDropdown(cptyNameInput, 'cptyname_list');
            };

            cptyNameInput.addEventListener('input', function() {
                filterDropdown(this.value, 'cptyname_list');
            });        
            
            // Show dropdown for Paid Through input field
            document.getElementById('paid_through_input').onfocus = function() {
                showDropdown(this, 'paid_through_list');
            };
            // Filter dropdowns based on user input
            document.getElementById('paid_through_input').addEventListener('input', function() {
                filterDropdown(this.value, 'paid_through_list');
            });

            document.getElementById('assvalgl_input').onfocus = function() {
                showDropdown(this, 'assvalgl_list');
            };
            document.getElementById('assvalgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'assvalgl_list');
            });

            document.getElementById('cgstvalgl_input').onfocus = function() {
                showDropdown(this, 'cgstvalgl_list');
            };
            document.getElementById('cgstvalgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'cgstvalgl_list');
            });

            document.getElementById('sgstvalgl_input').onfocus = function() {
                showDropdown(this, 'sgstvalgl_list');
            };
            document.getElementById('sgstvalgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'sgstvalgl_list');
            });

            document.getElementById('igstvalgl_input').onfocus = function() {
                showDropdown(this, 'igstvalgl_list');
            };
            document.getElementById('igstvalgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'igstvalgl_list');
            });

            document.getElementById('cessvalgl_input').onfocus = function() {
                showDropdown(this, 'cessvalgl_list');
            };
            document.getElementById('cessvalgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'cessvalgl_list');
            });

            document.getElementById('rndoffamtgl_input').onfocus = function() {
                showDropdown(this, 'rndoffamtgl_list');
            };
            document.getElementById('rndoffamtgl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'rndoffamtgl_list');
            });

            document.getElementById('othchrggl_input').onfocus = function() {
                showDropdown(this, 'othchrggl_list');
            };
            document.getElementById('othchrggl_input').addEventListener('input', function() {
                filterDropdown(this.value, 'othchrggl_list');
            });
            document.getElementById('pos_input').onfocus = function() {
                showDropdown(this, 'pos_list');
            };
            document.getElementById('pos_input').addEventListener('input', function() {
                filterDropdown(this.value, 'pos_list');
            });
            document.getElementById('supplierstate_input').onfocus = function() {
                showDropdown(this, 'supplierstate_list');
            };
            document.getElementById('supplierstate_input').addEventListener('input', function() {
                filterDropdown(this.value, 'supplierstate_list');
            });            
const states = [
    { code: "1", name: "Jammu & Kashmir (1)" },
    { code: "2", name: "Himachal Pradesh (2)" },
    { code: "3", name: "Punjab (3)" },
    { code: "4", name: "Chandigarh (4)" },
    { code: "5", name: "Uttarakhand (5)" },
    { code: "6", name: "Haryana (6)" },
    { code: "7", name: "Delhi (7)" },
    { code: "8", name: "Rajasthan (8)" },
    { code: "9", name: "Uttar Pradesh (9)" },
    { code: "10", name: "Bihar (10)" },
    { code: "11", name: "Sikkim (11)" },
    { code: "12", name: "Arunachal Pradesh (12)" },
    { code: "13", name: "Nagaland (13)" },
    { code: "14", name: "Manipur (14)" },
    { code: "15", name: "Mizoram (15)" },
    { code: "16", name: "Tripura (16)" },
    { code: "17", name: "Meghalaya (17)" },
    { code: "18", name: "Assam (18)" },
    { code: "19", name: "West Bengal (19)" },
    { code: "20", name: "Jharkhand (20)" },
    { code: "21", name: "Odisha (21)" },
    { code: "22", name: "Chhattisgarh (22)" },
    { code: "23", name: "Madhya Pradesh (23)" },
    { code: "24", name: "Gujarat (24)" },
    { code: "26", name:"Dadra & Nagar Haveli and Daman & Diu (26)"},
    { code :"27",name:"Maharashtra(27)"},
    {code :"29",name:"Karnataka(29)"},
    {code :"30",name:"Goa(30)"},
    {code :"31",name:"Lakshadweep(31)"},
    {code :"32",name:"Kerala(32)"},
    {code :"33",name:"Tamil Nadu(33)"},
    {code :"34",name:"Puducherry(34)"},
    {code :"35",name:"Andaman & Nicobar Islands(35)"},
    {code :"36",name:"Telangana(36)"},
    {code :"37",name:"Andhra Pradesh(37)"},
    {code :"38",name:"Ladakh(38)"},
    {code :"97",name:"Other territory(97)"},
    {code :"96",name:"Other country(96)"},
    {code :"99",name:"Center jurisdiction(99)"},
];

const statesdict = {
    '1': 'Jammu & Kashmir (1)',
    '2': 'Himachal Pradesh (2)',
    '3': 'Punjab (3)',
    '4': 'Chandigarh (4)',
    '5': 'Uttarakhand (5)',
    '6': 'Haryana (6)',
    '7': 'Delhi (7)',
    '8': 'Rajasthan (8)',
    '9': 'Uttar Pradesh (9)',
    '10': 'Bihar (10)',
    '11': 'Sikkim (11)',
    '12': 'Arunachal Pradesh (12)',
    '13': 'Nagaland (13)',
    '14': 'Manipur (14)',
    '15': 'Mizoram (15)',
    '16': 'Tripura (16)',
    '17': 'Meghalaya (17)',
    '18': 'Assam (18)',
    '19': 'West Bengal (19)',
    '20': 'Jharkhand (20)',
    '21': 'Odisha (21)',
    '22': 'Chhattisgarh (22)',
    '23': 'Madhya Pradesh (23)',
    '24': 'Gujarat (24)',
    '26': 'Dadra & Nagar Haveli and Daman & Diu (26)',
    '27': 'Maharashtra(27)',
    '29': 'Karnataka(29)',
    '30': 'Goa(30)',
    '31': 'Lakshadweep(31)',
    '32': 'Kerala(32)',
    '33': 'Tamil Nadu(33)',
    '34': 'Puducherry(34)',
    '35': 'Andaman & Nicobar Islands(35)',
    '36': 'Telangana(36)',
    '37': 'Andhra Pradesh(37)',
    '38': 'Ladakh(38)',
    '97':  'Other territory(97)',
    '96':  'Other country(96)', 
   '99':'Center jurisdiction(99)'
}
// Populate the dropdown for supplier state
function populateStateDropdown() {
    const dropdownList = document.getElementById('supplierstate_list');
    const dropdownList1 = document.getElementById('pos_list');
    
    // Clear any existing options
    dropdownList.innerHTML = '';
    // Clear any existing options
    dropdownList1.innerHTML = '';    

    // Populate dropdown with state options
    states.forEach(state => {
        // Create an option for supplier state
        const optionDivSupplier = document.createElement('div');
        optionDivSupplier.textContent = state.name; // Display state name
        optionDivSupplier.className = 'dropdown-item';
        
        // Set up click event to select the option for supplier state
        optionDivSupplier.onclick = function() {
            selectOption(state.code, state.name, 'supplierstate', 'supplierstate_input', 'supplierstate_list');
            dropdownList.style.display = 'none'; // Hide dropdown after selection
        };
        
        dropdownList.appendChild(optionDivSupplier);

        // Create an option for place of supply
        const optionDivPOS = document.createElement('div');
        optionDivPOS.textContent = state.name; // Display state name
        optionDivPOS.className = 'dropdown-item';
        
        // Set up click event to select the option for place of supply
        optionDivPOS.onclick = function() {
            selectOption(state.code, state.name, 'pos', 'pos_input', 'pos_list');
            dropdownList1.style.display = 'none'; // Hide dropdown after selection
        };
        
        dropdownList1.appendChild(optionDivPOS);        
    });
}

// Call this function to populate the dropdown when needed
populateStateDropdown();


    // Get the checkbox element
        const itcCheckbox = document.getElementById('is_input_availed');

        // Add event listener for change event
        itcCheckbox.addEventListener('change', function() {
            if (!this.checked) { // If unchecked
                const assvalglValue = document.getElementById('assvalgl').value;

                if (assvalglValue) {
                    // If assvalgl has a value, set it to other fields
                    document.getElementById('cgstvalgl').value = assvalglValue;
                    document.getElementById('sgstvalgl').value = assvalglValue;
                    document.getElementById('igstvalgl').value = assvalglValue;
                    document.getElementById('cessvalgl').value = assvalglValue;
                    const assvalgl_input = document.getElementById('assvalgl_input').value
                    document.getElementById('cgstvalgl_input').value = assvalgl_input;
                    document.getElementById('sgstvalgl_input').value = assvalgl_input;
                    document.getElementById('igstvalgl_input').value = assvalgl_input;
                    document.getElementById('cessvalgl_input').value = assvalgl_input;
                } else {
                    // Clear fields if assvalgl is empty
                    document.getElementById('cgstvalgl').value = "";
                    document.getElementById('sgstvalgl').value = "";
                    document.getElementById('igstvalgl').value = "";
                    document.getElementById('cessvalgl').value = "";
                    document.getElementById('cgstvalgl_input').value = "";
                    document.getElementById('sgstvalgl_input').value = "";
                    document.getElementById('igstvalgl_input').value = "";
                    document.getElementById('cessvalgl_input').value = "";
                }
            } else { // If checked
                
                document.getElementById('cgstvalgl').value = defaultglEntryMap[100000000001]["gl_id"];
                document.getElementById('sgstvalgl').value = defaultglEntryMap[100000000002]["gl_id"];
                document.getElementById('igstvalgl').value = defaultglEntryMap[100000000003]["gl_id"];
                document.getElementById('cessvalgl').value = defaultglEntryMap[100000000004]["gl_id"];
                document.getElementById('cgstvalgl_input').value = defaultglEntryMap[100000000001]["gl_name"]+" || ("+defaultglEntryMap[100000000001]["gl_nature"]+")";
                document.getElementById('sgstvalgl_input').value = defaultglEntryMap[100000000002]["gl_name"]+" || ("+defaultglEntryMap[100000000002]["gl_nature"]+")";
                document.getElementById('igstvalgl_input').value = defaultglEntryMap[100000000003]["gl_name"]+" || ("+defaultglEntryMap[100000000003]["gl_nature"]+")";
                document.getElementById('cessvalgl_input').value = defaultglEntryMap[100000000004]["gl_name"]+" || ("+defaultglEntryMap[100000000004]["gl_nature"]+")";
            }
        });


        // Store the old values globally or locally
        let oldAssvalglInputValue = document.getElementById('assvalgl_input').value;
        let oldAssvalglValue = document.getElementById('assvalgl').value;

        document.getElementById('assvalgl_input').addEventListener('input', function () {
            // Get the new values of assvalgl_input and assvalgl
            const newAssvalglInputValue = this.value;
            const newAssvalglValue = document.getElementById('assvalgl').value;

            // Array of dropdown/input IDs to check and update
            const fieldsToUpdate = [
                { input: 'cgstvalgl_input', hidden: 'cgstvalgl' },
                { input: 'sgstvalgl_input', hidden: 'sgstvalgl' },
                { input: 'igstvalgl_input', hidden: 'igstvalgl' },
                { input: 'cessvalgl_input', hidden: 'cessvalgl' },
                { input: 'rndoffamtgl_input', hidden: 'rndoffamtgl' },
                { input: 'othchrggl_input', hidden: 'othchrggl' }
            ];

            // Loop through each field and update its value if it matches the old values
            fieldsToUpdate.forEach(field => {
                const inputElement = document.getElementById(field.input);
                const hiddenElement = document.getElementById(field.hidden);

                // Update the fields if their current value matches the old values
                if (inputElement.value === oldAssvalglInputValue) {
                    inputElement.value = newAssvalglInputValue; // Update visible dropdown
                }

                if (hiddenElement.value === oldAssvalglValue) {
                    hiddenElement.value = newAssvalglValue; // Update hidden input field
                }
            });

            // Update the old values to the new values for the next change event
            oldAssvalglInputValue = newAssvalglInputValue;
            oldAssvalglValue = newAssvalglValue;
        });

        document.getElementById('is_itemize').addEventListener('change', function() {
            const itemTable = document.getElementById('itemTable');
            const totalTable = document.getElementById('totalTable');
            const itemTableContainer = document.getElementById('itemTablecontainer');
            const categoryInputs = document.querySelectorAll('.category_input');
            
            if (this.checked) {
                totalTable.classList.add('grayed-out'); // Add class to grey out
                itemTableContainer.style.display = 'block'; // Show the item 

                // Set 'required' attribute for each category input
                categoryInputs.forEach(input => {
                    input.setAttribute('required', 'required'); // Set required attribute
                });
                
                populateCategoryDropdown(1);
            } else {
                totalTable.classList.remove('grayed-out'); // Remove class to restore
                itemTableContainer.style.display = 'none'; // Hide the item table
                // Remove 'required' attribute for each category input
                categoryInputs.forEach(input => {
                    input.removeAttribute('required'); // Remove required attribute
                });
            }
        });


        // / Function to add a new row
        function addRow() {
            const tableBody = document.getElementById('itemTable').querySelector('tbody');
            const rowCount = tableBody.rows.length + 1; // Get current number of rows
            // Create a new row with inputs
            const newRow = `
                <tr class="item-row">
                    <td>
                        <span class="SlNo" id='SlNo'>${rowCount}</span>
                        <button type="button" class="delete-row" onclick="deleteRow(this)">🗑</button>
                    </td>
                    <td><input type="text"  class="item-description"></td>
                    <td><input type="text"  class="hsn-code"></td>
                    <td><input type="number"  class="quantity" onchange="calculateTotal(this)"step="0.0001"></td>
                    <td><input type="text"  class="unit" ></td>
                    <td><input type="number"   class="unit-price" onchange="calculateTotal(this)"step="0.0001"></td>
                    <td><input type="number"  class="discount" onchange="calculateTotal(this)"step="0.01"></td>
                    <td><input type="number"  class="taxable-amount" onchange="calculateTotal(this)"step="0.01"></td>
                    <td><input type="number"  class="tax-rate" onchange="calculateTotal(this)"step="0.01"></td>

                    <!-- Hidden Columns -->
                    <td class="hidden-column"><input type="number"  class="IgstAmt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="CgstAmt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="SgstAmt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="CesRT" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="CesAmt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="CesNonAdvlAmt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="StateCesRt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="StateCesAmt" onchange="calculateTotal(this)"step="0.01"></td>
                    <td class="hidden-column"><input type="number"  class="StateCesNonAdvlAmt" onchange="calculateTotal(this)"step="0.01"></td>

                    <td><input type="number" class="other-charges" onchange="calculateTotal(this)"></td>
                    <td class="total-cell"><input type="text" class="total" readonly></td>
                    <!-- Dropdown for Category -->
                    <td class='dropdown-filter'>
                            <input type='text' class='category_input' id='category_input_${rowCount}' placeholder='Select Category' onfocus='showDropdown(this, "category_list_${rowCount}")' oninput='filterDropdown(this.value, "category_list_${rowCount}")' required>
                            <div id='category_list_${rowCount}' class='dropdown-list'></div>
                            <input type='hidden' name='category_hidden_${rowCount}' id='category_hidden_${rowCount}'>
                    </td>

                </tr>
                `;
            
            // Append the new row to the table body
            tableBody.insertAdjacentHTML('beforeend', newRow);
            // Populate the category dropdown for the new row
            populateCategoryDropdown(rowCount);
            // Apply visibility state to new row's hidden columns
            const hiddenColumns = document.querySelectorAll('.hidden-column');
            const toggleButton = document.getElementById('toggleColumns');
            const newRowElement = tableBody.lastElementChild;

            if (toggleButton.textContent === '-') { // If hidden columns are visible
                const newHiddenColumns = newRowElement.querySelectorAll('.hidden-column');
                newHiddenColumns.forEach(column => {
                    column.style.display = 'table-cell';
                    column.classList.add('hidden-column-visible');
                });
            }
        }

        // Function to calculate total based on inputs
        function calculateTotal(element) {
            const row = element.closest('tr');

            const pos = document.getElementById('pos').value;
            const supplierstate = document.getElementById('supplierstate').value;


            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const discount = parseFloat(row.querySelector('.discount').value) || 0;
            const taxableAmount = parseFloat(row.querySelector('.taxable-amount').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
            const cessRate = parseFloat(row.querySelector('.CesRT').value) || 0;
            const StatecessRate = parseFloat(row.querySelector('.StateCesRt').value) || 0;
            const CesNonAdvlAmt = parseFloat(row.querySelector('.CesNonAdvlAmt').value) || 0;
            const StateCesNonAdvlAmt = parseFloat(row.querySelector('.StateCesNonAdvlAmt').value) || 0;
            const otherCharges = parseFloat(row.querySelector('.other-charges').value) || 0;

            // Calculate total
            let IgstAmt = 0;
            let CgstAmt = 0;
            let SgstAmt = 0;
            if (pos==supplierstate){
                IgstAmt = (taxableAmount * (taxRate / 100)*0);
                CgstAmt = (taxableAmount * (taxRate / 100)/2);
                SgstAmt = (taxableAmount * (taxRate / 100)/2); 
            } else {
                IgstAmt = (taxableAmount * (taxRate / 100));
                CgstAmt = (taxableAmount * (taxRate / 100)*0);
                SgstAmt = (taxableAmount * (taxRate / 100)*0); 

            }            
                
            // console.log(pos)
            // console.log(supplierstate)
            // console.log(IgstAmt)
            const CesAmt = (taxableAmount * (cessRate / 100));      
            const StateCesAmt = (taxableAmount * (StatecessRate / 100));        
            const total = taxableAmount+IgstAmt+CgstAmt+SgstAmt+CesAmt+StateCesAmt+CesNonAdvlAmt+StateCesNonAdvlAmt+otherCharges;            

            // Set total value in the corresponding input field
            
            row.querySelector('.IgstAmt').value = IgstAmt.toFixed(2); // Format to two decimal places
            row.querySelector('.CgstAmt').value = CgstAmt.toFixed(2); // Format to two decimal places
            row.querySelector('.SgstAmt').value = SgstAmt.toFixed(2); // Format to two decimal places
            row.querySelector('.StateCesAmt').value = StateCesAmt.toFixed(2); // Format to two decimal places
            row.querySelector('.CesAmt').value = CesAmt.toFixed(2); // Format to two decimal places
            row.querySelector('.total').value = total.toFixed(2); // Format to two decimal places
        }


    function updateRows() {
        const itemRows = document.querySelectorAll('#itemTable .item-row'); // Select all rows in the item table

        itemRows.forEach(row => {
            const taxableAmount = row.querySelector('.taxable-amount');

            
            // Call calculateTotal for this specific row
            calculateTotal(taxableAmount); // You can call it with any input; it will find the closest row.

        });
    }


        // Toggle hidden columns
        function toggleHiddenColumns() {
            const hiddenColumns = document.querySelectorAll('.hidden-column');
            const toggleButton = document.getElementById('toggleColumns');
            hiddenColumns.forEach(column => {
                if (column.style.display === 'none' || column.style.display === '') {
                    column.style.display = 'table-cell';
                    column.classList.add('hidden-column-visible'); // Add custom visible style
                } else {
                    column.style.display = 'none';
                    column.classList.remove('hidden-column-visible'); // Remove custom style
                }
            });
            // Change button text
            toggleButton.textContent = toggleButton.textContent === '+' ? '-' : '+';
        }
// Function to delete a row
function deleteRow(button) {
    const row = button.closest('tr'); // Get the row to delete
    const tableBody = document.querySelector('#itemTable tbody');

    // Remove the row
    tableBody.removeChild(row);

    // Update serial numbers
    updateSerialNumbers();
}

// Function to update serial numbers
function updateSerialNumbers() {
    const rows = document.querySelectorAll('#itemTable tbody tr');

    rows.forEach((row, index) => {
        const slnoElement = row.querySelector('.SlNo');
        const categoryInput = row.querySelector('.dropdown-filter input[type="text"]');
        const categoryList = row.querySelector('.dropdown-list');
        const categoryHidden = row.querySelector('.dropdown-filter input[type="hidden"]');

        // Update serial number
        slnoElement.textContent = index + 1;

        // Update category input IDs to maintain uniqueness
        if (categoryInput && categoryList && categoryHidden) {
            categoryInput.id = `category_input_${index + 1}`;
            categoryList.id = `category_list_${index + 1}`;
            categoryHidden.id = `category_hidden_${index + 1}`;

            // Ensure the `onfocus` and `oninput` attributes of the input match the new IDs
            categoryInput.setAttribute('onfocus', `showDropdown(this, "category_list_${index + 1}")`);
            categoryInput.setAttribute('oninput', `filterDropdown(this.value, "category_list_${index + 1}")`);
        }
    });
}

function calculateSummary() {
    let assval = 0;
    let cgstval = 0;
    let sgstval = 0;
    let igstval = 0;
    let cessval = 0;
    let othchrg = 0;
    let totinvval = 0;

    // Iterate over each row of the item table
    document.querySelectorAll('.item-row').forEach(row => {
        assval += parseFloat(row.querySelector('.taxable-amount').value || 0);
        cgstval += parseFloat(row.querySelector('.CgstAmt').value || 0);
        sgstval += parseFloat(row.querySelector('.SgstAmt').value || 0);
        igstval += parseFloat(row.querySelector('.IgstAmt').value || 0);
        cessval += parseFloat(row.querySelector('.CesAmt').value || 0) +
                   parseFloat(row.querySelector('.CesNonAdvlAmt').value || 0) +
                   parseFloat(row.querySelector('.StateCesAmt').value || 0)+
                   parseFloat(row.querySelector('.StateCesNonAdvlAmt').value || 0);
        othchrg += parseFloat(row.querySelector('.other-charges').value || 0);
        totinvval += parseFloat(row.querySelector('.total').value || 0);
    });

    // Update summary table
    document.getElementById('assval').value = assval.toFixed(2);
    document.getElementById('cgstval').value = cgstval.toFixed(2);
    document.getElementById('sgstval').value = sgstval.toFixed(2);
    document.getElementById('igstval').value = igstval.toFixed(2);
    document.getElementById('cessval').value = cessval.toFixed(2);
    document.getElementById('othchrg').value = othchrg.toFixed(2);
    document.getElementById('totinvval').value = totinvval.toFixed(2);
}

// Call calculateSummary() whenever necessary, e.g., after changes in the table
document.getElementById('itemTable').addEventListener('input', calculateSummary);
document.getElementById('itemTable').addEventListener('change', calculateSummary);


      document.getElementById('transactionForm').onsubmit = function(event) {
          event.preventDefault();

          const isCashPurchase = cashPurchaseGroup.querySelector('.selected').id === "yesButton" ? "Y" : "N";

          const jsonData = {
              transaction_type: document.getElementById('transaction_type').value,
              source: "manual",
              abid: parseInt(document.getElementById('abid').value),
              posted_date: document.getElementById('posted_date').value,
              is_cash_purchase: isCashPurchase,
              is_rev_charge_2b_or_einv: document.querySelector('input[name="is_rev_charge_2b_or_einv"]').checked ? 'Y' : 'N',
              is_input_availed: document.querySelector('input[name="is_input_availed"]').checked ? 'Y' : 'N',
              is_itemize: document.querySelector('input[name="is_itemize"]').checked ? 'Y' : 'N',
              ref_no: document.getElementById('ref_no').value,
              doc_type: document.getElementById('doc_type').value,
              doc_date: document.getElementById('doc_date').value,
              irn: document.getElementById('irn').value,
              journal_description: document.getElementById('journal_description').value,              
              pos: document.getElementById('pos').value,
              assvalgl: parseInt(document.getElementById('assvalgl').value) || '',
              cgstvalgl: parseInt(document.getElementById('cgstvalgl').value) || '',
              sgstvalgl: parseInt(document.getElementById('sgstvalgl').value) || '',
              igstvalgl: parseInt(document.getElementById('igstvalgl').value) || '',
              cessvalgl: parseInt(document.getElementById('cessvalgl').value) || '',
              rndoffamtgl: parseInt(document.getElementById('rndoffamtgl').value) || '',
              othchrggl: parseInt(document.getElementById('othchrggl').value) || '',
              defaulttradegl: parseInt(document.getElementById('defaulttradegl').value) || '',
              defaultbalancegl: parseInt(document.getElementById('defaultbalancegl').value) || '',                            
              assval: parseFloat(document.getElementById('assval').value) || 0,
              cgstval: parseFloat(document.getElementById('cgstval').value) || 0,
              sgstval: parseFloat(document.getElementById('sgstval').value) || 0,
              igstval: parseFloat(document.getElementById('igstval').value) || 0,
              cessval: parseFloat(document.getElementById('cessval').value) || 0,
              rndoffamt: parseFloat(document.getElementById('rndoffamt').value) || 0,
              othchrg: parseFloat(document.getElementById('othchrg').value) || 0,
              totinvval: parseFloat(document.getElementById('totinvval').value) || 0,
              items: []
          };

            // // Capture item rows including hidden fields
            // const rows = document.querySelectorAll('.item-row');
            // rows.forEach((row) => {
            //     const item = {
            //         SlNo: row.querySelector('.SlNo').innerText,
            //         PrdDesc: row.querySelector('.item-description').value,
            //         HsnCd: row.querySelector('.hsn-code').value,
            //         Qty: parseFloat(row.querySelector('.quantity').value) || 0,
            //         Unit: row.querySelector('.unit').value,
            //         UnitPrice: parseFloat(row.querySelector('.unit-price').value) || 0,
            //         Discount: parseFloat(row.querySelector('.discount').value) || 0,
            //         AssAmt: parseFloat(row.querySelector('.taxable-amount').value) || 0,
            //         GstRt: parseFloat(row.querySelector('.tax-rate').value) || 0,
            //         IgstAmt: parseFloat(row.querySelector('.IgstAmt').value) || 0,
            //         CgstAmt: parseFloat(row.querySelector('.CgstAmt').value) || 0,
            //         SgstAmt: parseFloat(row.querySelector('.SgstAmt').value) || 0,
            //         CesRt: parseFloat(row.querySelector('.CesRT').value) || 0,
            //         CesAmt: parseFloat(row.querySelector('.CesAmt').value) || 0,
            //         CesNonAdvlAmt: parseFloat(row.querySelector('.CesNonAdvlAmt').value) || 0,
            //         StateCesRt: parseFloat(row.querySelector('.StateCesRt').value) || 0,
            //         StateCesAmt: parseFloat(row.querySelector('.StateCesAmt').value) || 0,
            //         StateCesNonAdvlAmt: parseFloat(row.querySelector('.StateCesNonAdvlAmt').value) || 0,
            //         OthChrg: parseFloat(row.querySelector('.other-charges').value) || 0,
            //         TotItemVal: parseFloat(row.querySelector('.total').value) || 0,
            //         gl_id: parseInt(row.querySelector(`#category_hidden_${row.querySelector('.SlNo').innerText}`).value) || 0,
            //     };

            //     jsonData.items.push(item);
            // });

          // Check if it's a cash purchase
        if (jsonData.doc_type === "CRN") {
            // Create a list of values to negate
            let list = [
                'assval',
                'cgstval',
                'sgstval',
                'igstval',
                'cessval',
                'rndoffamt',
                'othchrg',
                'totinvval'
            ];
            
            // Negate each value in jsonData
            list.forEach(key => {
                jsonData[key] = -jsonData[key];
            });
            // Capture item rows including hidden fields
            if (jsonData.is_itemize === "Y") {
                const rows = document.querySelectorAll('.item-row');
                rows.forEach((row) => {
                    const item = {
                        SlNo: row.querySelector('.SlNo').innerText,
                        PrdDesc: row.querySelector('.item-description').value,
                        HsnCd: row.querySelector('.hsn-code').value,
                        Qty: -parseFloat(row.querySelector('.quantity').value) || 0,
                        Unit: row.querySelector('.unit').value,
                        UnitPrice: -parseFloat(row.querySelector('.unit-price').value) || 0,
                        Discount: -parseFloat(row.querySelector('.discount').value) || 0,
                        AssAmt: -parseFloat(row.querySelector('.taxable-amount').value) || 0,
                        GstRt: parseFloat(row.querySelector('.tax-rate').value) || 0,
                        IgstAmt: -parseFloat(row.querySelector('.IgstAmt').value) || 0,
                        CgstAmt: -parseFloat(row.querySelector('.CgstAmt').value) || 0,
                        SgstAmt: -parseFloat(row.querySelector('.SgstAmt').value) || 0,
                        CesRt: parseFloat(row.querySelector('.CesRT').value) || 0,
                        CesAmt: -parseFloat(row.querySelector('.CesAmt').value) || 0,
                        CesNonAdvlAmt: -parseFloat(row.querySelector('.CesNonAdvlAmt').value) || 0,
                        StateCesRt: parseFloat(row.querySelector('.StateCesRt').value) || 0,
                        StateCesAmt: -parseFloat(row.querySelector('.StateCesAmt').value) || 0,
                        StateCesNonAdvlAmt: -parseFloat(row.querySelector('.StateCesNonAdvlAmt').value) || 0,
                        OthChrg: -parseFloat(row.querySelector('.other-charges').value) || 0,
                        TotItemVal: -parseFloat(row.querySelector('.total').value) || 0,
                        gl_id: parseInt(row.querySelector(`#category_hidden_${row.querySelector('.SlNo').innerText}`).value) || 0,
                    };

                    jsonData.items.push(item);
                });
            }

        }else {
            // Capture item rows including hidden fields
            if (jsonData.is_itemize === "Y") {
                const rows = document.querySelectorAll('.item-row');
                rows.forEach((row) => {
                    const item = {
                        SlNo: row.querySelector('.SlNo').innerText,
                        PrdDesc: row.querySelector('.item-description').value,
                        HsnCd: row.querySelector('.hsn-code').value,
                        Qty: parseFloat(row.querySelector('.quantity').value) || 0,
                        Unit: row.querySelector('.unit').value,
                        UnitPrice: parseFloat(row.querySelector('.unit-price').value) || 0,
                        Discount: parseFloat(row.querySelector('.discount').value) || 0,
                        AssAmt: parseFloat(row.querySelector('.taxable-amount').value) || 0,
                        GstRt: parseFloat(row.querySelector('.tax-rate').value) || 0,
                        IgstAmt: parseFloat(row.querySelector('.IgstAmt').value) || 0,
                        CgstAmt: parseFloat(row.querySelector('.CgstAmt').value) || 0,
                        SgstAmt: parseFloat(row.querySelector('.SgstAmt').value) || 0,
                        CesRt: parseFloat(row.querySelector('.CesRT').value) || 0,
                        CesAmt: parseFloat(row.querySelector('.CesAmt').value) || 0,
                        CesNonAdvlAmt: parseFloat(row.querySelector('.CesNonAdvlAmt').value) || 0,
                        StateCesRt: parseFloat(row.querySelector('.StateCesRt').value) || 0,
                        StateCesAmt: parseFloat(row.querySelector('.StateCesAmt').value) || 0,
                        StateCesNonAdvlAmt: parseFloat(row.querySelector('.StateCesNonAdvlAmt').value) || 0,
                        OthChrg: parseFloat(row.querySelector('.other-charges').value) || 0,
                        TotItemVal: parseFloat(row.querySelector('.total').value) || 0,
                        gl_id: parseInt(row.querySelector(`#category_hidden_${row.querySelector('.SlNo').innerText}`).value) || 0,
                    };

                    jsonData.items.push(item);
                });
            }
        }

          // Check if it's a cash purchase
          if (document.getElementById('cashFields').classList.contains('hidden') === false) {
              jsonData.paid_through = document.getElementById('paid_through').value;
              jsonData.paymentref = document.getElementById('paymentref').value;
              jsonData.payment_date = document.getElementById('payment_date').value;
          }

          // Check if it's a non-cash purchase
          if (document.getElementById('nonCashFields').classList.contains('hidden') === false) {
              jsonData.cptyname = document.getElementById('cptyname').value;
              jsonData.cptyid = parseInt(document.getElementById('cptyid').value);
              jsonData.cpty_gstin = document.getElementById('cpty_gstin').value;
              jsonData.pan = document.getElementById('pan').value;
              jsonData.defaulttradegl = parseInt(document.getElementById('defaulttradegl').value);
              jsonData.defaultbalancegl = parseInt(document.getElementById('defaultbalancegl').value);
              // You can add more fields related to non-cash purchases here
          }


        //   console.log(JSON.stringify(jsonData));

        // Function to generate lines with summed amounts for same GL IDs
        function generateLines(data) {
            const linesMap = {};
            if (data.is_itemize == "N"){
                // Map GLs to their corresponding amounts and descriptions
                const glMappings = [
                    { gl_id: data.assvalgl, amount: data.assval, description: "Assessable Value" },
                    { gl_id: data.cgstvalgl, amount: data.cgstval, description: "CGST Amount" },
                    { gl_id: data.sgstvalgl, amount: data.sgstval, description: "SGST Amount" },
                    { gl_id: data.igstvalgl, amount: data.igstval, description: "IGST Amount" },
                    { gl_id: data.cessvalgl, amount: data.cessval, description: "CESS Amount" },
                    { gl_id: data.rndoffamtgl, amount: data.rndoffamt, description: "Round Off Amount" },
                    { gl_id: data.othchrggl, amount: data.othchrg, description: "Other Charges" }
                ];         

                if (data.is_rev_charge_2b_or_einv == "Y"){//ignore tds/tcs  for now
                    balanceamount = -data.totinvval+(data.cgstval+data.sgstval+data.igstval+data.cessval)
                    const rcm_lines = [
                        { gl_id: data.defaultbalancegl, amount: balanceamount, description:"Amount payable to cpty"},
                        { gl_id: defaultglEntryMap[100000000017]["gl_id"], amount: -data.cgstval, description: "RCM invward supplies CGST" },
                        { gl_id: defaultglEntryMap[100000000018]["gl_id"], amount: -data.sgstval, description: "RCM invward supplies SGST" },
                        { gl_id: defaultglEntryMap[100000000019]["gl_id"], amount: -data.igstval, description: "RCM invward supplies IGST" },
                        { gl_id: defaultglEntryMap[100000000020]["gl_id"], amount: -data.cessval, description: "RCM invward supplies Cess" }
                    ];
                    glMappings.push(...rcm_lines);// Append each element of rcm_lines to glMappings
                }else {
                    balanceamount = -data.totinvval
                    const balanceamount_lines = {gl_id: data.defaultbalancegl, amount: balanceamount, description:"Amount payable to cpty"}
                    glMappings.push(balanceamount_lines);
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
            }else {
                const glMappings=[]
                if (data.is_input_availed == "Y"){
                    const glMappings1 = [
                        { gl_id: data.cgstvalgl, amount: data.cgstval, description: "CGST Amount" },
                        { gl_id: data.sgstvalgl, amount: data.sgstval, description: "SGST Amount" },
                        { gl_id: data.igstvalgl, amount: data.igstval, description: "IGST Amount" },
                        { gl_id: data.cessvalgl, amount: data.cessval, description: "CESS Amount" },
                        { gl_id: data.rndoffamtgl, amount: data.rndoffamt, description: "Round Off Amount" },
                        { gl_id: data.othchrggl, amount: data.othchrg, description: "Other Charges" }
                    ]
                    glMappings.push(...glMappings1);
                    data.items.forEach(item => {
                        const glMappings2 = { gl_id: item.gl_id, amount: item.AssAmt, description: "Assessable Value" }
                        glMappings.push(glMappings2);
                    })
                }else{
                    const glMappings1 = [
                        { gl_id: data.rndoffamtgl, amount: data.rndoffamt, description: "Round Off Amount" },
                        { gl_id: data.othchrggl, amount: data.othchrg, description: "Other Charges" }
                    ]       
                    glMappings.push(...glMappings1);
                    data.items.forEach(item => {
                        const glMappings2 = { gl_id: item.gl_id, amount: item.AssAmt+item.IgstAmt+item.CgstAmt+item.SgstAmt+item.CesAmt+item.CesNonAdvlAmt+item.StateCesAmt+item.StateCesNonAdvlAmt, description: "Assessable Value" }
                        glMappings.push(glMappings2);
                    })                                 
                }
                
                if (data.is_rev_charge_2b_or_einv == "Y"){//ignore tds/tcs  for now
                    balanceamount = -data.totinvval+(data.cgstval+data.sgstval+data.igstval+data.cessval)
                    const rcm_lines = [
                        { gl_id: data.defaultbalancegl, amount: balanceamount, description:"Amount payable to cpty"},
                        { gl_id: defaultglEntryMap[100000000017]["gl_id"], amount: -data.cgstval, description: "RCM invward supplies CGST" },
                        { gl_id: defaultglEntryMap[100000000018]["gl_id"], amount: -data.sgstval, description: "RCM invward supplies SGST" },
                        { gl_id: defaultglEntryMap[100000000019]["gl_id"], amount: -data.igstval, description: "RCM invward supplies IGST" },
                        { gl_id: defaultglEntryMap[100000000020]["gl_id"], amount: -data.cessval, description: "RCM invward supplies Cess" }
                    ];
                    glMappings.push(...rcm_lines);// Append each element of rcm_lines to glMappings
                }else {
                    balanceamount = -data.totinvval
                    const balanceamount_lines = {gl_id: data.defaultbalancegl, amount: balanceamount, description:"Amount payable to cpty"}
                    glMappings.push(balanceamount_lines);
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
        }

        // Generate the lines
        const payload = generateLines(jsonData);

        // Log the result
        console.log(JSON.stringify(payload));

          // Use AJAX to send data to server using PHP cURL
          const xhrSubmit = new XMLHttpRequest();
          xhrSubmit.open("POST", "manualbillupload.php", true);
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
                    amountCell.textContent = line.amount.toFixed(0); // Format amount to two decimal places

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
                    amountCell.textContent = line.amount.toFixed(0); // Format amount to two decimal places

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

    </script>
</div>

</body>
</html>
