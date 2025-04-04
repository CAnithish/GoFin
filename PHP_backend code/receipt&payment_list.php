<!DOCTYPE html>
<html>
<head>
    <title>Receipts and payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .journal-card {
            background: linear-gradient(to bottom, #ffffff, #e6f7ff);
            border: none;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .journal-card:hover {
            transform: scale(1.01);
        }
        .journal-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff5733;
        }
        .orange-border {
            border-left: 8px solid orange;
        }
        .green-border {
            border-left: 8px solid green;
        }
    </style>
</head>
<body>
<div>
    <label for="narration-filter">Filter by Narration:</label>
    <input type="text" id="narration-filter" placeholder="Enter narration text">
</div>
    <div class="container mt-4">
        <h1 class="mb-4 text-center">Receipts and payments</h1>

        <div id="journal-list" class="grid-container">
            <?php
            if (isset($_COOKIE['userdetails'])) {
                $userdetails = json_decode($_COOKIE['userdetails'], true);

                $abid = isset($userdetails['abid']) ? $userdetails['abid'] : "Key not found";
                if ($abid === "Key not found") {
                    header("Location: http://localhost/example/ablogin.html");
                }
            } else {
                header("Location: http://localhost/example/ablogin.html");
            }

            $url = "http://127.0.0.1:8000/journal_header_list_payment_receipt";
            // Check if parameters are set and retrieve them safely
            // $abid = isset($_GET['abid']) ? (int)$_GET['abid'] : null;
            $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'payment_date'; // Default sort column
            $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc'; // Default sort order
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Default page number
            $page_size = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 1000; // Default page size

            // Optionally handle filters if needed
            $filter = isset($_GET['filter']) ? $_GET['filter'] : null;
            // Prepare query parameters
            $queryParams = [
                'abid' => $abid,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order,
                'page' => $page,
                'page_size' => $page_size,
                'filter' => $filter, // Add filter parameter here
            ];

            // Construct the complete URL with query parameters
            $urlWithParams = $url . '?' . http_build_query($queryParams);

            // Initialize cURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlWithParams);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode == 200) {
                $response = json_decode($response, true);
                $data = $response['headers'];
                // Example values for demonstration purposes
                $total_count = $response['total_count']; // Total records count from your database query
                $total_pages = $response['total_pages']; // Calculate total pages based on page size
                $current_page = $response['current_page']; // Current page from URL, default to 1

                // Prepare the data array for JavaScript
                $pagedata = [
                    "total_count" => $total_count,
                    "total_pages" => $total_pages,
                    "current_page" => $current_page,
                ];
                $glUrl = "http://127.0.0.1:8000/glmaster?abid=" . $abid;
                $chGl = curl_init();
                curl_setopt($chGl, CURLOPT_URL, $glUrl);
                curl_setopt($chGl, CURLOPT_RETURNTRANSFER, 1);
                $glResponse = curl_exec($chGl);
                $glHttpCode = curl_getinfo($chGl, CURLINFO_HTTP_CODE);
                curl_close($chGl);

                $glData = [];
                if ($glHttpCode == 200) {
                    $glData = json_decode($glResponse, true);

                    // Convert to dictionary where 'gl_id' is the key
                    $glDataDict = [];
                    foreach ($glData as $item) {
                        if (isset($item['gl_id'])) {
                            $glDataDict[$item['gl_id']] = $item;
                        }
                    }
                    // Log to browser console
                    // echo "<script>console.log('glDataDict:', " . json_encode($glDataDict, JSON_PRETTY_PRINT) . ");</?script>";
                }
                // Output the JavaScript call


                                                
                foreach ($data as $header) {
                    $journalId = htmlspecialchars($header['journal_id']);
                    $amount = htmlspecialchars($header['deposit'])-htmlspecialchars($header['withdrawal']);
                    $irn = htmlspecialchars($header['irn']);
                    $amountClass = ($amount < 0) ? 'text-danger' : 'text-success';
                    $formattedAmount = ($amount < 0) ? '(' . number_format(abs($amount), 0) . ')' : number_format($amount, 0);
                    $offsetgl= $glDataDict[$header['offsetgl']]['gl_name'] ?? $header['offsetgl'];

                    $transactionType = ucfirst(htmlspecialchars($header['transaction_type']));
                    $borderClass = ($amount < 0) ? 'orange-border' : 'green-border';

                    echo "<div class='journal-card row align-items-center $borderClass' data-journal-id='$journalId'>";
                    echo "  <div class='col-9'>";
                    echo "      <h5 class='journal-title mb-1'>" . htmlspecialchars($header['cptyname']) . "</h5>";
                    echo "      <h5 class='journal-title mb-1'> " . htmlspecialchars($offsetgl ?? 'N/A') . "</h5>";
                    echo "      <p class='narration mb-1'><strong>Narration:</strong> " . htmlspecialchars($header['narration']) . "</p>";
      
                    echo "      <p class='mb-1'><strong>Date:</strong> " . htmlspecialchars($header['payment_date']) . "</p>";
                    echo "      <p class='mb-1'><strong>Ref #:</strong> " . htmlspecialchars($header['paymentref']) . "</p>";
                    echo "  </div>";
                    echo "  <div class='col-3 text-end'>";
                    echo "      <p class='amount $amountClass'>" . $formattedAmount . "</p>";
                    echo "  </div>";
                    echo "</div>";


                }
            } elseif ($httpCode == 404) {
                echo "<p class='alert alert-warning'>No journal headers found.</p>";
            } else {
                echo "<p class='alert alert-danger'>Error fetching journal data. HTTP Status Code: " . $httpCode . "</p>";
            }

            curl_close($ch);
            ?>
        </div>
    </div>

