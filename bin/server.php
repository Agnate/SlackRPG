<?php
// Add these to composer's requires if they aren't already there:
// "devristo/phpws": "dev-master"
// "frlnc/php-slack": "*"

require_once('config.php');
require_once('includes/db.inc');
require(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');

// Create API call to start websocket connection.
use Frlnc\Slack\Http\SlackResponseFactory;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Core\Commander;

/* ==================================
  ____________  _____________  _____
 /_  __/  _/  |/  / ____/ __ \/ ___/
  / /  / // /|_/ / __/ / /_/ /\__ \ 
 / / _/ // /  / / /___/ _, _/___/ / 
/_/ /___/_/  /_/_____/_/ |_|/____/  
                                    
===================================== */

/**
 * Check for any queue items that need to be executed and completed.
 */
function timer_process_queue () {
  global $logger;
  //$logger->notice("Tick.");

  // Testing message.
  //send_message('Test', get_user_channel('U0265JBJW'));
  
  // Load all queue items that are ready to execute.
  $queue_items = Queue::load_ready();
  
  foreach ($queue_items as $qitem) {
    // Load the queue item.
    $item = $qitem->process();
    // Process the queue item.
    $result = $item->queue_process($qitem);

    // Expected result:
    // $result = array(
    //   'messages' => array(
    //     'instant_message' => array(
    //       'text' => $player_text,
    //       'player' => $guild,
    //     ),
    //     'channel' => array(
    //       'text' => $channel_text
    //     ),
    //   ),
    // );

    // Figure out a way to send this notice to the player when their party has returned.
    if (is_array($result)) {
      //$logger->notice($result);

      // If there are messages to send, do it.
      if (isset($result['messages'])) {
        foreach ($result['messages'] as $method => $info) {
          // Get the message.
          $msg = is_string($info) ? trim($info) : '';
          if (is_array($info) && isset($info['text'])) $msg = trim($info['text']);
          // Skip if the message is blank.
          if (empty($msg)) continue;
          // Determine the channel to send it.
          $channel = null;
          if ($method == 'instant_message' && isset($info['player'])) $channel = get_user_channel($info['player']->slack_user_id);
          if (!empty($channel) || $method == 'channel') send_message($msg, $channel);
          //$logger->notice("Msg to ".(empty($channel) ? '#channel' : $channel).": ".$msg);
        }
      }
    }
  }
}

function send_message ($text, $channel = null) {
  global $commander, $logger;

  $payload = compact('text');
  $payload['as_user'] = 'true';
  $payload['username'] = SLACK_BOT_USERNAME;
  $payload['channel'] = SLACK_BOT_PUBLIC_CHANNEL;
  if (!empty($channel)) $payload['channel'] = $channel;

  $response = $commander->execute('chat.postMessage', $payload);
  $body = $response->getBody();

  if ($body['ok']) return true;

  $logger->notice($body);

  return false;
}

function get_user_channel ($slack_user_id) {
  global $im_channels;
  return isset($im_channels[$slack_user_id]) ? $im_channels[$slack_user_id] : FALSE;
}


/* =====================================================
   _________    __  _________   __    ____  ____  ____ 
  / ____/   |  /  |/  / ____/  / /   / __ \/ __ \/ __ \
 / / __/ /| | / /|_/ / __/    / /   / / / / / / / /_/ /
/ /_/ / ___ |/ /  / / /___   / /___/ /_/ / /_/ / ____/ 
\____/_/  |_/_/  /_/_____/  /_____/\____/\____/_/      
                                                                                                                 
======================================================== */

$interactor = new CurlInteractor;
$interactor->setResponseFactory(new SlackResponseFactory);

$commander = new Commander(SLACK_BOT_TOKEN, $interactor);

/*$response = $commander->execute('chat.postMessage', array(
  'channel' => SLACK_BOT_PUBLIC_CHANNEL,
  'username' => SLACK_BOT_USERNAME,
  'as_user' => true,
  'text' => 'Hello, world!',
));*/

// Start the RPM session.
$response = $commander->execute('rtm.start', array());
$body = $response->getBody();
// Check for an okay response and get url.
if (isset($body['ok']) && $body['ok']) $url = $body['url'];


// Create list of IMs.
$response = $commander->execute('im.list', array());
$body = $response->getBody();
$im_channels = array();
if (isset($body['ok']) && $body['ok']) {
  foreach ($body['ims'] as $im) {
    if (!$im['is_im'] || $im['is_user_deleted']) continue;
    $im_channels[$im['user']] = $im['id'];
  }
}

//echo var_export($response->toArray(), true)."\n";


// Create websocket connection.
$loop = \React\EventLoop\Factory::create();

// Add any timers necessary.
$loop->addPeriodicTimer(5, 'timer_process_queue');

$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

$client = new \Devristo\Phpws\Client\WebSocket ($url, $loop, $logger);

$client->on("request", function($headers) use ($logger) {
  $logger->notice("Request object created.");
});

$client->on("handshake", function() use ($logger) {
  $logger->notice("Handshake received.");
});

$client->on("connect", function($headers) use ($logger, $client) {
  $logger->notice("Connected.");
});

$client->on("message", function($message) use ($client, $logger) {
  // Only keep track of messages.
  $data = json_decode($message->getData(), true);
  if (isset($data['type']) && $data['type'] == 'message') {
    $logger->notice("Got message: ".$message->getData());
  }
});

$client->open();
$loop->run();