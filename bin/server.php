<?php
// Add these to composer's requires if they aren't already there:
// "devristo/phpws": "dev-master"
// "frlnc/php-slack": "*"

require_once('config.php');
require_once('includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/RPGSession.php');

// Load in any extra resources we need.
require_once(RPG_SERVER_ROOT.'/bin/server/timer_refresh_tavern.php');
require_once(RPG_SERVER_ROOT.'/bin/server/timer_refresh_quests.php');
require_once(RPG_SERVER_ROOT.'/bin/server/timer_leaderboard_standings.php');

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

$tavern_refresh_time = '23:59:59'; // '4:00:00';
$next_tavern_refresh = strtotime(date('Y-m-d').' '.$tavern_refresh_time);

$quest_refresh_time = '23:59:59'; // '2:00:00';
$next_quest_refresh = strtotime(date('Y-m-d').' '.$quest_refresh_time);

$leaderboard_standings_time = '23:59:59'; // '8:30:00';
$next_leaderboard_standings = strtotime(date('Y-m-d').' '.$leaderboard_standings_time);

/**
 * Remove available Adventurers from tavern and create new ones.
 */
function timer_refresh_tavern () {
  global $logger, $next_tavern_refresh;

  //$logger->notice("Next Tavern Refresh: ".$next_tavern_refresh." -- Time: ".time());

  // If we need to refresh the tavern, do so now.
  if (time() >= $next_tavern_refresh) {
    // Set the next tavern refresh time.
    $next_tavern_refresh = strtotime('+1 day', $next_tavern_refresh);
    
    // Clean out the tavern of available adventurers.
    clean_out_tavern();

    // Generate new adventurers from the tavern.
    generate_new_adventurers();

    $logger->notice("Tavern refreshed! Next Tavern Refresh: ".$next_tavern_refresh);
  }
}

/**
 * Remove available Quests and create new ones.
 */
function timer_refresh_quests () {
  global $logger, $next_quest_refresh;

  //$logger->notice("Next Quest Refresh: ".$next_quest_refresh." -- Time: ".time());

  // If we need to refresh the quests, do so now.
  if (time() >= $next_quest_refresh) {
    // Set the next quest refresh time.
    $next_quest_refresh = strtotime('+1 day', $next_quest_refresh);
    
    // Remove the available quests.
    // remove_available_quests();

    // Generate new quests.
    generate_new_quests();

    $logger->notice("Quests refreshed! Next Quest Refresh: ".$next_quest_refresh);
  }
}

/**
 * Remove available Quests and create new ones.
 */
function timer_leaderboard_standings () {
  global $logger, $next_leaderboard_standings;

  //$logger->notice("Next Leaderboard Standings: ".$next_leaderboard_standings." -- Time: ".time());

  // If we need to refresh the quests, do so now.
  if (time() >= $next_leaderboard_standings) {
    // Set the next quest refresh time.
    $next_leaderboard_standings = strtotime('+1 day', $next_leaderboard_standings);
    
    // Show leaderboard standings.
    $message = show_leaderboard_standings();
    send_message($message);

    $logger->notice("Leaderboard shown! Next Leaderboard Standings: ".$next_leaderboard_standings);
  }
}

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

    // If there was a problem loading this queue item,
    // it's possible the related object was deleted. Just remove the queue.
    if (empty($item)) {
      $qitem->delete();
      continue;
    }

    // Process the queue item.
    $result = $item->queue_process($qitem);

    // Send out any SlackMessages returned in the result.
    if (is_array($result)) {
      //$logger->notice($result);

      // If there are messages to send, do it.
      if (isset($result['messages'])) {
        //$logger->notice($result['messages']);

        foreach ($result['messages'] as $message) {
          // Send off the message.
          send_message($message);
        }
      }
    }
  }
}

