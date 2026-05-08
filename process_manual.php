<?php
include 'dbconfig.php';
require_once 'dompdf/autoload.inc.php'; 
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $purpose = $_POST['purpose_details'];
    $amount = $_POST['amount'];
    
    // Generate a unique Manual Receipt Number
    $receiptNo = "MAN-" . date('Ymd') . "-" . strtoupper(bin2hex(random_bytes(2)));

    // 1. Save to the NEW manual_contributions table
    $stmt = $db->prepare("INSERT INTO manual_contributions (full_name, email, phone_number, purpose_details, amount, receipt_no) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssds", $name, $email, $phone, $purpose, $amount, $receiptNo);
    
    if ($stmt->execute()) {
        // 2. Generate PDF
        $html = "
        <div style='font-family:Arial; border:2px solid #333; padding:20px; width:450px; margin:auto;'>
            <h2 style='text-align:center; color:#1e7e34;'>LAISER HILL SDA CHURCH</h2>
            <h4 style='text-align:center; margin-top:-10px;'>OFFICIAL MANUAL RECEIPT</h4>
            <hr>
            <p><strong>Receipt No:</strong> $receiptNo</p>
            <p><strong>Received From:</strong> $name</p>
            <p><strong>Payment Details:</strong> $purpose</p>
            <h3 style='background:#f4f4f4; padding:10px;'>Total: KES " . number_format($amount, 2) . "</h3>
            <p style='font-size:12px; color:#666;'>Date Issued: " . date('d M, Y H:i') . "</p>
            <p style='text-align:center; font-style:italic;'>God bless you for your contribution.</p>
        </div>";

        try {
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A5', 'portrait');
            $dompdf->render();
            $pdf_content = $dompdf->output();

            // 3. Send Email
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
            $mail->addStringAttachment($pdf_content, "Receipt_$receiptNo.pdf");
            $mail->isHTML(true);
            $mail->Subject = "Manual Contribution Receipt: $receiptNo";
            $mail->Body = "Dear $name, thank you for your contribution. Please find your official receipt attached.";
            
            $mail->send();
            echo "<script>alert('Receipt Sent Successfully!'); window.location='analysis_dashboard.php';</script>";
        } catch (Exception $e) {
            file_put_contents('manual_errors.log', $mail->ErrorInfo . PHP_EOL, FILE_APPEND);
            echo "Data saved, but email failed. Error logged.";
        }
    }
}
?>