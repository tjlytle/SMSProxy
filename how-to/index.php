<?php
// accept either get or post
$request = array_merge($_GET, $_POST);

//check if the request is from Nexmo
if(!isset($request['msisdn']) OR !isset($request['to']) OR !isset($request['text'])){
    error_log('not inbound message');
    return; // nothing to do
}

require_once __DIR__ . '/../config.php';

$proxy = array(
    array('14845551212', '18125551212')
);

$found = false;
foreach($proxy as $pair){
    // look for the sender
    if(in_array($request['msisdn'], $pair)){
        // find the other number (not the sender)
        foreach($pair as $number){
            if($number != $request['msisdn']){
                $found = $number;
                break 2;
            }
        }
    }
}

if(!$found){
    error_log('number not in proxy');
    return;
}

$url = 'https://rest.nexmo.com/sms/json?' . http_build_query(array(
        'api_key' => NEXMO_KEY,
        'api_secret' => NEXMO_SECRET,
        'to' => $found,
        'from' => $request['to'],
        'text' => $request['text']
    ));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
curl_close($ch);

error_log($result);

$data = json_decode($result, true);
if(!isset($data['messages'])){
    error_log('unknown API response');
}

foreach($data['messages'] as $message){
    if(0 != $message['status']){
        error_log('API Error: ' . $message['error-text']);
    }
}
