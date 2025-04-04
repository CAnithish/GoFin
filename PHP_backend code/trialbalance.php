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

// Fetching data from the FastAPI endpoint
$apiUrl = 'http://127.0.0.1:8000/trial_balance?abid='.$abid; // Change the abid as needed
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Grouping data by gl_nature
$groupedData = [];
foreach ($data as $item) {
    $groupedData[$item['gl_nature']][] = $item;
}

// Function to calculate totals
function calculateTotal($items) {
    return array_reduce($items, function ($carry, $item) {
        return $carry + $item['total_amount'];
    }, 0);
}

// Calculate totals for balance sheet and income statement
$totalAssets = calculateTotal($groupedData['asset'] ?? []);
$totalLiabilities = calculateTotal($groupedData['liability'] ?? []);
$totalIncome = calculateTotal($groupedData['income'] ?? []);
$totalExpenses = calculateTotal($groupedData['expense'] ?? []);

// Adjust signs: Liabilities and Income are positive; Expenses are negative

$adjustedIncome = -($totalIncome);
$adjustedExpenses = -($totalExpenses);
$netProfit = $adjustedIncome + $adjustedExpenses; // Income - Expenses (negative expenses)
$adjustedLiabilities = -($totalLiabilities)+$netProfit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet and Income Statement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
        }
        h2 {
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .category {
            cursor: pointer;
            background-color: #007bff;
            color: white;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .details {
            display: none; /* Hidden by default */
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
        }
        .total {
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<h1>Financial Statements</h1>

<h2>Balance Sheet</h2>

<table>
    <thead>
        <tr>
            <th>Liabilities</th>
            <th>Amount</th>
            <th>Assets</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        // Get count of rows for each section
        $liabilityCount = !empty($groupedData['liability']) ? count($groupedData['liability']) : 0;
        $assetCount = !empty($groupedData['asset']) ? count($groupedData['asset']) : 0;

        // Include Net Profit in the Liabilities count
        $liabilityCount++; 

        // Determine the max row count
        $maxRows = max($liabilityCount, $assetCount);

        // Ensure equal number of rows by adding placeholders
        $liabilities = array_values($groupedData['liability'] ?? []);
        $assets = array_values($groupedData['asset'] ?? []);

        for ($i = 0; $i < $maxRows; $i++): ?>
            <tr class="category">
                <td>
                    <?php if (!empty($liabilities[$i])): ?>
                        <span onclick="toggleDetails('<?php echo htmlspecialchars($liabilities[$i]['gl_id']); ?>')">
                            <?php echo htmlspecialchars($liabilities[$i]['gl_name']); ?>
                        </span>
                    <?php elseif ($i == $liabilityCount - 1): // Net Profit row ?>
                        <span onclick="toggleDetails('netProfit')">Net Profit</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo !empty($liabilities[$i]) 
                        ? number_format(-($liabilities[$i]['total_amount']), 2) 
                        : ($i == $liabilityCount - 1 ? number_format($netProfit, 2) : ''); ?>
                </td>
                <td>
                    <?php echo !empty($assets[$i]) 
                        ? htmlspecialchars($assets[$i]['gl_name']) 
                        : ''; ?>
                </td>
                <td>
                    <?php echo !empty($assets[$i]) 
                        ? number_format(($assets[$i]['total_amount']), 2) 
                        : ''; ?>
                </td>
            </tr>

            <?php if (!empty($liabilities[$i])): ?>
                <tr class="details" id="<?php echo htmlspecialchars($liabilities[$i]['gl_id']); ?>">
                    <td colspan="2">Description: <?php echo htmlspecialchars($liabilities[$i]['description']); ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php elseif ($i == $liabilityCount - 1): // Net Profit Description ?>
                <tr class="details" id="netProfit">
                    <td colspan="2">This amount represents the net profit from the income statement.</td>
                    <td colspan="2"></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($assets[$i])): ?>
                <tr class="details" id="<?php echo htmlspecialchars($assets[$i]['gl_id']); ?>">
                    <td colspan="2"></td>
                    <td colspan="2">Description: <?php echo htmlspecialchars($assets[$i]['description']); ?></td>
                </tr>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Total Liabilities & Total Assets on the Same Row -->
        <tr>
            <td class="total">Total Liabilities:</td>
            <td class="total"><?php echo number_format($adjustedLiabilities, 2); ?></td>
            <td class="total">Total Assets:</td>
            <td class="total"><?php echo number_format(($totalAssets), 2); ?></td>
        </tr>
    </tbody>
</table>



<h2>Income Statement</h2>

<table style="margin-top: 20px;">
    <thead>
        <tr>
            <th>Income/Expense</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>

    <?php 
    // Display Income
    if (!empty($groupedData['income'])):
        foreach ($groupedData['income'] as $item): ?>
            <tr class="category" onclick="toggleDetails('<?php echo htmlspecialchars($item['gl_id']); ?>')">
                <td><?php echo htmlspecialchars($item['gl_name']); ?></td>
                <td><?php echo number_format((-$item['total_amount']), 2); ?></td> <!-- Show income as positive -->
            </tr>
            <tr class="details" id="<?php echo htmlspecialchars($item['gl_id']); ?>">
                <td colspan="2">Description: <?php echo htmlspecialchars($item['description']); ?></td>
            </tr>
         <?php endforeach; ?>
         <!-- Total Income -->
         <tr class="total">
             <td>Total Income:</td><td><?php echo number_format((-$totalIncome), 2); ?></td> <!-- Show total income as positive -->
         </tr>

     <?php endif; ?>

     <?php 
     // Display Expenses
     if (!empty($groupedData['expense'])):
         foreach ($groupedData['expense'] as $item): ?>
             <tr class="category" onclick="toggleDetails('<?php echo htmlspecialchars($item['gl_id']); ?>')">
                 <td><?php echo htmlspecialchars($item['gl_name']); ?></td>
                 <td><?php echo number_format(-($item['total_amount']), 2); ?></td> <!-- Show expenses as negative -->
             </tr>
             <tr class="details" id="<?php echo htmlspecialchars($item['gl_id']); ?>">
                 <td colspan="2">Description: <?php echo htmlspecialchars($item['description']); ?></td>
             </tr>

         <?php endforeach; ?>
         <!-- Total Expenses -->
         <tr class="total">
             <td>Total Expenses:</td><td><?php echo number_format((-$totalExpenses), 2); ?></td> <!-- Show total expenses as negative -->
         </tr>

     <?php endif; ?>

     <!-- Net Profit/Loss -->
     <tr class="total">
         <th>Net Profit/Loss:</th><th><?php echo number_format(($netProfit), 2); ?></th><!-- Show net profit as positive -->
     </tr>

    </tbody>
</table>

<script>
// Function to toggle details visibility
function toggleDetails(id) {
    const detailsDiv = document.getElementById(id);
    if (detailsDiv.style.display === "none" || detailsDiv.style.display === "") {
        detailsDiv.style.display = "block";
        event.currentTarget.querySelector('i').classList.remove('fa-plus');
        event.currentTarget.querySelector('i').classList.add('fa-minus');
    } else {
        detailsDiv.style.display = "none";
        event.currentTarget.querySelector('i').classList.remove('fa-minus');
        event.currentTarget.querySelector('i').classList.add('fa-plus');
    }
}
</script>

</body>
</html>

