<?php
$BOT_TOKEN = '1860008131:AAGhrN8JEtEjcVzxJ5r2AC6rOKRY-0iDNY4';

$paramenter = array(
    "chat_id" => 1405043184,
    "text" => "Masuk yaya"
);

sendData("sendMessage", $paramenter);
// print_r($paramenter); exit;


function sendData ($method, $data)
{
    global $BOT_TOKEN;
    $url = "https://api.telegram.org/bot$BOT_TOKEN/$method";
   

    if (!$curld = curl_init()) {
        exit;
    }

    curl_setopt($curld, CURLOPT_POST, true);
    curl_setopt($curld, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curld, CURLOPT_URL, $url);
    curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($curld);
    curl_close($curld);
    return $output;
    
}
?>