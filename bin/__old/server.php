<?php
// Add these to composer's requires if they aren't already there:
// "react/zmq": "0.2.*",

require_once('config.php');
require_once('includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');

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
  global $messenger;

  //echo "\nTick.\n";

  // Load all queue items that are ready to execute.
  $queue_items = Queue::load_ready();
  
  foreach ($queue_items as $qitem) {
    // Load the queue item.
    $item = $qitem->process();
    // Process the queue item.
    $result = $item->queue_process($qitem);

    // Figure out a way to send this notice to the player when their party has returned.
    if (is_array($result)) {
      //echo var_export($result, true)."\n";

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
          if ($method == 'instant_message' && isset($info['player'])) $channel = '@'.$info['player']->username;
          $messenger->send($msg, $channel);
          //echo "Msg to ".(empty($channel) ? '#channel' : $channel).": ".$msg."\n";
        }
      }
    }
  }
}


/* =====================================================
   _________    __  _________   __    ____  ____  ____ 
  / ____/   |  /  |/  / ____/  / /   / __ \/ __ \/ __ \
 / / __/ /| | / /|_/ / __/    / /   / / / / / / / /_/ /
/ /_/ / ___ |/ /  / / /___   / /___/ /_/ / /_/ / ____/ 
\____/_/  |_/_/  /_/_____/  /_____/\____/\____/_/      
                                                                                                                 
======================================================== */

// Message sender initiate.
$messenger = new SlackMessage (SLACK_WEBHOOK, SLACK_BOT_USERNAME, SLACK_BOT_ICON);

// Create game loop.
$loop = React\EventLoop\Factory::create();

// Add any timers necessary.
$loop->addPeriodicTimer(5, 'timer_process_queue');

// Run the loop.
$loop->run();