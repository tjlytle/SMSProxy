<?php
/**
 * Simple wrapper for HTTP calls.
 *
 * @author Tim Lytle <tim@timlytle.net>
 */
class Nexmo
{
    protected $key;
    protected $secret;

    public function __construct($key, $secret)
    {
        $this->key    = $key;
        $this->secret = $secret;
    }

    public function sendSMS($to, $from, $text)
    {
        $uri = sprintf('http://rest.nexmo.com/sms/json?username=%1$s&password=%2$s&from=%3$s&to=%4$s&text=%5$s', $this->key, $this->secret, $from, $to, urlencode($text));
        $result = file_get_contents($uri);
        $result = json_decode($result);
        return $result;
    }
}