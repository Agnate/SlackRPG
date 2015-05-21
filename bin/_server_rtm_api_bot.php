<?php
// Add these to composer's requires if they aren't already there:
// "devristo/phpws": "dev-master"
// "frlnc/php-slack": "*"

require_once('config.php');
require_once('includes/db.inc');
require(RPG_SERVER_ROOT.'/vendor/autoload.php');
//require_once(RPG_SERVER_ROOT.'/src/autoload.php');

// Create API call to start websocket connection.
use Frlnc\Slack\Http\SlackResponseFactory;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Core\Commander;

$interactor = new CurlInteractor;
$interactor->setResponseFactory(new SlackResponseFactory);

$commander = new Commander(SLACK_BOT_TOKEN, $interactor);

/*$response = $commander->execute('chat.postMessage', array(
  'channel' => SLACK_BOT_PUBLIC_CHANNEL,
  'username' => SLACK_BOT_USERNAME,
  'as_user' => true,
  'text' => 'Hello, world!',
));*/

$response = $commander->execute('rtm.start', array());
$body = $response->getBody();

// Check for an okay response and get url.
if (isset($body['ok']) && $body['ok']) {
  $url = $body['url'];
}

//echo var_export($response->toArray(), true)."\n";


// Create websocket connection.
$loop = \React\EventLoop\Factory::create();

$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

$client = new \Devristo\Phpws\Client\WebSocket($url, $loop, $logger);

$client->on("request", function($headers) use ($logger){
  $logger->notice("Request object created!");
});

$client->on("handshake", function() use ($logger) {
  $logger->notice("Handshake received!");
});

$client->on("connect", function($headers) use ($logger, $client){
  $logger->notice("Connected!");
});

$client->on("message", function($message) use ($client, $logger){
  // Only keep track of messages.
  $data = json_decode($message->getData(), true);
  if (isset($data['type']) && $data['type'] == 'message') {
    $logger->notice("Got message: ".$message->getData());
  }
});

$client->open();
$loop->run();