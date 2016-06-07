<?php
//Nexmo credentials may be optionally defined elsewhere

if(file_exists(__DIR__ . '/.local.php')){
    require_once __DIR__ . '/.local.php';
}

// try environment
defined('NEXMO_KEY')    || (getenv('NEXMO_KEY')    AND define('NEXMO_KEY', getenv('NEXMO_KEY')));
defined('NEXMO_SECRET') || (getenv('NEXMO_SECRET') AND define('NEXMO_SECRET', getenv('NEXMO_SECRET')));
defined('MONGO')        || (getenv('MONGO')        AND define('MONGO', getenv('MONGO')));

// use pirate speak?
defined('PIRATE') || define('PIRATE', '11/19' == date('m/d'));