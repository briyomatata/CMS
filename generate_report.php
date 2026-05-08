<?php
include 'dbconfig.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Fetch all successful contributions
$query = "SELECT full_name, amount, purpose_details, mpesa_receipt, created_at 
          FROM contributions 
          WHERE status = 'completed' 
          ORDER BY created_at DESC";
$result = $db->query($query);

// 2. Prepare HTML for the PDF
$html = "
<style>
    body { font-family: sans-serif; font-size: 12px; }
    .header { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .total { text-align: right; font-weight: bold; margin-top: 20px; font-size: 14px; }
</style>

<div class='header'>
    <h2>LAISER HILL SDA CHURCH</h2>
    <h3>Financial Collection Report</h3>
    <p>Generated on: " . date('d M, Y H:i') . "</p>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Name</th>
            <th>Breakdown</th>
            <th>M-Pesa</th>
            <th>Amount (KES)</th>
        </tr>
    </thead>
    <tbody>";

$grandTotal = 0;
while ($row = $result->fetch_assoc()) {
    $grandTotal += $row['amount'];
    $date = date('d/m/y', strtotime($row['created_at']));
    $html .= "
        <tr>
            <td>$date</td>
            <td>{$row['full_name']}</td>
            <td>{$row['purpose_details']}</td>
            <td>{$row['mpesa_receipt']}</td>
            <td style='text-align:right;'>" . number_format($row['amount'], 2) . "</td>
        </tr>";
}

$html .= "
    </tbody>
</table>
<div class='total'>Grand Total Collected: KES " . number_format($grandTotal, 2) . "</div>";

// 3. Initialize Dompdf and stream the PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Laiser_Hill_SDA_Report_" . date('Ymd') . ".pdf", ["Attachment" => true]);
?>