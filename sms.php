<?php
//Nexmo credentials may be optionally defined elsewhere
defined('NEXMO_KEY') || getenv('NEXMO_KEY') AND define('NEXMO_KEY', getenv('NEXMO_KEY'));
defined('NEXMO_SECRET') || getenv('NEXMO_SECRET') AND define('NEXMO_SECRET', getenv('NEXMO_SECRET'));
defined('NEXMO_FROM') || getenv('NEXMO_FROM') AND define('NEXMO_FROM', getenv('NEXMO_FROM'));

//looks valid?
if(!isset($_POST['text']) OR !isset($_POST['msisdn'])){
    return;
}

//some vars
$user = $_POST['msisdn'];
$text = $_POST['text'];

//db setup
$db = new PDO("sqlite:data/proxy.sq3");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//check if user is already in a proxy
$query = $db->prepare('SELECT connected FROM proxy WHERE user = :user');
$query->bindParam(':user', $user);
$query->execute();

$result = $query->fetch(PDO::FETCH_ASSOC);

if($result){
    if('#end' == strtolower($text)){
        $end = $db->prepare('UPDATE proxy SET connected = NULL WHERE user = :user OR user = :connected');
        $end->bindParam(':user', $user);
        $end->bindParam(':connected', $result['connected']);
        $end->execute();
        sendSms($user, 'Establishing connection...');
        sendSms($result['connected'], 'Establishing connection...');
        return;
    }

    if('stop' == strtolower($text)){
        $stop = $db->prepare('DELETE FROM proxy WHERE user = :user');
        $stop->bindParam(':user', $user);
        $stop->execute();
        
        $end = $db->prepare('UPDATE proxy SET connected = NULL WHERE user = :connected');
        $end->bindParam(':connected', $result['connected']);
        $end->execute();

        sendSms($user, 'Quit. SMS email address to start.');
        sendSms($result['connected'], 'Establishing connection...');
        return;
    }
    
    sendSms($result['connected'], $text);
    return;
}

//new user without email
$email = filter_var($text, FILTER_VALIDATE_EMAIL);
if(!$email){
    sendSms($user, 'Sorry, you need to provide an email first.');
    return;
}

//add new user
$insert = $db->prepare('INSERT INTO proxy (user, email) VALUES (:user, :email)');
$insert->bindParam(':user',  $user);
$insert->bindParam(':email', $text);
$result = $insert->execute();

sendSms($user, 'Establishing connection...');

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