function send_message ($message) {
  global $commander, $logger;

  // Check if we need to alter the channel for personal messages.
  if ($message->is_instant_message()) {
    $message->channel = get_user_channel($message->player->slack_user_id);
  }

  $message->as_user = 'true';
  $message->username = SLACK_BOT_USERNAME;
  if (empty($message->channel)) $message->channel = SLACK_BOT_PUBLIC_CHANNEL;

  // Get message as associative array.
  $payload = $message->encode();

  // Manually encode attachments.
  // if (isset($payload['attachments']) && is_array($payload['attachments'])) {
  //   $payload['attachments'] = json_encode($payload['attachments']);
  // }

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

function gather_im_channels (&$commander) {
  $response = $commander->execute('im.list', array());
  $body = $response->getBody();

  $list = array();
  if (isset($body['ok']) && $body['ok']) {
    foreach ($body['ims'] as $im) {
      if (!$im['is_im'] || $im['is_user_deleted']) continue;
      $list[$im['user']] = $im['id'];
    }
  }

  return $list;
}

function gather_user_list (&$commander) {
  $response = $commander->execute('users.list', array());
  $body = $response->getBody();

  $list = array();
  if (isset($body['ok']) && $body['ok']) {
    foreach ($body['members'] as $member) {
      if ($member['deleted']) continue;
      $list[$member['id']] = $member;
    }
  }

  return $list;
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
else {
  echo "Failed to initiate the rtm.start call:\n";
  echo var_export($body, true)."\n";
  exit;
}

// Create list of IMs.
$im_channels = gather_im_channels($commander);

// Create list of Users.
$user_list = gather_user_list($commander);

//echo var_export($im_channels, true)."\n";
//echo var_export($response->toArray(), true)."\n";


// Create websocket connection.
$loop = \React\EventLoop\Factory::create();




// Add any timers necessary.
$loop->addPeriodicTimer(2, 'timer_process_queue');
$loop->addPeriodicTimer(31, 'timer_refresh_tavern');
$loop->addPeriodicTimer(32, 'timer_refresh_quests');
$loop->addPeriodicTimer(33, 'timer_leaderboard_standings');




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

$client->on("connect", function() use ($logger, $client) {
  $logger->notice("Connected.");
});

$client->on("message", function($message) use ($client, $logger) {
  // Only keep track of messages and reactions.
  $data = json_decode($message->getData(), true);

  // If a new IM channel is opened, refresh the list.
  if (isset($data['type']) && $data['type'] == 'im_created') {
    global $im_channels, $commander;
    $im_channels = gather_im_channels($commander);
    return;
  }

  // If a new team member joins, refresh the list.
  else if (isset($data['type']) && $data['type'] == 'team_join') {
    global $im_channels, $commander;
    $user_list = gather_user_list($commander);
    return;
  }
  
  // Reaction (aka confirmation) from the user.
  else if (isset($data['type']) && $data['type'] == 'reaction_added' && isset($data['reaction']) && $data['reaction'] == 'confirm') {
    // Skip if we don't have the appropriate data.
    if (!isset($data['user'])) return;
    if (!isset($data['item'])) return;

    // Get the message and make sure it's from RPG bot in a personal message.
    $item = $data['item'];
    if (!isset($item['ts'])) return;
    if (!isset($item['type']) || $item['type'] != 'message') return;
    if (!isset($item['channel'])) return;

    global $im_channels, $user_list, $commander;
    $user_id = $data['user'];
    $channel = $item['channel'];

    // Get the list of current reactions to find the message (very tediuos step).
    $response = $commander->execute('reactions.list', array('user' => $user_id, 'count' => 5, 'page' => 1));
    $body = $response->getBody();
    $reaction = null;
    if (isset($body['ok']) && $body['ok']) {
      foreach ($body['items'] as $areaction) {
        if ($areaction['channel'] != $channel) continue;
        if ($areaction['message']['type'] != 'message') continue;
        if ($areaction['message']['ts'] != $item['ts']) continue;
        $reaction = $areaction;
        break;
      }
    }
    if (empty($reaction)) return;

    // Check that the user data exists.
    if (!isset($user_list[$user_id])) return;
    $user = $user_list[$user_id];

    // $logger->notice("Got reaction from user: ".$message->getData());

    // Get the message text.
    $orig_text = $reaction['message']['text'];

    // Look for confirmation snippet.
    preg_match("/Type `\+:confirm:` to confirm\.\\n\(You typed: `(.+)`\)/", $orig_text, $matches);
    if (count($matches) < 2) return;
    $text = $matches[1].' CONFIRM';

    // $logger->notice("Reaction command: ".$text);
  }

  // Message from the user.
  else if (isset($data['type']) && $data['type'] == 'message' && !isset($data['subtype'])) {
    // Skip if we don't have the appropriate data.
    if (!isset($data['user'])) return;
    if (!isset($data['channel'])) return;

    global $im_channels, $user_list;
    $user_id = $data['user'];
    $channel = $data['channel'];

    // Get the personal message channel.
    if (!isset($im_channels[$user_id])) return;
    $im_channel = $im_channels[$user_id];

    // Check that it is a personal message channel.
    if ($channel != $im_channel) return;

    // Check that the user data exists.
    if (!isset($user_list[$user_id])) return;
    $user = $user_list[$user_id];

    // $logger->notice("Got personal message from user: ".$message->getData());

    // Get the message text.
    $text = $data['text'];
  }

  // If we have some text, process it.
  if (isset($text) && !empty($text)) {
    // Bust it up and send it as a command to RPGSession.
    $session_data = array(
      'user_id' => $user_id,
      'user_name' => $user['name'],
    );
    $session = new RPGSession ();
    $response = $session->handle($text, $session_data);
    //$logger->notice($response);

    // Send the messages to the users.
    if (isset($response['personal']) && !empty($response['personal'])) {
      foreach ($response['personal'] as $personal_message) {
        send_message($personal_message);
      }
    }

    // If there's a global message, send that.
    if (isset($response['channel']) && !empty($response['channel'])) {
      send_message($response['channel']);
    }
  }
});

$client->open();
$loop->run();