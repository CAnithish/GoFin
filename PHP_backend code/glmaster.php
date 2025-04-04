<?php
ob_start(); // Start output buffering

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Master</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .add-button {
            float: right;
            padding: 10px 15px;
            background-color: #28a745; /* Green color */
            color: white;
            border: none;
            cursor: pointer;
        }
        .import-button {
            float: right;
            padding: 10px 15px;
            background-color: #141414; /* Green color */
            color: white;
            border: none;
            cursor: pointer;
        }
        .fade-out {
            transition: opacity 2s ease-out;
            opacity: 1;
        }
        .fade-out.hidden {
            opacity: 0;
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['success_message'])): ?>
    <div id="success-message" class="alert alert-success fade-out">
        <?php echo $_SESSION['success_message']; ?>
    </div>
    <?php unset($_SESSION['success_message']); // Clear the message after displaying it ?>
<?php endif; ?>

<script>
    // Fade out success message after 2 seconds
    window.onload = function() {
        const message = document.getElementById('success-message');
        if (message) {
            setTimeout(() => {
                message.classList.add('hidden');
            }, 2000); // Fade out after 2 seconds
        }
    };
</script>

<h1>Category Master</h1>

<!-- Button trigger modal for adding new record -->
<button type="button" class="btn add-button" data-toggle="modal" data-target="#myModal">+</button>

<!-- Import Button -->
<button type="button" class="btn import-button" data-toggle="modal" data-target="#dataModal">Import</button>

<!-- The Modal for Adding New Category -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" action="" method="POST">
                    <div class="form-group">
                        <label for="gl_name">Category Name (Mandatory):</label>
                        <input type="text" class="form-control" id="gl_name" name="gl_name" required>
                    </div>
                    <div class="form-group">
                        <label for="gl_nature">Nature (Mandatory):</label>
                        <select class="form-control" id="gl_nature" name="gl_nature" required>
                            <option value="" disabled selected>Select Nature</option>
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <input type="text" class="form-control" id="description" name="description">
                    </div>
                    <div class="form-group">
                        <label for="grouping">Grouping:</label>
                        <input type="text" class="form-control" id="grouping" name="grouping">
                    </div>

                    <?php
                        echo '<input type="hidden" id="abid" name="abid" value="' . htmlspecialchars($abid) . '">';
                        echo '<input type="hidden" id="type" name="type" value="custom">';
                    ?>

                    <button type="submit" class="btn btn-primary">Create</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- The Modal for Importing Data -->
<div class='modal fade' id='dataModal' tabindex='-1' role='dialog' aria-labelledby='dataModalLabel' aria-hidden='true'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='dataModalLabel'>Fetched Data</h5>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
            </div>
            <div class='modal-body'>
                <table id='fetchedDataTable' class='table'>
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Default GL ID</th>
                            <th>Default Name</th>
                            <th>Nature</th>
                            <th>Import status</th>
                        </tr>
                    </thead>
                    <tbody id='dataBody'>
                        <!-- Fetched data will be inserted here -->
                    </tbody>
                </table>
                <button type="button" id="importButton" class="btn btn-primary">Import Selected</button>

            </div>
        </div>
    </div>
</div>

<!-- The Modal for Editing -->
<div class='modal fade' id='editModal' tabindex='-1' role='dialog' aria-labelledby='editModalLabel' aria-hidden='true'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='editModalLabel'>Edit Category</h5>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
            </div>
            <div class='modal-body'>
                <form id='editForm' action='' method='POST'>
                    <input type='hidden' id='edit_gl_id' name='gl_id'>
                    <input type='hidden' id='edit_abid' name='abid'>
                    <div class='form-group'>
                        <label for='edit_gl_name'>Category Name (Mandatory):</label>
                        <input type='text' class='form-control' id='edit_gl_name' name='gl_name' required>
                    </div>

                    <!-- Nature dropdown disabled -->
                    <div class='form-group'>
                        <label for='edit_gl_nature'>Nature (Mandatory):</label>
                        <select class='form-control' id='edit_gl_nature' name='gl_nature' required disabled>
                            <!-- Options will be filled dynamically -->
                            <option value='' disabled>Select Nature</option>
                            <option value='asset'>Asset</option>
                            <option value='liability'>Liability</option>
                            <option value='income'>Income</option>
                            <option value='expense'>Expense</option>
                        </select>
                    </div>

                    <!-- Editable fields -->
                    <div class='form-group'>
                        <label for='edit_description'>Description:</label>
                        <input type='text' class='form-control' id='edit_description' name='description'>
                    </div>

                    <div class='form-group'>
                        <label for='edit_grouping'>Grouping:</label>
                        <input type='text' class='form-control' id='edit_grouping' name='grouping'>
                    </div>

                    <!-- Update button -->
                    <button type='submit' class='btn btn-primary'>Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src='https://code.jquery.com/jquery-3.5.1.slim.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js'></script>
