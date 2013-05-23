<?php
$db = new PDO("sqlite:data/proxy.sq3");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$select = $db->query('SELECT * FROM proxy'); //<-- example code

//build relationship graph
$proxies = array();
foreach($select->fetchAll(PDO::FETCH_ASSOC) as $user){
    //generate a shared key between users
    $key = array($user['user'], $user['connected']);
    sort($key);
    $key = implode('-', $key);

    //get the gravatar id
    $user['gravatar'] = 'http://www.gravatar.com/avatar/' . md5(trim(strtolower($user['email'])));
    $user['username'] = substr($user['email'], 0, strpos($user['email'], '@'));
    unset($user['user']);
    unset($user['connected']);
    unset($user['email']);
    
    $proxies[$key][] = $user;
}

echo json_encode(array_values($proxies));