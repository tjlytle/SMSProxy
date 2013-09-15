<?php
/**
 * @author Tim Lytle <tim@timlytle.net>
 */
if(!isset($_REQUEST['msisdn'], $_REQUEST['text'])){
    return;
}

require_once __DIR__ . '/../bootstrap.php';

$mongo = new MongoClient(MONGO);
$db = $mongo->proxy;

$nexmo = new Nexmo(NEXMO_KEY, NEXMO_SECRET);

$proxy = new Proxy($nexmo, NEXMO_FROM, $db);

try{
    $proxy->processMessage($_REQUEST['msisdn'], $_REQUEST['text']);
} catch (Exception $e) {
    error_log($e);
}

