<?php
ob_start();

if (!isset($_COOKIE['userdetails'])) {
    header("Location: http://localhost/example/ablogin.html");
    exit();
}

$userdetails = json_decode($_COOKIE['userdetails'], true);
$abid = isset($userdetails['abid']) ? $userdetails['abid'] : "Key not found";

if ($abid === "Key not found") {
    header("Location: http://localhost/example/ablogin.html");
    exit();
}

$selected_items = isset($_POST['selected_items']) ? json_decode($_POST['selected_items'], true) : [];

if (empty($selected_items)) {
    echo "<p style='color:red;'>No items selected.</p>";
    exit();
}

// Fetch data from FastAPI
$history_url = "http://127.0.0.1:8000/item_journal";
$options = [
    'http' => [
        'method'  => 'GET',
        'header'  => "Content-Type: application/json\r\n",
        'content' => json_encode(['abid' => $abid, 'item_ids' => $selected_items])
    ]
];

$context = stream_context_create($options);
$history_json = file_get_contents($history_url, false, $context);
$history_data = json_decode($history_json, true);

if (!is_array($history_data)) {
    $history_data = [];
}

usort($history_data, function ($a, $b) {
    return strtotime($b['doc_date']) - strtotime($a['doc_date']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Item History</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Item History</h1>
    <table id="historyTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Doc Date</th>
                <th>Product Description</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Unit Price</th>
                <th>Party ID</th>
                <th>Party Name</th>
                <th>Ref No</th>
                <th>Doc Type</th>
                <th>Doc Date</th>
                <th>Status</th>
                <th>Barcode</th>
                <th>Item ID</th>
            </tr>
            <tr class="filters">
                <th><input type="text" class="form-control" placeholder="Search Doc Date"></th>
                <th><input type="text" class="form-control" placeholder="Search Product Description"></th>
                <th><input type="text" class="form-control" placeholder="Search Qty"></th>
                <th><input type="text" class="form-control" placeholder="Search Unit"></th>
                <th><input type="text" class="form-control" placeholder="Search Unit Price"></th>
                <th><input type="text" class="form-control" placeholder="Search Party ID"></th>
                <th><input type="text" class="form-control" placeholder="Search Party Name"></th>
                <th><input type="text" class="form-control" placeholder="Search Ref No"></th>
                <th><input type="text" class="form-control" placeholder="Search Doc Type"></th>
                <th><input type="text" class="form-control" placeholder="Search Doc Date"></th>
                <th><input type="text" class="form-control" placeholder="Search Status"></th>
                <th><input type="text" class="form-control" placeholder="Search Barcode"></th>

                <th><input type="text" class="form-control" placeholder="Search Item ID"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history_data as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['doc_date']) ?></td>
                    <td><?= htmlspecialchars($item['PrdDesc']) ?></td>
                    <td><?= htmlspecialchars($item['Qty']) ?></td>
                    <td><?= htmlspecialchars($item['Unit']) ?></td>
                    <td><?= htmlspecialchars($item['UnitPrice']) ?></td>
                    <td><?= htmlspecialchars($item['cptyid']) ?></td>
                    <td><?= htmlspecialchars($item['cptyname']) ?></td>
                    <td><?= htmlspecialchars($item['ref_no']) ?></td>
                    <td><?= htmlspecialchars($item['doc_type']) ?></td>
                    <td><?= htmlspecialchars($item['doc_date']) ?></td>
                    <td><?= htmlspecialchars($item['journal_status']) ?></td>
                    <td><?= htmlspecialchars($item['Barcde']) ?></td>
                    <td><?= htmlspecialchars($item['item_id']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    var table = $('#historyTable').DataTable({
        "order": [[0, "desc"]]
    });
    
    $('.filters input').on('keyup', function() {
        let colIndex = $(this).parent().index();
        table.column(colIndex).search(this.value).draw();
    });
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>