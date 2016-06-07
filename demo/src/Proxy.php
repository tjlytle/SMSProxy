<?php
class Proxy
{
    const COLLECTION = 'chats';

    /**
     * @var Nexmo\Client
     */
    protected $nexmo;

    /**
     * @var MongoDB
     */
    protected $db;

    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * Create a new proxy service.
     *
     * @param Nexmo $nexmo
     * @param MongoDB $db
     * @param string $collection
     */
    public function __construct(Nexmo\Client $nexmo, MongoDB $db, $collection = self::COLLECTION)
    {
        $this->nexmo = $nexmo;
        $this->db = $db;
        $this->collection = $db->$collection;
    }

    /**
     * A user would like to chat, assign them to another user (if there's one waiting), or just add them to their own
     * chat and wait for another user.
     *
     * @param string $number User's Number
     * @param string $from   Proxy Number (the number the user sent the sms to)
     * @param string $email  User's email (for gravatar)
     */
    public function startChat($number, $from, $email)
    {
        $this->log($number, 'starting chat');
        $number = $this->validateNumber($number);

        $eparts = explode('@', $email);

        //new user
        $user = array(
            'proxy'   => $from,
            'number'  => $number,
            'email'   => $email,
            'domain'  => $eparts[1],
            'user'    => $eparts[0],
            'md5'     => md5(trim(strtolower($email))),
            'created' => new MongoDate()
        );

        //find open chat from a different domain, and add the new number
        $chat = $this->getChat(
            array('users' => array('$size' => 1), 'active' => true, 'users.domain' => ['$ne' => $eparts[1]]),
            array('$push' => array('users' => $user))
        );

        //send message to both users
        if($chat){
            $this->log($number, 'chat found, user added');
            foreach($chat['users'] as $user){
                $this->sendSMS($user['number'], $user['proxy'], 'Connected, text #end to stop.');
            }
            return;
        }

        //create open chat
        $this->log($number, 'creating a new chat');
        $chat = array('users' => array($user), 'active' => true);
        $this->collection->insert($chat);

        //send waiting message
        $this->sendSMS($user['number'], $user['proxy'], 'Waiting for another user...');
    }

    /**
     * Mark the chat as ended, and let both users know.
     *
     * @param $number
     */
    public function endChat($number)
    {
        //close chat
        $this->log($number, 'ending chat');
        $chat = $this->getChat($number, array('$set' => array('active' => false)));

        //send confirm to users
        if($chat){
            foreach($chat['users'] as $user){
                $this->sendSMS($user['number'], $user['proxy'], 'Thanks for chatting. Text your email address to start chat.');
            }
        }
    }

    /**
     * Check if the number is in an active chat, wants to start a chat, or has no idea what's going on.
     *
     * @param string $number  User's number
     * @param string $from    Inbound number
     * @param string $message SMS Body
     */
    public function processMessage(\Nexmo\Message\InboundMessage $inboundMessage)
    {
        $number = $inboundMessage->getFrom();
        $from   = $inboundMessage->getTo();
        $message = $inboundMessage->getBody();
        
        $this->log($number, 'processing: ' . $message);
        $message = array(
            'number'  => $number,
            'message' => $message,
            'created' => new MongoDate()
        );

        $chat = $this->getChat($number, array('$push' => array('messages' => $message)));

        if($chat){
            $this->log($number, 'chat found');
            //check for commands
            switch(strtolower($message['message'])){
                case '#end':
                    $this->endChat($number);
                    break;
                default:
                    foreach($chat['users'] as $user){
                        if($user['number'] == $number){
                            continue;
                        }
                        $this->sendSMS($user['number'], $user['proxy'], $message['message']);
                    }
                    break;
            }
            return;
        }

        $email = filter_var($message['message'], FILTER_VALIDATE_EMAIL);
        if($email){
            $this->startChat($number, $from, $email);
            return;
        }

        $this->log($number, 'chat not found, sending help');
        $this->sendSMS($number, $from, 'To chat, text your email address.');
    }

    /**
     * Pull a specific chat from storage, optionally make an update to the chat before returning it. Default is to
     * lookup by number, but if an array is passed, that will be used as the query.
     *
     * @param string|array $number
     * @param array $update
     * @return array|null
     */
    protected function getChat($number, $update = null)
    {
        //number can optionally be a query
        if(!is_array($number)){
            $query = array('users.number' => $number, 'active' => true);
        } else {
            $query = $number;
        }

        //no update, just return the query
        if(!$update){
            return $this->collection->findOne($query);
        }

        //do a find and modify
        return $this->collection->findAndModify(
            $query,
            $update,
            null,
            array('new' => true)
        );
    }

    /**
     * Get all active chats.
     */
    protected function getChats()
    {
        return $this->collection->find(array('active' => true));
    }

    /**
     * Could be used to validate number (is a customer, is in the right country, etc). Should throw an exception on
     * failure.
     *
     * @param string $number
     * @return string
     */
    protected function validateNumber($number)
    {
        return $number;
    }

    /**
     * Send a message to the number. Pretty simple really.
     *
     * @param $to
     * @param $from
     * @param $text
     */
    protected function sendSMS($to, $from, $text)
    {
        $this->log($to, 'sending message: ' . $text);
        $message = $this->nexmo->message()->send([
            'to' => $to,
            'from' => $from,
            'text' => $text
        ]);

        $this->log($to, $message['message-id']);
    }

    /**
     * Simple log wrapper.
     *
     * @param string $number
     * @param string $text
     */
    protected function log($number, $text)
    {
        error_log("[$number] $text");
    }

    /**
     * Get a serializable snapshot of the proxy. Do some data manipulation to make things easier client side.
     *
     * For PHP > 5.4 JsonSerializable would be used.
     */
    public function __toArray()
    {
        return array_map(function($data){
            //make friendly ids
            $data['id'] = (string) $data['_id'];
            unset($data['_id']);

            //add user info to each message log (this could be refactored)
            $userMap = array();
            foreach($data['users'] as $user){
                $userMap[$user['number']] = $user;
            }

            //add the user info and update the timestamps
            if(isset($data['messages'])){
                foreach($data['messages'] as $index => $message){
                    $data['messages'][$index]['user'] = $userMap[$message['number']];
                    $data['messages'][$index]['created'] = date('r', $message['created']->sec);
                }
            }

            return $data;
        }, iterator_to_array($this->getChats()));
    }
}