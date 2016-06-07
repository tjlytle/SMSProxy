<?php
require_once __DIR__ . '/../bootstrap.php'; //credentials and such

/**
 * Very Simple Request Router
 */

//some common setup, not really needed for serving the UI, but whatever - can you say premature optimization?
$mongo = new MongoClient(MONGO);
$db = $mongo->proxy;
$basic = new \Nexmo\Client\Credentials\Basic(NEXMO_KEY, NEXMO_SECRET);
$nexmo = new \Nexmo\Client($basic);

if(PIRATE){
    $proxy = new Pirate($nexmo, $db);
} else {
    $proxy = new Proxy($nexmo, $db);
}

//request looks to be from Nexmo
try{
    $inbound = \Nexmo\Message\InboundMessage::createFromGlobals();
    if($inbound->isValid()){
        $proxy->processMessage($inbound);
        return;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

//request look to be for API
if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
    echo  json_encode($proxy->__toArray());
    return;
}

//nothin' special, render admin view
include 'index.phtml';