<script src='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js'></script>

<script>
    let selectedIds = []; // Store selected default_gl_ids

    // Fetch data and populate the modal table
    document.querySelector('.import-button').addEventListener('click', function () {
        fetch('default_gl_master.php')
            .then((response) => response.json())
            .then((data) => {
                const dataBody = document.getElementById('dataBody');
                dataBody.innerHTML = ''; // Clear previous data
                selectedIds = []; // Reset selection

                if (data.error) {
                    dataBody.innerHTML = `<tr><td colspan="4">${data.error}</td></tr>`;
                } else {
                    data.forEach((item) => {
                        const isDisabled = item.import_status === 'Already Imported' ? 'disabled' : '';
                        const rowClass = item.import_status === 'Already Imported' ? 'table-secondary' : '';
                        const row = `
                            <tr class="${rowClass}">
                                <td>
                                    <input type="checkbox" class="select-checkbox" data-id="${item.default_gl_id}" ${isDisabled}>
                                </td>
                                <td>${item.default_gl_id}</td>
                                <td>${item.default_gl_name}</td>
                                <td>${item.default_gl_nature}</td>
                                <td>${item.import_status}</td>
                            </tr>`;
                        dataBody.innerHTML += row;
                    });

                    // Add event listeners to checkboxes
                    document.querySelectorAll('.select-checkbox').forEach((checkbox) => {
                        checkbox.addEventListener('change', function () {
                            const id = parseInt(this.getAttribute('data-id'), 10); // Convert id to an integer
                            if (this.checked) {
                                selectedIds.push(id); // Add to selected list as an integer
                                console.log(selectedIds);
                            } else {
                                selectedIds = selectedIds.filter((selectedId) => selectedId !== id); // Remove from selected list
                            }
                        });
                    });
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                const dataBody = document.getElementById('dataBody');
                dataBody.innerHTML = `<tr><td colspan="4">Error fetching data</td></tr>`;
            });
    });

    // Function to handle the import action
    document.getElementById('importButton').addEventListener('click', function () {
        if (selectedIds.length === 0) {
            alert('No items selected for import.');
            return;
        }

        fetch('import_default_gl.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ default_gl_ids: selectedIds }),
        })
            .then((response) => response.json())
            .then((result) => {
                alert(result.message); // Show success or error message
                selectedIds = []; // Clear selection after import
                document.querySelector('.import-button').click(); // Refresh data
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('Error during import process.');
            });
    });
</script>



<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['gl_id'])) {
    // Get form data
    $gl_name = $_POST['gl_name'];
    $gl_nature = $_POST['gl_nature'];
    $description = $_POST['description'];
    $grouping = $_POST['grouping'];
    $abid = $_POST['abid'];
    $type = $_POST['type'];

    // Prepare data to send
    $data = [
        'gl_name' => $gl_name,
        'gl_nature' => $gl_nature,
        'gl_grouping' => $grouping,
        'description' => $description,
        'abid' => $abid,
        'type' => $type
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set the URL for the POST request
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/glmaster");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    // Send JSON data
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute the POST request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo '<p style=\'color:red;\'>Error creating record: ' . curl_error($ch) . '</p>';
        exit();
    }

    // Get HTTP response code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    // Handle response based on HTTP status code
    if ($http_code == 200) {
        echo '<p style=\'color:green;\'>Record created successfully.</p>';
        
        header("Refresh:2; url=".$_SERVER['PHP_SELF']); // Refresh after 2 seconds
        exit();
        
    } else {
        echo '<p style=\'color:red;\'>Unexpected error occurred. HTTP Status Code ' . $http_code . '</p>';
        exit();
    }
}

