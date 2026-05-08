<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. DATABASE CONFIG
if (file_exists('dbconfig.php')) {
    include 'dbconfig.php';
} else {
    die("Error: dbconfig.php not found in " . __DIR__);
}

include 'accessToken.php';
date_default_timezone_set('Africa/Nairobi');

// 2. CAPTURE & AUTO-FORMAT PHONE NUMBER
$phone = $_POST['phone'] ?? '';
$phone = preg_replace('/^(?:\+254|0|^)/', '254', $phone);

// 3. CAPTURE USER & MULTI-PAYMENT DATA
$name          = $_POST['name'] ?? 'Anonymous';
$email         = $_POST['email'] ?? '';
$selectedItems = $_POST['items'] ?? []; // THIS WAS MISSING
$allAmounts    = $_POST['amounts'] ?? []; 
$otherPurpose  = $_POST['other_purpose_name'] ?? 'Other';
$grandTotal    = $_POST['total_amount'] ?? 0;

// BUILD THE ITEMIZED LIST
$detailsArray = [];
foreach ($selectedItems as $item) {
    $amt = $allAmounts[$item] ?? 0;
    
    // If the item is 'Other', use the custom name provided by the user
    $displayName = ($item === 'Other') ? $otherPurpose : $item;
    $detailsArray[] = "$displayName (KES $amt)";
}

$purpose_details = implode(", ", $detailsArray);

// Determine the primary category for the 'category' column
$category = (count($selectedItems) === 1) ? $selectedItems[0] : "Multiple";
if ($category === "Other") { $category = $otherPurpose; }

// 4. MPESA CONFIG
$processrequestUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$callbackurl = 'https://7e28-41-90-172-249.ngrok-free.app/Drops/callback.php'; 

$passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
$BusinessShortCode = '174379';
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $passkey . $Timestamp);

// 5. SEND REQUEST
$stkpushheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];
$curl_post_data = array(
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $grandTotal, 
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackurl,
    'AccountReference' => 'Church Contribution',
    'TransactionDesc' => substr($purpose_details, 0, 12) 
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $processrequestUrl);
curl_setopt($curl, CURLOPT_HTTPHEADER, $stkpushheader);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));

$curl_response = curl_exec($curl);
$data = json_decode($curl_response);

// 6. HANDLE RESPONSE & SAVE
if (isset($data->ResponseCode) && $data->ResponseCode == "0") {
    $CheckoutRequestID = $data->CheckoutRequestID;

    // SAVE TO DATABASE
    $query = $db->prepare("INSERT INTO contributions (full_name, email, phone, amount, category, purpose_details, checkout_request_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $query->bind_param("sssdsss", $name, $email, $phone, $grandTotal, $category, $purpose_details, $CheckoutRequestID);
    
    if ($query->execute()) {
        echo "Success! Please check your phone ($phone) for the KES $grandTotal PIN prompt.";
    } else {
        echo "Database Error: " . $db->error;
    }
} else {
    echo "M-Pesa Error: " . ($data->errorMessage ?? "Invalid request. Ensure all fields are filled.");
}
?>