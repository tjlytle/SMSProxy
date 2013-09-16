<?php
require_once __DIR__ . '/../bootstrap.php'; //credentials and such

/**
 * Very Simple Request Router
 *
 * @author Tim Lytle <tim@timlytle.net>
 */

//request looks to be from Nexmo
$request = array_merge($_GET, $_POST); //method configurable via Nexmo API / Dashboard
if(isset($request['msisdn'], $request['text'])){
    $mongo = new MongoClient(MONGO);
    $db = $mongo->proxy;

    $nexmo = new Nexmo(NEXMO_KEY, NEXMO_SECRET);

    $proxy = new Proxy($nexmo, NEXMO_FROM, $db);

    try{
        $proxy->processMessage($_REQUEST['msisdn'], $_REQUEST['text']);
    } catch (Exception $e) {
        error_log($e);
    }
    return;
}

//request look to be for API
if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
    $mongo = new MongoClient(MONGO);
    $db = $mongo->proxy;

    $nexmo = new Nexmo(NEXMO_KEY, NEXMO_SECRET);

    $proxy = new Proxy($nexmo, NEXMO_FROM, $db);

    error_log('angular request');
    echo  json_encode($proxy->__toArray());
    return;
}

//nothin' special, render admin view
include 'index.phtml';