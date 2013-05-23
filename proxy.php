<?php
//Nexmo credentials may be optionally defined elsewhere
defined('NEXMO_KEY') || getenv('NEXMO_KEY') AND define('NEXMO_KEY', getenv('NEXMO_KEY'));
defined('NEXMO_SECRET') || getenv('NEXMO_SECRET') AND define('NEXMO_SECRET', getenv('NEXMO_SECRET'));
defined('NEXMO_FROM') || getenv('NEXMO_FROM') AND define('NEXMO_FROM', getenv('NEXMO_FROM'));

//simple daemon type thing
$run = true;
$db = new PDO("sqlite:data/proxy.sq3");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$select = $db->prepare('SELECT user FROM proxy WHERE connected IS NULL');
$update = $db->prepare('UPDATE proxy SET connected = ? WHERE user = ?');

while(true == $run){
    error_log('looking for peoples');
    $select->execute();
    $results = $select->fetchAll(PDO::FETCH_ASSOC);

    if(!$results OR count($results) < 2){
        error_log('waiting for a bit');
        sleep(10);
        continue;
    }
    
    shuffle($results);
    $connect = null;
    
    foreach($results as $result){
        $user = $result['user'];
        if(!$connect){
            $connect = $user;
            continue;
        }
        
        error_log('connecting ' . $connect . ' to ' . $user);
        $update->execute(array($user, $connect));
        $update->execute(array($connect, $user));

        sendSms($user, '...connected...#end to leave.');
        sendSms($connect, '...connected...#end to leave.');
        
        $connect = null;

    }
}

function sendSms($to, $message){
    error_log('sent to: ' . $to . ' message: ' . $message);
    $uri = sprintf('http://rest.nexmo.com/sms/json?username=%1$s&password=%2$s&from=%3$s&to=%4$s&text=%5$s', NEXMO_KEY, NEXMO_SECRET, NEXMO_FROM, $to, urlencode($message));
    $result = file_get_contents($uri);
    $result = json_decode($result);
    foreach($result->messages as $message){
        if(isset($message->{'error-text'})){
            error_log($message->{'error-text'});
        }
    }
}