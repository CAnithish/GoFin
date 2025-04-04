<!DOCTYPE html>
<html>
<head>
    <title>Bills</title>
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
        <h1 class="mb-4 text-center">Bills</h1>

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

            $url = "http://127.0.0.1:8000/journal_header_list_bill";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . "?abid=" . $abid);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode == 200) {
                $data = json_decode($response, true);

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
                }

                foreach ($data as $header) {
                    $journalId = htmlspecialchars($header['journal_id']);
                    $amount = htmlspecialchars($header['totinvval']);
                    $irn = htmlspecialchars($header['irn']);
                    $amountClass = ($amount < 0) ? 'text-danger' : 'text-success';
                    $formattedAmount = ($amount < 0) ? '(' . number_format(abs($amount), 0) . ')' : number_format($amount, 0);

                    $transactionType = ucfirst(htmlspecialchars($header['transaction_type']));
                    $borderClass = ($transactionType === "Bill") ? 'orange-border' : 'green-border';

                    echo "<div class='journal-card row align-items-center $borderClass' data-journal-id='$journalId'>";
                    echo "  <div class='col-9'>";
                    echo "      <h5 class='journal-title mb-1'>" . htmlspecialchars($header['cptyname']) . "</h5>";
                    echo "      <p class='mb-1'><strong>Document Date:</strong> " . htmlspecialchars($header['doc_date']) . "</p>";
                    if ($header['source'] === "einvoice") {
                        echo "<a href='einvoice.php?irn=" . urlencode($irn) . "&abid=" . urlencode($abid) . "'><strong>Source: E-Invoice</strong></a>";
                      } else {
                        echo "      <p class='mb-1'><strong>Source:</strong> " . htmlspecialchars($header['source']) . "</p>";
                      }
                      echo "</p>";

                    // echo "<a href='billui.php?journal_id=" . urlencode($journalId) . "&abid=" . urlencode($abid) . "'><strong>Edit</strong></a>";

                    echo "      <p class='mb-1'><strong>Type:</strong> " . $transactionType . "</p>";
                    echo "      <p class='mb-1'><strong>Ref #:</strong> " . htmlspecialchars($header['ref_no']) . "</p>";
                    echo "  </div>";
                    echo "  <div class='col-3 text-end'>";
                    echo "      <p class='amount $amountClass'>" . $formattedAmount . "</p>";
                    echo "  </div>";
                    echo "</div>";


                }
            } elseif ($httpCode == 404) {
                echo "<p class='alert alert-warning'>No bills found.</p>";
            } else {
                echo "<p class='alert alert-danger'>Error fetching journal data. HTTP Status Code: " . $httpCode . "</p>";
            }

            curl_close($ch);
            ?>
        </div>
    </div>

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
        // Function to filter journal entries by narration
        function filterByNarration() {
            const narrationFilterValue = document.getElementById('narration-filter').value.toLowerCase(); // Get the filter value
            const journalCards = document.querySelectorAll('.journal-card'); // Select all journal cards

            journalCards.forEach(card => {
                // Get the narration text from each card using the unique class
                const narrationText = card.querySelector('.journal-title').textContent.toLowerCase().trim(); // Extract text after "Narration:"

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


        document.addEventListener('DOMContentLoaded', function () {
            const journalCards = document.querySelectorAll('.journal-card');
            const journalLinesTable = document.getElementById('journalLinesTable');

            journalCards.forEach(card => {
                card.addEventListener('click', function () {
                    const journalId = this.dataset.journalId;
                    const abid = "<?php echo $abid; ?>";


                    // Build the URL
                    const url = `billui.php?journal_id=${encodeURIComponent(journalId)}&abid=${encodeURIComponent(abid)}`;
                    // Redirect to the URL
                    window.location.href = url;
                    // const journalLines = window[`journalLines_${journalId}`];
                    // const glData = window.glData;

                    // journalLinesTable.innerHTML = '';

                    // journalLines.forEach(line => {
                    //     const gl = glData.find(g => g.gl_id === line.gl_id);
                    //     const row = document.createElement('tr');

                    //     row.innerHTML = `
                    //         <td>${gl ? gl.gl_nature : 'N/A'}</td>
                    //         <td>${gl ? gl.gl_name : 'N/A'}</td>
                    //         <td>${line.amount.toFixed(0)}</td>
                    //     `;

                    //     journalLinesTable.appendChild(row);
                    // });

                    // const modal = new bootstrap.Modal(document.getElementById('journalModal'));
                    // modal.show();


                });
            });
        });
    </script>
</body>
</html>
