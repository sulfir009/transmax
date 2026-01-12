<?php

require_once('LiqPay.php');
require_once('payment_keys.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/app/services/Site.php');


function logToFile($message) {
    $logFile = $_SERVER['DOCUMENT_ROOT'] . '/log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}
function createPayment($orderId, $total_sum, $description, $public_key, $private_key) {

    $Site = new Tours();
    $liqpay = new LiqPay($public_key, $private_key);

    $data = array(
        'public_key' => $public_key,
        'action' => 'pay',
        'amount' => $total_sum,
        'currency' => 'UAH',
        'description' => $description,
        'order_id' => $orderId,
        'version' => '3',
        'server_url' => 'https://maxtransltd.com/public/pages/liqPaycallback.php',
        'result_url' => 'https://maxtransltd.com/dyakuyu-za-bronyuvannya-biletu',
    );

    $encoded_data = base64_encode(json_encode($data));
    $signature = base64_encode(sha1($private_key . $encoded_data . $private_key, true));

    header('Content-Type: application/json');
    echo json_encode([
        'data' => $encoded_data,
        'signature' => $signature
    ]);

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['orderId'];
    $total_sum = $_POST['totalSum'];
    $description = $_POST['description'];

    logToFile($orderId);
    logToFile($total_sum);
    logToFile($description);


    createPayment($orderId, $total_sum, $description, $public_key, $private_key);
}

?>