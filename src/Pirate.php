<?php
/**
 * @author Tim Lytle <tim@timlytle.net>
 */
class Pirate extends Proxy
{
    public function sendSMS($to, $from, $text)
    {
        try{
            $this->log($to, 'trying to translate');
            $translate = file_get_contents('http://isithackday.com/arrpi.php?' . http_build_query(array('format' => 'json', 'text' => $text)));
            $translate = json_decode($translate, true);

            if(empty($translate['translation']['pirate'])){
                throw new Exception('translation failed');
            }

            parent::sendSMS($to, $from, $translate['translation']['pirate']);
        } catch (Exception $e) {
            $this->log($to, $e->getMessage());
            parent::sendSMS($to, $text);
        }

    }
}