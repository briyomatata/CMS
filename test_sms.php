<?php
$username = 'sandbox'; 
$apiKey   = 'atsk_11bd734a131a7787d167faed10f0c0e106bafea8db88337ac97358b86ba1ff6a9203a175'; 
$myPhone  = '+254715599743'; 
$message  = "Laiser Hill SDA: Test SMS Successful!";

$url = "https://api.sandbox.africastalking.com/version1/messaging";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Content-Type: application/x-www-form-urlencoded",
    "apikey: $apiKey" // Ensure 'apiKey' is spelled exactly like this
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => $username,
    'to'       => $myPhone,
    'message'  => $message
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// THESE TWO LINES PREVENT 401/CONNECTION ISSUES ON LOCALHOST
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status Code: $httpCode <br>";
echo "Response: $response";
?>