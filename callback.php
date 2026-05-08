<?php
include 'dbconfig.php';
require_once 'dompdf/autoload.inc.php'; 
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");

// 1. CAPTURE DATA FROM SAFARICOM
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
file_put_contents('callback_log.txt', $json_data . PHP_EOL, FILE_APPEND);

$callback = $data['Body']['stkCallback'] ?? null;
$ResultCode = $callback['ResultCode'] ?? 1;
$CheckoutRequestID = $callback['CheckoutRequestID'] ?? '';

if ($ResultCode == 0 && $callback) {
    // 2. EXTRACT RECEIPT AND AMOUNT
    $MpesaReceipt = "";
    $Amount = 0;
    foreach ($callback['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] == 'MpesaReceiptNumber') $MpesaReceipt = $item['Value'];
        if ($item['Name'] == 'Amount') $Amount = $item['Value'];
    }

    // 3. UPDATE THE DATABASE
    $upd = $db->prepare("UPDATE contributions SET mpesa_receipt = ?, status = 'completed' WHERE checkout_request_id = ?");
    $upd->bind_param("ss", $MpesaReceipt, $CheckoutRequestID);
    
    if($upd->execute()) {
        // 4. FETCH EMAIL & ITEM DETAILS FOR THE RECEIPT
        $stmt = $db->prepare("SELECT email, full_name, purpose_details FROM contributions WHERE checkout_request_id = ?");
        $stmt->bind_param("s", $CheckoutRequestID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $email = $user['email'];
            $name = $user['full_name'];
            $purpose_string = $user['purpose_details'];
            $currentDate = date('d/m/Y');

            // 5. GENERATE TABLE ROWS FROM PURPOSE DETAILS
            $items_array = explode(", ", $purpose_string);
            $table_rows = "";
            foreach ($items_array as $item) {
                // Splits "Tithe (KES 500)" into "Tithe" and "500"
                preg_match('/^(.*) \(KES (.*)\)$/', $item, $matches);
                $itemName = $matches[1] ?? $item;
                $itemAmt = $matches[2] ?? '0.00';
                $table_rows .= "
                    <tr>
                        <td style='border: 1px solid #000; padding: 8px; text-align: left;'>$itemName</td>
                        <td style='border: 1px solid #000; padding: 8px; text-align: right;'>$itemAmt</td>
                    </tr>";
            }

            // 6. BUILD THE LAISER HILL SDA HTML RECEIPT
            $html_receipt = "
            <div style='font-family: Arial, sans-serif; border: 2px solid #000; padding: 20px; width: 450px; margin: auto;'>
                <div style='text-align: center; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 16px;'>SEVENTH-DAY ADVENTIST CHURCH</h2>
                    <h3 style='margin: 5px 0; font-size: 14px;'>LAISER HILL SDA</h3>
                    <p style='margin: 0; font-size: 11px;'>M-Pesa Receipt: $MpesaReceipt</p>
                </div>

                <table style='width: 100%; font-size: 13px; margin-bottom: 15px;'>
                    <tr>
                        <td><strong>NAME:</strong> <span style='border-bottom: 1px dotted #000;'>$name</span></td>
                        <td style='text-align: right;'><strong>No.</strong> <span style='color: red;'>$MpesaReceipt</span></td>
                    </tr>
                    <tr>
                        <td><strong>DATE:</strong> <span style='border-bottom: 1px dotted #000;'>$currentDate</span></td>
                        <td></td>
                    </tr>
                </table>

                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;'>
                    <thead>
                        <tr style='background-color: #f2f2f2;'>
                            <th style='border: 1px solid #000; padding: 8px;'>Description</th>
                            <th style='border: 1px solid #000; padding: 8px;'>Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        $table_rows
                        <tr style='font-weight: bold;'>
                            <td style='border: 1px solid #000; padding: 8px; text-align: right;'>TOTAL</td>
                            <td style='border: 1px solid #000; padding: 8px; text-align: right;'>" . number_format($Amount, 2) . "</td>
                        </tr>
                    </tbody>
                </table>

                <div style='margin-top: 20px; font-size: 13px;'>
                    <p><strong>Treasurer:</strong> <span style='border-bottom: 1px dotted #000;'>Brian Okanga</span></p>
                    <p style='font-size: 9px; text-align: center; color: #555; margin-top: 15px;'>
                        
                    </p>
                </div>
            </div>";

            // 7. GENERATE PDF & SEND EMAIL
            try {
                $dompdf = new Dompdf();
                $dompdf->loadHtml($html_receipt);
                $dompdf->setPaper('A5', 'portrait');
                $dompdf->render();
                $pdf_output = $dompdf->output();

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'otienobriyo@gmail.com';
                $mail->Password = 'wxkdsojvxdaagzuo';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('otienobriyo@gmail.com', 'Laiser Hill SDA Treasurer');
                $mail->addAddress($email);
                $mail->addStringAttachment($pdf_output, "LaiserHill_Receipt_$MpesaReceipt.pdf");

                $mail->isHTML(true);
                $mail->Subject = "Your Laiser Hill SDA Contribution Receipt";
                $mail->Body = "Dear $name,<br><br>Please find attached your official receipt for your contribution of KES " . number_format($Amount, 2) . ".<br><br>God bless you.";
                $mail->send();
            } catch (Exception $e) {
                file_put_contents('mail_errors.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }
    }
}
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
?>