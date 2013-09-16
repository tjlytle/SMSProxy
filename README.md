# SMS Proxy

A simple example of proxying user's phone numbers through a common virtual number. Useful when connecting users without
a prior relationship (discussing a sale, questions about a listing, etc).

## Requirements

- PHP 5.3+
- [MongoDB Extension](http://php.net/manual/en/mongo.installation.php)
- Mongo Database
- [Nexmo Account](https://dashboard.nexmo.com/register)

## Setup

Clone the repo, and configure the web server of your choice to use `/public` as the webwoot. Here's how to do it locally
with PHP 5.4+:

    git clone https://github.com/Nexmo/Proxy.git
    php -S localhost:8080 ./Proxy/public/index.php

Then point your [Nexmo number to your server][3].

*If you're using a local develpoment server, make sure it can be reached by Nexmo so incoming messages can be routed
correctly. [Forward][1], or [Runscope's Passageway][2] make this easy.

Edit the configuration in `/bootstrap.php`, or define that configuration in your environment variables.

## Usage

SMS an email address to your Nexmo number to start a chat. If no one else is ready, you'll just wait for another user.
When you're done, SMS #end to close the chat.

Both users will be sending message to / receiving message from the Nexmo number, so their personal number will not be
revealed.

While users are chatting, you can view the connections and see a log of the messages in your web browser.


[1]: https://forwardhq.com/
[2]: https://www.runscope.com/docs/passageway
[3]: https://dashboard.nexmo.com/private/numbers