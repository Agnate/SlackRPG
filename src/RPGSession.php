<?php
use \Kint;

require_once(RPG_SERVER_ROOT.'/src/autoload.php');

class RPGSession {

  public $data;
  public $response = array();
  public $commands = array('bad_command' => '_bad_command');
  public $curplayer;

  const CHANNEL = 'channel';
  const PERSONAL = 'personal';
  const IM = 'instant_message';
  
  function __construct() {
    $this->response['username'] = 'RPG';
    $this->response['icon_emoji'] = ':rpg:';

    // NOTE: For command keys (the actual text the user will type), short-forms should come AFTER the long forms.
    //     Example:
    //     $this->register_callback(array('updates', 'update'), 'cmd_updates');
    //     'updates' needs to come before 'update' or it won't properly recognize the 'update' command.

    $this->register_callback(array('help'), 'cmd_help');
    $this->register_callback(array('updates', 'update'), 'cmd_updates');

    $this->register_callback(array('register'), 'cmd_register');
    $this->register_callback(array('recruit'), 'cmd_recruit');
    $this->register_callback(array('quest'), 'cmd_quest');

    $this->register_callback(array('test'), 'cmd_test');    

    $this->register_callback(array('status'), 'cmd_status');
  }



  /**
   * Display the available commands.
   */
  protected function cmd_help( $args = array() ) {
    $response = array();
    $response[] = '*RPG Commands:*';
    $response[] = 'Register Guild: `/rpg register [GUILD EMOJI] [GUILD NAME]` (example: `/rpg register :skull: Death\'s Rattle`)';
    $response[] = 'Guild status: `/rpg status`';
    $response[] = 'Recruit Adventurers: `/rpg recruit`';
    $response[] = '';
    
    $response[] = '*Other*';
    $response[] = 'See recent updates: `/rpg updates [VERSION]` (Exclude the version to show the newest updates)';

    $this->respond( implode("\n", $response), RPGSession::PERSONAL );
  }



  /**
   * Show the most recent update (or other updates if you know the special password).
   */
  protected function cmd_updates ($args = array()) {
    // Set the newest version here.
    $version = '0.1.0';
    
    if ($args[0] != '') $version = $args[0];
    $update = true;
    $response = array();

    switch ($version) {
      case '0.1.0':
        $response[] = '*Nothing happened*';
        $response[] = '- First thing.';
        $response[] = '- Second thing.';
        break;

      default:
        $update = false;
    }

    if ($update) {
      array_unshift($response, '*Update '.$version.'*');
      $this->respond( implode("\n", $response), (isset($args[1]) && $args[1] == 'monkey123' ? RPGSession::CHANNEL : RPGSession::PERSONAL) );
    }
  }



  /**
   * Register your Guild to play in the current season.
   */
  protected function cmd_register ($args = array()) {
    $player = $this->get_current_player();
    // If the player exists already, no need to register.
    if (!empty($player)) {
      $this->respond('You have already registered this season with *'.$player->name.'*.');
      return FALSE;
    }

    // Check if they gave a Guild name.
    if (empty($args) || count($args) < 2 || empty($args[0]) || empty($args[1])) {
      $this->respond('Please select a Guild name. Type: `/rpg register [GUILD EMOJI] [GUILD NAME]` (example: `/rpg register :skull: Death\'s Rattle`).');
      return FALSE;
    }

    // Extract the icon and confirm the format.
    $icon = array_shift($args);
    if (strpos($icon, ':') !== 0 || strrpos($icon, ':') !== strlen($icon)-1) {
      $this->respond('You must enter an emoji icon as the first argument (example: `/rpg register :skull: Death\'s Rattle`).');
      return FALSE;
    }

    $name = implode(' ', $args);
    if (empty($name)) {
      $this->respond('You must choose a name for your Guild.');
      return FALSE;
    }

    // Create the new player.
    $data = array(
      'slack_user_id' => $this->data['user_id'],
      'username' => $this->data['user_name'],
      'name' => $name,
      'icon' => $icon,
      'gold' => 1000, // Starting gold.
    );
    $player = new Guild ($data);

    // Try to save the new player.
    $success = $player->save();
    if ( $success === false ) {
      $this->respond(':skull::skull::skull: Contact Paul immediately. Oh god it\'s all gone horribly wrong. Server errors! :skull::skull::skull:');
      return FALSE;
    }

    $this->respond('@'.$player->username.' just created a Guild called '.$player->get_display_name().'!', RPGSession::CHANNEL);
  }