// Handle form submission for updating record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gl_id'])) {
    // Get form data
    $gl_id = $_POST['gl_id'];
    $abid = $_POST['abid'];
    $gl_name = $_POST['gl_name'];
    $description = $_POST['description'];
    $grouping = $_POST['grouping'];

   // Prepare data to send
   $data = [
       'gl_id' => (int)$gl_id,
       'abid' => (int)$abid,
       'gl_name' => $gl_name,
       'description' => $description,
       'gl_grouping' => $grouping,
   ];

   // Initialize cURL for PUT request
   $ch = curl_init();
   
   // Set the URL for the PUT request
   curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/glmaster");
   curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_HTTPHEADER, [
       'Content-Type: application/json'
   ]);
   
   // Send JSON data
   curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

   // Execute the PUT request
   $response = curl_exec($ch);

   // Check for cURL errors
   if (curl_errno($ch)) {
       echo '<p style=\'color:red;\'>Error updating record: ' . curl_error($ch) . '</p>';
       exit();
   }

   // Get HTTP response code
   $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

   // Close cURL session
   curl_close($ch);

   // Handle response based on HTTP status code
   if ($http_code == 200) {
       echo '<p style=\'color:green;\'>Record updated successfully.</p>';
       
       header("Refresh:2; url=".$_SERVER['PHP_SELF']); // Refresh after 2 seconds
       exit();
   } else {
       echo '<p style=\'color:red;\'>Unexpected error occurred. HTTP Status Code ' . $http_code . '</p>';
   }
}

// Fetch existing records to display
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/glmaster?abid={$abid}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string

// Execute the GET request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
   echo '<p style=\'color:red;\'>cURL Error while fetching records: ' . curl_error($ch) . '</p>';
}

// Get HTTP response code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL session
curl_close($ch);

// Handle the response based on the HTTP status code
if ($http_code == 200) {
   // Decode JSON response
   $data = json_decode($response, true);
   
   if (!empty($data)) {
       echo '<h2>Records Found:</h2>';
       echo '<table>';
       echo '<tr><th>Nature</th><th>Name</th><th>Type</th><th>Action</th></tr>'; 

       foreach ($data as $item) {
           echo '<tr onclick=\'populateEditForm(' . htmlspecialchars(json_encode($item)) . ')\'>';
           echo '<td>' . htmlspecialchars($item['gl_nature']) . '</td>'; 
           echo '<td>' . htmlspecialchars($item['gl_name']) . '</td>'; 
           echo '<td>' . htmlspecialchars($item['type']) . '</td>'; 
           echo '<td><button class=\'btn btn-info\' onclick=\'populateEditForm(' . htmlspecialchars(json_encode($item)) . ')\'>Edit</button>';
           echo '<button class=\'btn btn-danger\' onclick=\'deleteGLMaster(' . htmlspecialchars(json_encode($item)) . ')\'>Delete</button></td>';
           echo '</tr>';
       }
       
       echo '</table>';
   } else {
       echo '<p>No records found.</p>';
   }
} elseif ($http_code == 404) {
   echo '<p style=\'color:orange;\'>No records found.</p>'; 
} else {
   echo '<p style=\'color:red;\'>Unexpected error while fetching records. HTTP Status Code ' . $http_code . '</p>';
}
?>

<script>
// Function to populate the edit form with selected record data
function populateEditForm(item) {
   document.getElementById('edit_gl_id').value = item.gl_id;
   document.getElementById('edit_abid').value = item.abid;
   document.getElementById('edit_gl_name').value = item.gl_name;

   var natureSelect = document.getElementById('edit_gl_nature');
   
	// Enable the nature select dropdown and set its value
	natureSelect.value = item.gl_nature; 
	natureSelect.disabled = true; 

	document.getElementById('edit_description').value = item.description || '';
	document.getElementById('edit_grouping').value = item.gl_grouping || '';
	
	// Show the modal for editing
	$('#editModal').modal('show');
}

function deleteGLMaster(item) {
    if (confirm("Are you sure you want to delete this record?")) {
        const gl_id = item.gl_id;
        const abid = item.abid;

        // Create a form dynamically
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_gl.php'; // URL of your PHP endpoint

        // Create hidden input fields for gl_id and abid
        const inputGlId = document.createElement('input');
        inputGlId.type = 'hidden';
        inputGlId.name = 'gl_id';
        inputGlId.value = gl_id;

        const inputAbId = document.createElement('input');
        inputAbId.type = 'hidden';
        inputAbId.name = 'abid';
        inputAbId.value = abid;

        // Append inputs to the form
        form.appendChild(inputGlId);
        form.appendChild(inputAbId);

        // Append the form to the body (not displayed)
        document.body.appendChild(form);

        // Submit the form
        form.submit();
    }
}
</script>

</body>
</html>