<div id="pagination-info">
    <p>Total Records: <span id="total-records">0</span></p>
    <p>Total Pages: <span id="total-pages">0</span></p>
    <p>Current Page: <span id="current-page">1</span></p>
</div>

<div id="pagination-controls">
    <button id="prev-page" disabled>Previous</button>
    <button id="next-page">Next</button>
</div>    
<div>
    <label for="filter-dropdown">Filter by GL:</label>
    <select id="filter-dropdown">
        <option value="">Select GL</option>
        <!-- Populate this dropdown with GL options -->
        <?php foreach ($glDataDict as $gl_id => $gl_item): ?>
            <option value="<?= htmlspecialchars($gl_id) ?>"><?= htmlspecialchars($gl_item['gl_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="sort-dropdown">Sort by:</label>
    <select id="sort-dropdown">
        <option value="payment_date">Payment Date</option>
        <option value="offsetgl">Offset GL</option>
        <!-- Add more sorting options as needed -->
    </select>

    <button id="filter-button">Apply Filter & sort</button>
    <!-- <button id="sort-button">Sort</button> -->
</div>

<div>
    <label for="records-per-page">Records per page:</label>
    <input type="number" id="records-per-page" value="1000" min="1">
</div>


<script>
// JavaScript to handle filter and sort actions
document.getElementById('filter-button').addEventListener('click', function() {
    const filterValue = document.getElementById('filter-dropdown').value;
    const sortBy = document.getElementById('sort-dropdown').value;
    const pageSize = document.getElementById('records-per-page').value;
    

    const url = new URL("http://localhost/example/gofin/receipt&payment_list.php");
    
    url.searchParams.append("abid", <?= json_encode($abid) ?>); // Use PHP variable directly in JS context 
    url.searchParams.append("filter", filterValue);
    url.searchParams.append("sort_by", sortBy);
    url.searchParams.append("sort_order", "desc"); // or whatever sorting order you want to set
    url.searchParams.append("page", 1); // Reset to first page on new filter
    url.searchParams.append("page_size", pageSize); // Reset to first page on new filter

    window.location.href = url.toString(); // Redirect to new URL with parameters
});
    // Function to update pagination info
function updatePaginationInfo(data) {
    document.getElementById('total-records').textContent = data.total_count;
    document.getElementById('total-pages').textContent = data.total_pages;
    document.getElementById('current-page').textContent = data.current_page;

    // Enable or disable pagination buttons
    document.getElementById('prev-page').disabled = data.current_page <= 1;
    document.getElementById('next-page').disabled = data.current_page >= data.total_pages;
}

document.getElementById('next-page').addEventListener('click', function() {
        const currentPage = parseInt(document.getElementById('current-page').textContent);
        const totalPages = parseInt(document.getElementById('total-pages').textContent);
        
        if (currentPage < totalPages) {
            const filterValue = document.getElementById('filter-dropdown').value;
            const sortBy = document.getElementById('sort-dropdown').value;
            const pageSize = document.getElementById('records-per-page').value;
            

            const url = new URL("http://localhost/example/gofin/receipt&payment_list.php");
            
            url.searchParams.append("abid", <?= json_encode($abid) ?>); // Use PHP variable directly in JS context 
            url.searchParams.append("filter", filterValue);
            url.searchParams.append("sort_by", sortBy);
            url.searchParams.append("sort_order", "desc"); // or whatever sorting order you want to set
            url.searchParams.append("page", currentPage+1); // Reset to first page on new filter
            url.searchParams.append("page_size", pageSize); // Reset to first page on new filter

            window.location.href = url.toString(); // Redirect to new URL with parameters
        }
    });
document.getElementById('prev-page').addEventListener('click', function() {
        const currentPage = parseInt(document.getElementById('current-page').textContent);
        const totalPages = parseInt(document.getElementById('total-pages').textContent);
        
        if (currentPage > 1) {
            const filterValue = document.getElementById('filter-dropdown').value;
            const sortBy = document.getElementById('sort-dropdown').value;
            const pageSize = document.getElementById('records-per-page').value;
            

            const url = new URL("http://localhost/example/gofin/receipt&payment_list.php");
            
            url.searchParams.append("abid", <?= json_encode($abid) ?>); // Use PHP variable directly in JS context 
            url.searchParams.append("filter", filterValue);
            url.searchParams.append("sort_by", sortBy);
            url.searchParams.append("sort_order", "desc"); // or whatever sorting order you want to set
            url.searchParams.append("page", currentPage-1); // Reset to first page on new filter
            url.searchParams.append("page_size", pageSize); // Reset to first page on new filter

            window.location.href = url.toString(); // Redirect to new URL with parameters
        }
    });
<?php
echo "updatePaginationInfo(" . json_encode($pagedata) . ");";
?>

// Function to filter journal entries by narration
function filterByNarration() {
    const narrationFilterValue = document.getElementById('narration-filter').value.toLowerCase(); // Get the filter value
    const journalCards = document.querySelectorAll('.journal-card'); // Select all journal cards

    journalCards.forEach(card => {
        // Get the narration text from each card using the unique class
        const narrationText = card.querySelector('.narration').textContent.toLowerCase().slice(11).trim(); // Extract text after "Narration:"

        // Check if the narration text includes the filter value
        if (narrationText.includes(narrationFilterValue)) {
            card.style.display = ''; // Show card if it matches
        } else {
            card.style.display = 'none'; // Hide card if it doesn't match
        }
    });
}

// Event listener for narration filter input
document.getElementById('narration-filter').addEventListener('input', filterByNarration);



</script>

    <div class="modal fade" id="journalModal" tabindex="-1" aria-labelledby="journalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="journalModalLabel">Journal Lines</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>GL Nature</th>
                                <th>GL Name</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody id="journalLinesTable">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const journalCards = document.querySelectorAll('.journal-card');
            const journalLinesTable = document.getElementById('journalLinesTable');

            journalCards.forEach(card => {
                card.addEventListener('click', function () {
                    const journalId = this.dataset.journalId;
                    const abid = "<?php echo $abid; ?>";


                    // Build the URL
                    const url = `paymentUI.php?journal_id=${encodeURIComponent(journalId)}&abid=${encodeURIComponent(abid)}`;
                    // Redirect to the URL
                    window.location.href = url;


                });
            });
        });
    </script>
</body>
</html>