  /**
   * Show your Guild status.
   */
  protected function cmd_status ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    $adventurers = $player->get_adventurers();
    
    $response = array();
    $response[] = '*Guild name*: '.$player->get_display_name(false);
    $response[] = '*Gold*: '.$this->get_currency($player->gold);
    $response[] = '*Adventurers*: ('.$player->get_adventurers_count().' / '.$player->adventurer_limit.')';

    foreach ($adventurers as $adventurer) {
      $adv_status = !empty($adventurer->agid) ? ' [Questing]' : '';
      $response[] = $adventurer->get_display_name(false) .$adv_status;
    }

    $this->respond(implode("\n", $response));
  }



  /**
   * Recruit new Adventurers.
   */
  protected function cmd_recruit ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    
    // If no Adventurer is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      // Load up any adventurers in the tavern.
      $adventurers = Adventurer::load_multiple(array('available' => true, 'gid' => 0));

      if (empty($adventurers)) {
        $this->respond('There are no adventurers in the Tavern.');
        return FALSE;
      }

      $response = array();
      $response[] = 'Adventurers in the Tavern:';
      foreach ($adventurers as $adventurer) {
        $adventurer_cost = $adventurer->level * 250;
        $response[] = $adventurer->get_display_name().' for '.$this->get_currency($adventurer_cost).'  `/rpg recruit '.$adventurer->name.'`';
      }

      $this->respond(implode("\n", $response));
      return FALSE;
    }

    // They chose a name, so let's check if that adventurer is available.
    $adventurer = Adventurer::load(array('name' => implode(' ', $args), 'available' => true, 'gid' => 0), true);

    if (empty($adventurer)) {
      $this->respond('There is no adventurer by the name of "'.implode(' ', $args).'" in the Tavern.');
      return FALSE;
    }

    // Check if they have enough money to hire that adventurer.
    $adventurer_cost = $adventurer->level * 250;
    if ($player->gold < $adventurer_cost) {
      $this->respond('You do not have enough gold to recruit '.$adventurer->get_display_name().'.');
      return FALSE;
    }

    // Remove the gold.
    $player->gold -= $adventurer_cost;
    $player->save();

    // Recruit the adventurer.
    $adventurer->gid = $player->gid;
    $adventurer->available = false;
    $success = $adventurer->save();

    if (!$success) {
      $this->respond('Talk to Paul, as there was an error saving this recruitment (and it probably didn\'t work).');
      return FALSE;
    }

    $this->respond($player->get_display_name().' has recruited a new adventurer, '.$adventurer->get_display_name().'.', RPGSession::CHANNEL, false);
    $this->respond('You recruited '.$adventurer->get_display_name().' for '.$this->get_currency($adventurer_cost).'.');
  }



  /**
   * See and go on quests.
   */
  protected function cmd_quest ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    // If no Quest is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      // Load up any active Quests.
      $quests = Quest::load_multiple(array('active' => true));

      if (empty($quests)) {
        $this->respond('There are no quests to undertake.');
        return FALSE;
      }

      $response = array();
      $response[] = 'Quests available:';
      foreach ($quests as $quest) {
        $response[] = $quest->name.' (adventurers required: '.$quest->party_size.')  `/rpg quest q'.$quest->qid.' [ADVENTURER NAMES (comma-separated)]`';
      }

      // Also show the list of available adventurers.
      $response[] = '';
      $response[] = 'Adventurers available for quests:';
      foreach ($player->get_adventurers() as $adventurer) {
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name();
      }

      $this->respond(implode("\n", $response));
      return FALSE;
    }

    // Check if Quest ID is available.
    $qid = substr(array_shift($args), 1);
    $quest = Quest::load(array('qid' => $qid));
    if (empty($quest)) {
      $this->respond("This quest is not available or does not exist.\n(You typed: `/rpg quest q".$qid." ".implode(' ', $args)."`)");
      return FALSE;
    }

    // Get the adventurers going.
    if (empty($args)) {
      $this->respond('Please choose Adventurers to go on this quest. Type: `/rpg quest q'.$quest->qid.' [ADVENTURER NAMES (comma-separated)]`');
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Check if the adventurers are valid.
    $adventurer_args = implode(' ', $args);
    $list = explode(',', $adventurer_args);
    $adventurers = array();
    foreach ($list as $name) {
      // Check if the Adventurer is available for a Quest.
      $adventurer = Adventurer::load(array(
        'name' => trim($name),
        'gid' => $player->gid,
        'agid' => 0,
      ), true);

      if (empty($adventurer)) {
        $this->respond('The adventurer named "'.trim($name).'" is not available or valid. Please double-check that the name is correct.'."\n(You typed: `/rpg quest q".$qid." ".$adventurer_args.(!empty($confirmation) ? ' '.$confirmation : '')."`)");
        return FALSE;
      }

      $adventurers[] = $adventurer;
    }

    $response = array();

    // Check the party size requirement.
    $num_adventurers = count($adventurers);
    if ($num_adventurers != $quest->party_size) {
      $adventurer_diff = $quest->party_size - $num_adventurers;
      $adventurer_response = ($adventurer_diff > 0) ? abs($adventurer_diff).' too few' : abs($adventurer_diff).' too many';

      if ($adventurer_diff > 0) {
        $response[] = 'You need '.abs($adventurer_diff).' more adventurer'.($quest->party_size > 1 ? 's' : '').' (total of '.$quest->party_size.') to embark on this quest.';
      }
      else {
        $response[] = 'There are '.abs($adventurer_diff).' too many adventurers for this quest. Please reduce the group to '.$quest->party_size.' adventurer'.($quest->party_size > 1 ? 's' : '').'.'; 
      }

      $response[] = "(You typed: `/rpg quest q".$qid." ".$adventurer_args.(!empty($confirmation) ? ' '.$confirmation : '')."`)";

      $this->respond(implode("\n", $response));
      return FALSE;
    }
    
    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM_Q'.$quest->qid) {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM_Q'.$quest->qid.'`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = '*Quest*: '.$quest->name;
      $response[] = '*Adventuring party*: ('.count($adventurers).')';
      foreach ($adventurers as $adventurer) {
        $response[] = $adventurer->get_display_name();
      }
      $response[] = '';
      $response[] = 'To confirm your departure, type:';
      $response[] = '`/rpg q'.$quest->qid.' '.$adventurer_args.' CONFIRM_Q'.$quest->qid.'`';
      $this->respond(implode("\n", $response));
      return FALSE;
    }

    // Put together the adventuring party.
    $duration = $quest->duration;
    $data = array(
      'gid' => $player->gid,
      'created' => time(),
      'task_id' => $quest->qid,
      'task_type' => 'Quest',
      'task_eta' => $duration,
      'completed' => false,
    );
    $advgroup = new AdventuringGroup ($data);
    $success = $advgroup->save();
    if ($success === false) {
      $this->respond('There was a problem saving the adventuring group. Please talk to Paul.');
      return FALSE;
    }

    // Assign all the adventurers to the new group.
    $names = array();
    foreach ($adventurers as $adventurer) {
      $names[] = $adventurer->get_display_name();
      $adventurer->agid = $advgroup->agid;
      $success = $adventurer->save();
      if ($success === false) {
        $this->respond('There was a problem saving an adventurer to the adventuring group. Please talk to Paul.');
        return FALSE;
      }
    }

    // Assign adventuring group to the quest.
    $quest->gid = $player->gid;
    $quest->agid = $advgroup->agid;
    $quest->active = false;
    $success = $quest->save();
    if ($success === false) {
      $this->respond('There was a problem saving the quest. Please talk to Paul.');
      return FALSE;
    }

    // Queue the quest for completion.
    $queue = $quest->queue( $duration );
    if (empty($queue)) {
      $this->respond('There was a problem adding the quest to the queue. Please talk to Paul.');
      return FALSE;
    }

    // Get list of adventurer names.
    $name_count = count($names);
    $last_name = ($name_count > 1) ? ', and '.array_pop($names) : '';
    $names = implode(', ', $names).$last_name;

    $this->respond($names.' embark'.($name_count == 1 ? 's' : '').' on the quest to '.$quest->name.' returning in '.$this->get_duration_as_hours($duration).'.');
  }



  protected function cmd_test ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    $this->respond('This is a test.', RPGSession::IM);
  }



  /* =================================================================================================
     _____ __  ______  ____  ____  ____  ______   ________  ___   ______________________  _   _______
    / ___// / / / __ \/ __ \/ __ \/ __ \/_  __/  / ____/ / / / | / / ____/_  __/  _/ __ \/ | / / ___/
    \__ \/ / / / /_/ / /_/ / / / / /_/ / / /    / /_  / / / /  |/ / /     / /  / // / / /  |/ /\__ \ 
   ___/ / /_/ / ____/ ____/ /_/ / _, _/ / /    / __/ / /_/ / /|  / /___  / / _/ // /_/ / /|  /___/ / 
  /____/\____/_/   /_/    \____/_/ |_| /_/    /_/    \____/_/ |_/\____/ /_/ /___/\____/_/ |_//____/  
                                                                                                     
  ==================================================================================================== */

  protected function load_current_player ($allow_error = true) {
    $player = $this->get_current_player();

    if (empty($player)) {
      $this->respond('You must register your Guild before you can begin playing. Type: `/rpg register [GUILD EMOJI] [GUILD NAME]`');
      return FALSE;
    }

    return $player;
  }

  protected function get_current_player () {
    // If we've already loaded this one, we're done.
    if (!empty($this->curplayer)) {
      return $this->curplayer;
    }

    // First time loading the player, so we need the data.
    $data = array(
      'slack_user_id' => $this->data['user_id'],
      'username' => $this->data['user_name'],
    );

    $this->curplayer = Guild::load($data);

    return $this->curplayer;
  }

  protected function get_currency ($amount) {
    return $amount.' gp';
  }

  protected function get_duration_as_hours ($duration) {
    $seconds = $duration;
    $hours = floor($seconds / (60 * 60));
    $seconds -= ($hours * 60 * 60);
    $minutes = floor($seconds / 60);
    $seconds -= ($minutes * 60);
    
    return ($hours > 0 ? $hours.' hours, ' : '').($minutes > 0 ? $minutes.' minutes, ' : '').($seconds.' seconds');
  }



  /* =============================================================================
      _   ______  _   __      __________  __  _____  ______    _   ______  _____
     / | / / __ \/ | / /     / ____/ __ \/  |/  /  |/  /   |  / | / / __ \/ ___/
    /  |/ / / / /  |/ /_____/ /   / / / / /|_/ / /|_/ / /| | /  |/ / / / /\__ \ 
   / /|  / /_/ / /|  /_____/ /___/ /_/ / /  / / /  / / ___ |/ /|  / /_/ /___/ / 
  /_/ |_/\____/_/ |_/      \____/\____/_/  /_/_/  /_/_/  |_/_/ |_/_____//____/  
                                                                                
  ================================================================================ */

  protected function _bad_command($args = array()){
    $this->respond('"'.implode($args, ' ').'" is not a valid command.');
  }

  public function register_callback($keys, $callback){
    if(!is_array($keys)) $keys = array($keys);
    $args = func_get_args();
    array_splice($args, 0, 2);
    foreach($keys as $key){
      $this->commands[$key] = array('callback' => $callback, 'args' => $args);
    }
  }

  public function handle ($input = '', $data = array()) {
    $this->data = $data;
    $callback = $this->commands['bad_command'];
    $args = array($input);
    
    foreach( $this->commands as $cmd_key => $cmd ) {
      $check = strpos($input, $cmd_key);
      if ( $check !== false && $check === 0 ) {
        $args = explode(' ', trim(str_replace($cmd_key, '', $input)));

        $cmd_args = array();
        if ( is_array($cmd) ) {
          $callback = $cmd['callback'];
          if ( isset($cmd['args'])  &&  is_array($cmd['args'])  &&  !empty($cmd['args']) ) {
            $cmd_args = $cmd['args'];
          }
        }
        else {
          $callback = $cmd;
        }
        break;
      }
    }

    if ( !empty($cmd_args) ) $this->{$callback}($cmd_args, $args);
    else $this->{$callback}($args);
    return $this->response;
  }

  protected function _convert_to_markup ( $string ) {
    $info = array(
      '/:([A-Za-z0-9_\-\+]+?):/' => '<img src="/debug/icons/\1.png" width="22px" height="22px">',
      '/\\n/' => '<br>',
      '/\*(.*?)\*/' => '<strong>\1</strong>',
      '/\b_((?:__|[\s\S])+?)_\b|^\*((?:\*\*|[\s\S])+?)\*(?!\*)/' => '<em>\1</em>',
      '/(`+)\s*([\s\S]*?[^`])\s*\1(?!`)/' => '<code>\2</code>',
    );

    return preg_replace(array_keys($info), array_values($info), $string);
  }

  public function respond ($text = null, $location = RPGSession::PERSONAL, $exit = true) {
    if (!is_null($text)) $this->response['text'] = $text;

    if (isset($_REQUEST['forced_debug_mode']) && $_REQUEST['forced_debug_mode'] == 'true') {
      echo '<head><link rel="stylesheet" type="text/css" href="debug/css/debug.css"></head>';
      echo '<u>CHANNEL: '. $location .'</u><br><br>';
      echo '<div class="channel-'.$location.'">'.$this->_convert_to_markup($this->response['text']).'</div><br><br>';
      if ($exit) exit;
      return;
    }

    if ($location == RPGSession::PERSONAL) {
      echo $this->response['text'];
    }
    else if ($location == RPGSession::CHANNEL || $location == RPGSession::IM) {
      // Message the user directly via private message.
      $channel = NULL;
      if ($location == RPGSession::IM) {
        $channel = '@'.$this->data['user_name'];
      }

      $msg = new SlackMessage (SLACK_WEBHOOK, SLACK_BOT_USERNAME, SLACK_BOT_ICON);
      $msg->send($this->response['text'], $channel);
    }

    if ($exit) exit;
  }

  protected function _digest_msg ( $msg, $tokens ) {
    return str_replace(array_keys($tokens), array_values($tokens), $msg);
  }
}





/* ==============================================================================
   __  ______________       ________  ___   ______________________  _   _______
  / / / /_  __/  _/ /      / ____/ / / / | / / ____/_  __/  _/ __ \/ | / / ___/
 / / / / / /  / // /      / /_  / / / /  |/ / /     / /  / // / / /  |/ /\__ \ 
/ /_/ / / / _/ // /___   / __/ / /_/ / /|  / /___  / / _/ // /_/ / /|  /___/ / 
\____/ /_/ /___/_____/  /_/    \____/_/ |_/\____/ /_/ /___/\____/_/ |_//____/  
                                                                               
================================================================================= */

function debug ( $data ) {
  if ( !isset($_REQUEST['forced_debug_mode']) || $_REQUEST['forced_debug_mode'] != 'true' ) return;
  if ( !Kint::enabled() ) return;

  d($data);
}