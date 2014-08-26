# [How-To] Proxying SMS

[Animation of the technical implementation](http://prezi.com/1gs_qvnegmgn/how-to-proxy-sms-with-audio/?utm_source=prezi-view&utm_medium=ending-bar&utm_content=Title-link&utm_campaign=ending-bar-tryout).

## Use Case
What happens when two of your users want to easily message each other, but they don't want to give out their phone 
numbers.

Maybe it's an ecommerce platform, and a buyer has a question for a seller. Perhaps it's a freelance site, and a client 
wants to message a potential contractor.

Whatever the case, you want to allow your users the communication channel they're already familiar with, respond to 
quickly, and works on any phone - while protecting their privacy.

Using a Nexmo virtual number, you can easily mask user to user conversations, accomplishing both.

## How-To
Here's how to implement a simple number proxy in PHP. Let's start with a simple array associating pairs of numbers.

    $proxy = array(
        array('14843472194', '18123276810')
    );

[*View in Context*](https://github.com/Nexmo/Proxy/blob/master/how-to/index.php#L13-L15)

Now, when an inbound message is received from Nexmo, we'll check that array for sender's number (the `msisdn`). If it's 
not found, nothing happens, we just eat the message.

    $found = false;
    foreach($proxy as $pair){
        // look for the sender
        if(in_array($request['msisdn'], $pair)){
            // find the other number (not the sender)
            foreach($pair as $number){
                if($number != $request['msisdn']){
                    $found = $number;
                    break 2;
                }
            }
        }
    }
    
    if(!$found){
        error_log('number not in proxy');
        return;
    }

[*View in Context*](https://github.com/Nexmo/Proxy/blob/master/how-to/index.php#L17-L34)

If it is found, we'll use the paired number as the `to` for an outbound SMS message. We set the `text` to the text of 
the inbound message, and the `from` is the same number to which the inbound message was sent. An HTTP call to the 
Nexmo API is all it takes to send the message.

    $url = 'https://rest.nexmo.com/sms/json?' . http_build_query(array(
            'api_key' => NEXMO_KEY,
            'api_secret' => NEXMO_SECRET,
            'to' => $found,
            'from' => $request['to'],
            'text' => $request['text']
        ));
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);

[*View in Context*](https://github.com/Nexmo/Proxy/blob/master/how-to/index.php#L36-L47)

With that, the conversation has been proxied. Both users are sending messages to the same Nexmo virtual number, and 
both users are receiving messages from that same number. But neither user knows the other's number.

Just point one of your Nexmo numbers at [this script](https://github.com/Nexmo/Proxy/blob/master/how-to/index.php)
to see it in action.

## Next Steps
In the real world, the array lookup would be replaced with some call to persistent storage where you track what users 
are in a proxied conversation. 

With a single virtual number, you can proxy multiple one-to-one conversations. If a single user needs to be in more 
than one proxied conversation, the logic changes - but not significantly. 

Instead of just looking up the proxy pair using the sender's number, the proxied conversation is identified by both 
the sender's number and the virtual number the message was sent to.

Now you can proxy multiple conversations involving the same user. This requires a pool of virtual numbers, but only 
as many as the number of concurrent conversations you expect a single user to maintain.

## Demo Application
This example is pretty simple, but there's also a full - albeit silly - demo application based on the same concept. 

It allows users to SMS their email address and start an proxied chat with another random user. A simple web interface 
shows the proxied connection and the chat logs.

Get the demo running on [PHP](https://github.com/Nexmo/Proxy/tree/master/demo#setup) and 
[take it for a spin](https://github.com/Nexmo/Proxy/tree/master/demo#usage).
