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
    $this->register_callback(array('dismiss'), 'cmd_dismiss');
    $this->register_callback(array('champion'), 'cmd_champion');
    $this->register_callback(array('powerup', 'power'), 'cmd_powerup');
    $this->register_callback(array('edit'), 'cmd_edit');
    $this->register_callback(array('upgrades', 'upgrade'), 'cmd_upgrade');
    $this->register_callback(array('items', 'item', 'inventory', 'inv'), 'cmd_inventory');

    $this->register_callback(array('quest'), 'cmd_quest');
    $this->register_callback(array('map'), 'cmd_map');
    $this->register_callback(array('explore'), 'cmd_explore');

    $this->register_callback(array('status'), 'cmd_status');
    $this->register_callback(array('leaderboard', 'leader'), 'cmd_leaderboard');


    $this->register_callback(array('test'), 'cmd_test');
  }



  /**
   * Display the available commands.
   */
  protected function cmd_help( $args = array() ) {
    $response = array();
    $response[] = '*Commands:*';
    $response[] = 'Register Guild: `/rpg register [GUILD EMOJI] [GUILD NAME]` (example: `/rpg register :fake-icon: Death\'s Rattle`)';
    $response[] = 'Guild status: `/rpg status [GUILD NAME]`';
    $response[] = 'Leaderboard rankings: `/rpg leaderboard [all]`';
    $response[] = 'Recruit Adventurers: `/rpg recruit`';
    $response[] = 'Quests: `/rpg quest`';
    $response[] = 'View and explore the Map: `/rpg explore`';
    $response[] = 'Set your guild\'s Champion: `/rpg champion [NAME]`';
    $response[] = 'Edit your guild\'s information: `/rpg edit`';
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

    // Add new adventurers to the Guild.
    $num_adventurers = 2;
    $adventurers = array();
    for ($i = 0; $i < $num_adventurers; $i++) {
      // Generate the adventurer.
      $adventurer = Adventurer::generate_new_adventurer(false, false);
      $adventurer->gid = $player->gid;
      $adventurer->available = false;
      //if ($i == 0) $adventurer->champion = true;
      $adventurer->save();
      $adventurers[] = $adventurer;
    }

    $this->respond('@'.$player->username.' just registered a Guild called '.$player->get_display_name().'!', RPGSession::CHANNEL, false);

    $response = array();
    $response[] = 'You just registered '.$player->get_display_name().' and '.$num_adventurers.' new adventurer'.($num_adventurers > 1 ? 's' : '').' have joined you!';
    $response[] = 'Welcome,';
    foreach ($adventurers as $adventurer) $response[] = $adventurer->get_display_name();
    $this->respond($response);
  }



  /**
   * Show your Guild status.
   */
  protected function cmd_status ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    $guild = $player;
    $guild_is_player = true;

    // Check if they want to find the status of another guild.
    if (!empty($args) && !empty($args[0])) {
      $guild_name = implode(' ', $args);
      $guild = Guild::load(array('name' => $guild_name), true);
      if (empty($guild)) {
        $this->respond('There is no guild by the name of "'.$guild_name.'".');
        return FALSE;
      }
      $guild_is_player = false;
    }

    $adventurers = $guild->get_adventurers();
    
    $response = array();
    $response[] = '*Guild name*: '.$guild->get_display_name(false);
    $response[] = '*Fame*: '.$this->get_fame($guild->fame);
    $response[] = '*Founder:* @'.$guild->username;
    $response[] = '*Founded on:* '.date('F j, Y \a\t g:i A', $guild->created);
    if ($guild_is_player) $response[] = '*Gold*: '.$this->get_currency($guild->gold);

    // Show adventurers.
    $response[] = '*Adventurers*:'.($guild_is_player ? ' ('.$guild->get_adventurers_count().' / '.$guild->adventurer_limit.')' : '');
    foreach ($adventurers as $adventurer) {
      $adv_status = !empty($adventurer->agid) ? ' [Adventuring]' : '';
      $response[] = $adventurer->get_display_name(false) .($guild_is_player ? $adv_status : '');
    }

    // Show upgrades.
    $upgrades = $guild->get_upgrades();
    if ($guild_is_player && count($upgrades) > 0) {
      $response[] = '';
      $response[] = '*Upgrades*:';
      foreach ($upgrades as $upgrade) {
        $response[] = $upgrade->get_display_name();
      }
      $response[] = '';
    }

    $this->respond($response);
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

      $this->respond($response);
      return FALSE;
    }

    // Check if the player is at their max.
    if ($player->get_adventurers_count() >= $player->adventurer_limit) {
      $this->respond('You cannot recruit more adventurers because you are at your maximum capacity ('.$player->adventurer_limit.').');
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
    $success = $player->save();
    if ($success === false) {
      $this->respond('Talk to Paul, as there was an error saving the purchase transaction.');
      return FALSE;
    }

    // Recruit the adventurer.
    $adventurer->gid = $player->gid;
    $adventurer->available = false;
    $success = $adventurer->save();
    if ($success === false) {
      $this->respond('Talk to Paul, as there was an error saving this recruitment.');
      return FALSE;
    }

    $this->respond($player->get_display_name().' has recruited a new adventurer, '.$adventurer->get_display_name().'.', RPGSession::CHANNEL, false);
    $this->respond('You recruited '.$adventurer->get_display_name().' for '.$this->get_currency($adventurer_cost).'.');
  }



  /**
   * Dismiss a current Adventurer.
   */
  protected function cmd_dismiss ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'dismiss';

    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    
    // If no Adventurer is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      $this->respond("You must select an Adventurer to dismiss.");
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // They chose a name, so let's check if that adventurer is available.
    $adventurer = Adventurer::load(array('name' => implode(' ', $args), 'gid' => $player->gid), true);
    if (empty($adventurer)) {
      $this->respond('You do not have an Adventurer by the name of "'.implode(' ', $args).'".');
      return FALSE;
    }

    // If the adventurer is out adventuring, error out.
    if (!empty($adventurer->agid)) {
      $this->respond($adventurer->get_display_name().' is currently out on an adventure. You can only dismiss an Adventurer once they have returned.');
      return FALSE;
    }

    $response = array();

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = 'Are you sure you want to dismiss *'.$adventurer->name.'*? '.$adventurer->get_pronoun(true).' will return to the Tavern for other Guilds to recruit.';
      $response[] = '';
      $response[] = '*Name*: '.$adventurer->get_display_name(false, true, false, true, false);
      if (!empty($adventurer->class)) $response[] = '*Class*: '.$adventurer->class;
      $response[] = '*Level*: '.$adventurer->level;
      //$response[] = '*Popularity*: '.$adventurer->popularity;
      $response[] = '*Experience*: '.$adventurer->exp;
      $response[] = '';
      $response[] = 'To confirm the dismissal, type:';
      $response[] = '`/rpg '.$cmd_word.' '.implode(' ', $orig_args).' CONFIRM`';
      $this->respond($response);
      return FALSE;
    }

    // Dismiss the adventurer.
    $adventurer->gid = 0;
    $adventurer->available = true;
    $success = $adventurer->save();
    if ($success === false) {
      $this->respond('Talk to Paul, as there was an error dismissing your Adventurer.');
      return FALSE;
    }

    $this->respond($player->get_display_name().' has dismissed an adventurer, '.$adventurer->get_display_name().', who is now available in the Tavern.', RPGSession::CHANNEL, false);
    $this->respond('You have dismissed '.$adventurer->get_display_name().'.');
  }



  /**
   * See and go on quests.
   */
  protected function cmd_quest ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'quest';

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
      $response[] = $this->get_difficulty_legend();
      $response[] = '';
      $response[] = 'Quests available:';
      foreach ($quests as $quest) {
        // Get the best adventurers available for questing.
        $best_adventurers = $player->get_best_adventurers($quest->party_size_max);
        $success_rate = $quest->get_success_rate($player, $best_adventurers);
        $death_rate = $quest->death_rate;
        $response[] = $this->get_difficulty($success_rate) .' '.($death_rate > 0 ? ':skull: ' : ''). $this->get_stars($quest->stars).' '.$quest->name.' (adventurers required: '.$quest->get_party_size().')  `/rpg quest q'.$quest->qid.' [ADVENTURER NAMES (comma-separated)]`';
      }

      // Also show the list of available adventurers.
      $response[] = '';
      $response[] = 'Adventurers available for quests:';
      foreach ($player->get_adventurers() as $adventurer) {
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name();
      }

      $this->respond($response);
      return FALSE;
    }

    // Check if Quest ID is available.
    $qid = substr(array_shift($args), 1);
    $quest = Quest::load(array('qid' => $qid));
    if (empty($quest)) {
      $this->respond("This quest is not available or does not exist.".$this->get_typed($cmd_word, $orig_args));
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
    $success = $this->check_for_valid_adventurers($player, $list);
    if (!$success['success']) {
      $success['msg'][] = $this->get_typed($cmd_word, $orig_args);
      $this->respond(implode("\n", $success['msg']));
      return FALSE;
    }
    // Get the list of adventurers.
    $adventurers = $success['data']['adventurers'];

    $response = array();

    // Check the party size requirement.
    $num_adventurers = count($adventurers);
    $too_few = $num_adventurers < $quest->party_size_min;
    $too_many = $num_adventurers > $quest->party_size_max;
    if ($too_few || $too_many) {
      if ($too_few) {
        $response[] = 'You need at least '.$quest->party_size_min.' adventurer'.($quest->party_size_min > 1 ? 's' : '').' to embark on this quest.';
      }
      
      if ($too_many) {
        $adventurer_diff = $num_adventurers - $quest->party_size_max;
        $response[] = 'There are '.abs($adventurer_diff).' too many adventurers for this quest. Please reduce the group to '.$quest->party_size_max.' adventurer'.($quest->party_size_max > 1 ? 's' : '').'.';
      }

      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Check if the success rate is above 0%.
    $success_rate = $quest->get_success_rate($player, $adventurers);
    if ($success_rate <= 0) {
      $response[] = 'Your adventuring party has no chance of completing this quest. Please choose stronger adventurers.';
      $response[] = '';
      $response[] = 'Adventurers available for quests:';
      foreach ($player->get_adventurers() as $adventurer) {
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name(in_array($adventurer, $adventurers));
      }
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }
    
    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    $duration = $quest->get_duration($player, $adventurers);
    $death_rate = $quest->get_death_rate($player, $adventurers);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = '*Quest*: '.$this->get_stars($quest->stars).' '.$quest->name;
      $response[] = '*Duration*: '.$this->get_duration_as_hours($duration);
      $response[] = '*Chance of Success*: '.$this->get_difficulty($success_rate).' '.$success_rate.'%';
      if ($death_rate > 0) $response[] = '*Chance of Death*: :skull: '.$death_rate.'%';
      $response[] = '*Adventuring party*: ('.count($adventurers).')';
      foreach ($adventurers as $adventurer) $response[] = $adventurer->get_display_name();
      $response[] = '';
      $response[] = 'To confirm your departure, type:';
      $response[] = '`/rpg quest q'.$quest->qid.' '.$adventurer_args.' CONFIRM`';
      $this->respond($response);
      return FALSE;
    }

    // Put together the adventuring party.
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



  /**
   * Go explore the map.
   */
  protected function cmd_explore ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'explore';

    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    // If there's no coordinates entered, show the map.
    if (empty($args) || empty($args[0])) {
      $this->cmd_map($args);
      return;
    }

    // Get the coordinates: ex. A4.
    $coord = strtoupper(array_shift($args));
    // Regex the line to scrub out the letters.
    $row = preg_replace('/[^0-9]/', '', $coord);
    $col = preg_replace('/[^a-zA-Z]/', '', $coord);
    if (empty($row) || empty($col)) {
      $this->respond('Please enter the coordinates without any spaces. Example: `/rpg explore A4 [ADVENTURER NAMES (comma-separated)]`');
      return false;
    }

    // Load the Location.
    $location = Location::load(array(
      'row' => $row,
      'col' => Location::get_number($col),
    ));
    // Check if the Location exists.
    if (empty($location)) {
      $this->respond("Location ".$coord." is not on the map.\n", RPGSession::PERSONAL, false);
      $this->cmd_map($args);
      return;
    }
    // Check if the Location is already revealed.
    if ($location->revealed) {
      // Load up the Guild that revealed this location.
      $guild = Guild::load(array('gid' => $location->gid));
      $revealed_text = empty($guild) ? '' : ' by '.$guild->get_display_name();
      if ($guild->gid == $player->gid) $revealed_text = ' you';
      $this->respond('Location '.$coord.' was already explored'.$revealed_text.'.');
      return false;
    }
    
    // Get the adventurers going.
    if (empty($args)) {
      $response = array();
      $response[] = 'Please choose Adventurers to go exploring. Type: `/rpg explore '.$coord.' [ADVENTURER NAMES (comma-separated)]`';
      // Also show the list of available adventurers.
      $response[] = '';
      $response[] = 'Adventurers available for exploring:';
      foreach ($player->get_adventurers() as $adventurer) {
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name();
      }
      $this->respond($response);
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
    $success = $this->check_for_valid_adventurers($player, $list);
    if (!$success['success']) {
      $success['msg'][] = $this->get_typed($cmd_word, $orig_args);
      $this->respond(implode("\n", $success['msg']));
      return FALSE;
    }
    // Get the list of adventurers.
    $adventurers = $success['data']['adventurers'];
    $has_strider = false;
    foreach ($adventurers as $adventurer) $has_strider = $has_strider || ($adventurer->class == 'strider');


    $response = array();

    // Check the party size requirement.
    $num_adventurers = count($adventurers);
    // Check if a Strider is in the list of adventurers, and if so, only require 1 minimum.
    $min_allowed = $has_strider ? 1 : 2;
    $too_few = $num_adventurers < $min_allowed;
    if ($too_few) {
      $response[] = 'You need at least '.$min_allowed.' adventurer'.($min_allowed > 1 ? 's' : '').' to go exploring.';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }
    
    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Calculate the duration.
    $duration = $location->get_duration($player, $adventurers);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = '*Quest*: Exploration';
      $response[] = '*Duration*: '.$this->get_duration_as_hours($duration);
      $response[] = '*Adventuring party*: ('.count($adventurers).')';
      foreach ($adventurers as $adventurer) {
        $response[] = $adventurer->get_display_name();
      }
      $response[] = '';
      $response[] = 'To confirm your departure, type:';
      $response[] = '`/rpg explore '.$coord.' '.$adventurer_args.' CONFIRM`';
      $this->respond($response);
      return FALSE;
    }

    // Create the exploration "quest".
    $quest = new Quest (array(
      'gid' => $player->gid,
      'locid' => $location->locid,
      'type' => Quest::TYPE_EXPLORE,
      'name' => 'Explore '.$location->get_coord_name(),
      'icon' => ':explore:',
      'created' => time(),
      'active' => false,
      'permanent' => false,
      'reward_gold' => 0,
      'reward_exp' => 0,
      'reward_fame' => 0,
      'duration' => 0,
      'cooldown' => 0,
      'min_party_size' => 2,
      'max_party_size' => 0,
    ));
    $success = $quest->save();
    if ($success === false) {
      $this->respond('There was a problem saving the exploration quest. Please talk to Paul.');
      return FALSE;
    }

    // Put together the adventuring party.
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

    $this->respond($names.' set'.($name_count == 1 ? 's' : '').' off to explore '.$location->get_coord_name().' returning in '.$this->get_duration_as_hours($duration).'.');
  }



  /**
   * View the map.
   */
  protected function cmd_map ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    $season = $this->load_current_season();
    $map = Map::load(array('season' => $season));
    $locations = Location::load_multiple(array('mapid' => $map->mapid));

    $response = array();
    $response[] = '[MAP GOES HERE]';

    foreach ($locations as $location) {
      if ($location->revealed) continue;
      $response[] = $location->get_display_name().': `/rpg explore '.$location->get_coord_name().' [ADVENTURER NAMES (comma-separated)]`';
    }

    // Also show the list of available adventurers.
    $response[] = '';
    $response[] = 'Adventurers available for exploring:';
    foreach ($player->get_adventurers() as $adventurer) {
      if (!empty($adventurer->agid)) continue;
      $response[] = $adventurer->get_display_name();
    }

    $response[] = '';
    $response[] = 'To explore a location on the map, type: `/rpg explore [LETTER][NUMBER] [ADVENTURER NAMES (comma-separated)]` (ex: `/rpg explore A4 Morgan, Gareth`).';

    $this->respond($response);
  }




  /**
   * Select your champion.
   */
  protected function cmd_champion ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    $response = array();

    // Make sure they give a name.
    if (empty($args) || empty($args[0])) {
      $response[] = 'Please choose which adventurer should be your Champion. You may only choose one.';
      $response[] = '';
      $response[] = 'Your adventurers:';
      foreach ($player->get_adventurers() as $adventurer) {
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name();
      }
      $this->respond($response);
      return FALSE;
    }

    // Check if the adventurer they named exists for them.
    $name = implode(' ', $args);
    $champion = Adventurer::load(array('name' => $name, 'gid' => $player->gid), true);
    if (empty($champion)) {
      $response[] = 'You do not have an adventurer by the name of "'.$name.'".';
      $response[] = '';
      $response[] = 'Your adventurers:';
      foreach ($player->get_adventurers() as $adventurer) {
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name();
      }
      $this->respond($response);
      return FALSE;
    }

    // Load up the old champ to remove their title.
    $old = Adventurer::load(array('champion' => true, 'gid' => $player->gid));
    if (!empty($old)) {
      if ($old->aid == $champion->aid) {
        $this->respond($champion->get_display_name().' is already your Champion.');
        return FALSE;
      }

      $old->champion = false;
      $success = $old->save();
      if ($success === false) {
        $this->respond('There was an error removing the old Champion. Go talk to Paul.');
        return FALSE;
      }
    }

    $champion->champion = true;
    $success = $champion->save();
    if ($success === false) {
      $this->respond('There was an error saving the new Champion. Go talk to Paul.');
      return FALSE;
    }

    if (!empty($old)) $response[] = $old->name.' was denounced as the old Champion.';
    $response[] = 'All hail '.$champion->get_display_name().', the new Champion of '.$player->get_display_name().'!';

    $this->respond($response);
  }



  /**
   * Select your leaderboard.
   */
  protected function cmd_leaderboard ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    $orig_max = 10;
    $max = $orig_max;
    // Set the max based on the argument provided by user.
    if (!empty($args) && !empty($args[0])) {
      $max = (strtolower($args[0]) == 'all') ? 0 : (int)$args[0];
    }

    $response = array();
    $response[] = ($max > 0 ? 'Top '.$max.' ' : '') .'Guild Ranking:';

    // Load all Guilds.
    $guilds = Guild::load_multiple(array());
    if (empty($guilds)) {
      $this->respond('There are no Guilds? Go talk to Paul because that seems like an error.');
      return FALSE;
    }

    // Sort Guilds by fame.
    usort($guilds, array('Guild','sort'));

    $count = 0;
    foreach ($guilds as $guild) {
      $count++;
      $response[] = $this->addOrdinalNumberSuffix($count).': ('.$this->get_fame($guild->get_total_points()).') '.$guild->get_display_name();
      if ($count == $max) break;
    }

    if ($max == $orig_max) {
      $response[] = '';
      $response[] = 'To view all Guilds, type: `/rpg leader all`.';
    }

    $this->respond($response);
  }



  /**
   * Edit your Guild information.
   */
  protected function cmd_edit ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    $response = array();

    if (empty($args) || empty($args[0])) {
      $response[] = 'You may edit the following information:';
      $response[] = '- Guild emoji: `/rpg edit icon [ICON]`';

      $this->respond($response);
      return FALSE;
    }

    if ($args[0] == 'icon') {
      if (!isset($args[1])) {
        $this->respond('You must include the new emoji icon alias (example: `/rpg edit icon :skull:`).');
        return FALSE;
      }

      $icon = $args[1];
      if (strpos($icon, ':') !== 0 || strrpos($icon, ':') !== strlen($icon)-1) {
        $this->respond('You must include a valid emoji icon alias (example: `/rpg edit icon :skull:`).');
        return FALSE;
      }

      $player->icon = $icon;
      $success = $player->save();
      if ($success === false) {
        $this->respond('There was an error saving your Guild information. Talk to Paul.');
        return FALSE;
      }

      $this->respond('Your icon has been changed to: '.$player->get_display_name());
      return TRUE;
    }
  }



  /**
   * Upgrade your Guild with additional benefits.
   */
  protected function cmd_upgrade ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'upgrade';

    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    // If there are no arguments, list the upgrades.
    if (empty($args) || empty($args[0])) {
      $response = array();
      $response[] = 'You can purchase the following upgrades:';
      $response[] = '';

      $upgrades = $player->get_available_upgrades();
      foreach ($upgrades as $upgrade) {
        $response[] = '*'.$upgrade->get_display_name() .'* for '. $this->get_currency($upgrade->cost) .' and '. $this->get_duration_as_hours($upgrade->duration).' `/rpg upgrade '.$upgrade->name_id.'`';
      }

      $this->respond($response);
      return TRUE;
    }

    // Load up the upgrade.
    $upgrade_name = array_shift($args);
    $upgrade = Upgrade::load(array('name_id' => $upgrade_name));
    if (empty($upgrade)) {
      $this->respond('The upgrade "'.$upgrade_name.'" is not available.');
      return FALSE;
    }

    // Check if they already have this upgrade.
    if ($player->has_upgrade($upgrade)) {
      $this->respond('You already have *'.$upgrade->get_display_name(false).'*.');
      return FALSE;
    }

    // Check that they meet the requirements.
    if (!$player->meets_requirement($upgrade)) {
      $this->respond('You do not meet the requirements of *'.$upgrade->get_display_name(false).'*.');
      return FALSE;
    }

    // Try to purchase the upgrade.
    if ($player->gold < $upgrade->cost) {
      $this->respond('You do not have enough gold to purchase the upgrade *'.$upgrade->get_display_name(false).'*.');
      return FALSE;
    }

    // Check that they confirmed the upgrade.
    $response = array();
    $confirm = array_pop($args);
    if ($confirm != 'CONFIRM') {
      $response[] = '*Upgrade*: '.$upgrade->get_display_name(false);
      if (!empty($upgrade->description)) $response[] = '*Description*: '.$upgrade->description;
      $response[] = '*Cost*: '.$this->get_currency($upgrade->cost);
      $response[] = '*Duration*: '.$this->get_duration_as_hours($upgrade->duration);
      $response[] = '';
      $response[] = 'To confirm your departure, type:';
      $response[] = '`/rpg '.$cmd_word.' '.implode(' ', $orig_args).' CONFIRM`';
      $this->respond($response);
      return TRUE;
    }

    // Start the upgrade purchase.
    $player->gold -= $upgrade->cost;
    $success = $player->save();
    if ($success === false) {
      $this->respond('There was a problem saving your Guild when purchasing the '.$upgrade->get_display_name(false).' upgrade. Please talk to Paul.');
      return FALSE;
    }

    // Queue the upgrade for completion.
    $duration = $upgrade->duration;
    $queue = $upgrade->queue( $duration, $player->gid );
    if (empty($queue)) {
      $this->respond('There was a problem adding the upgrade to the queue. Please talk to Paul.');
      return FALSE;
    }

    $this->respond('*'. $upgrade->get_display_name(false) .'* was purchased for '.$this->get_currency($upgrade->cost).' and will be upgraded in '.$this->get_duration_as_hours($duration).'.');
  }



  /**
   * See your inventory of items.
   */
  protected function cmd_inventory ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    $response = array();

    // If there's no item name, show the whole inventory.
    if (empty($args) || empty($args[0])) {
      // Get the Guild's items.
      $items = $player->get_items();
      $response[] = 'Your inventory:';
      // Compact same-name items.
      $compact_items = $this->compact_items($items);
      foreach ($compact_items as $citemid => $citems) {
        $count = count($citems);
        if ($count <= 0) continue;
        $response[] = ($count > 1 ? $count.'x ' : ''). $citems[0]->get_display_name();
      }

      if (empty($compact_items)) $response[] = '_Empty_';

      $this->respond($response);
      return TRUE;
    }

    // Show more details about the item named.
    $item_name = implode(' ', $args);
    $item = Item::load(array('gid' => $player->gid, 'name' => $item_name), true);

    if (empty($item)) {
      $this->respond('You do not have an item named "'.$item_name.'".');
      return FALSE;
    }

    // Show the item details.
    $response[] = $item->get_display_name();
    $response[] = $item->get_description();

    $this->respond($response);
  }

  /**
   * Power up an Adventurer.
   */
  protected function cmd_powerup ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'powerup';

    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();
    
    // You must specify class name and then the adventurer name.
    if (empty($args) || empty($args[0])) {
      $this->respond('You must specify the adventurer name and then the class name. Example: `/rpg powerup Morgan LeClair Shaman`');
      return FALSE;
    }

    $response = array();

    // Get the class name.
    $class_name = array_pop($args);
    $adventurer_class = AdventurerClass::load(array('name_id' => $class_name), true);
    if (empty($adventurer_class)) {
      $response[] = 'Please specify a valid class name. Example: `/rpg powerup Morgan LeClair Shaman`';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // If they didn't specify an adventurer name, error out.
    if (empty($args) || empty($args[0])) {
      $response[] = 'Please specify a valid adventurer name. Example: `/rpg powerup Morgan LeClair Shaman`';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Get the adventurer name.
    $adventurer_name = implode(' ', $args);
    $adventurer = Adventurer::load(array('gid' => $player->gid, 'name' => $adventurer_name), true);
    if (empty($adventurer)) {
      $response[] = 'Please specify a valid class name. Example: `/rpg powerup Morgan LeClair Shaman`';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // If the adventurer is out adventuring, error out.
    if (!empty($adventurer->agid)) {
      $this->respond($adventurer->get_display_name().' is currently out on an adventure. You can only power up an Adventurer once they have returned.');
      return FALSE;
    }

    // Check if the adventurer already has a class.
    if (!empty($adventurer->class)) {
      $this->respond($adventurer->get_display_name().' has already been powered up. Please choose an adventurer that has not been powered up.');
      return FALSE;
    }

    // Check if the player has the item they need to powerup.
    $item_needed = 'powerstone_'.$adventurer_class->name_id;
    $items = &$player->get_items();
    $has_item = false;
    foreach ($items as &$item) {
      if ($item->name_id != $item_needed) continue;
      $has_item = true;
      break;
    }

    // If they do not have the item, we're done.
    if (!$has_item) {
      // Get ItemTemplate for the name.
      $item_template = ItemTemplate::load(array('name_id' => $item_needed));
      $response[] = 'A '.$item_template->get_display_name().' is required to power up '.$adventure->get_display_name().' as a '.$adventurer_class->get_display_name().'.';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Power up the Adventurer.
    $adventurer->set_adventurer_class($adventurer_class);
    $success = $adventurer->save();
    if ($success === false) {
      $this->respond('There was an error saving your Adventurer during the power up. Please talk to Paul.');
      return FALSE;
    }

    // Remove the item.
    $success = $player->remove_item($item);
    if ($success === false) {
      $this->respond('There was an error saving the item used to power up your Adventurer. Please talk to Paul.');
      return FALSE;
    }
    
    // If successful, delete the item permanently.
    $item->delete();
    
    $response[] = $adventurer->get_display_name().' has been powered up as a '.$adventurer_class->get_display_name().'!';
    $this->respond($response);
  }



  protected function cmd_test ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    $player = $this->load_current_player();

    // Give the player a powerstone.
    $item_template = ItemTemplate::load(array('name_id' => 'powerstone_shaman'));
    
    $items = &$player->get_items();
    
    d($items);
    $player->add_item($item_template);
    d($items);
    $item = $items[0];
    $player->remove_item($item);
    d($items);

    return false;
    
    //$adventurers = $player->get_adventurers();
    //$adventurer = $adventurers[0];

    // $adventurer = Adventurer::load(array('name' => 'cath', 'gid' => $player->gid), true);
    // d($adventurer);
    // $adventurer->give_exp(100);
    // d($adventurer);
    // return false;

    // Create a fake location.
    $types = Location::types();
    $loc_type = array_rand($types);
    $loc_type = $types[$loc_type];
    $star = 1; //rand(1, 5);

    // d($loc_type);
    // d($star);

    $location = new Location (array(
      'locid' => 10,
      'mapid' => 1,
      'name' => 'Fancy Swamp',
      'type' => $loc_type,
      'created' => time(),
      'revealed' => false,
      'star_min' => $star, //($star > 1 ? $star - 1 : $star),
      'star_max' => $star,
      'row' => 13,
      'col' => 13,
    ));

    // Create quests for the location.
    //$quests = Quest::generate_quests($location, false);
    //d($quests);

    $quest_data = array(
      'locid' => 10,
      'name' => 'Test Quest',
      'type' => Quest::TYPE_INVESTIGATE,
      'stars' => 1,
      'active' => true,
      'reward_gold' => 300,
      'reward_exp' => 200,
      'reward_fame' => 100,
      'duration' => 1000,
      'party_size_min' => 1,
      'party_size_max' => 3,
      'level' => 6,
      'success_rate' => 75,
      'death_rate' => 25,
    );
    $quest = new Quest ($quest_data);
    d($quest_data);
    d($player);

    $adventurers = $player->get_best_adventurers($quest->party_size_max);
    d($adventurers);

    d($quest->reward_exp);
    d($quest->get_reward_exp($player, $adventurers));

    d($quest->reward_gold);
    d($quest->get_reward_gold($player, $adventurers));

    d($quest->reward_fame);
    d($quest->get_reward_fame($player, $adventurers));

    d($quest->get_death_rate($player, $adventurers));
    d($quest->get_success_rate($player, $adventurers));
    d($quest->get_duration($player, $adventurers));
  }



  /* =================================================================================================
     _____ __  ______  ____  ____  ____  ______   ________  ___   ______________________  _   _______
    / ___// / / / __ \/ __ \/ __ \/ __ \/_  __/  / ____/ / / / | / / ____/_  __/  _/ __ \/ | / / ___/
    \__ \/ / / / /_/ / /_/ / / / / /_/ / / /    / /_  / / / /  |/ / /     / /  / // / / /  |/ /\__ \ 
   ___/ / /_/ / ____/ ____/ /_/ / _, _/ / /    / __/ / /_/ / /|  / /___  / / _/ // /_/ / /|  /___/ / 
  /____/\____/_/   /_/    \____/_/ |_| /_/    /_/    \____/_/ |_/\____/ /_/ /___/\____/_/ |_//____/  
                                                                                                     
  ==================================================================================================== */

  protected function load_current_season () {
    return 1;
  }

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
    return ':moneybag: '.number_format($amount).' gp';
  }

  protected function get_duration_as_hours ($duration) {
    $seconds = $duration;
    $hours = floor($seconds / (60 * 60));
    $seconds -= ($hours * 60 * 60);
    $minutes = floor($seconds / 60);
    $seconds -= ($minutes * 60);
    
    return ($hours > 0 ? $hours.' hours, ' : '').($minutes > 0 ? $minutes.' minutes, ' : '').($seconds > 0 ? $seconds.' seconds' : '');
  }

  protected function get_fame ($fame) {
    return ':trophy: '.number_format($fame);
  }

  protected function get_typed ($cmd, $args) {
    return "\n(You typed: `/rpg ".$cmd." ".implode(' ', $args)."`)";
  }

  protected function get_stars ($stars, $max = 5) {
    $text = '';
    // for ($i = 1; $i <= $max; $i++) $text .= ($i <= $stars ? ':quest-star:' : ':quest-star-empty:');
    for ($i = 1; $i <= $stars; $i++) $text .= ':star:';
    return $text;
  }

  protected function get_difficulty ($rate) {
    if ($rate <= 0) return ':rpg-quest-diff0:';
    if ($rate <= 35) return ':rpg-quest-diff1:';
    if ($rate <= 50) return ':rpg-quest-diff2:';
    if ($rate <= 75) return ':rpg-quest-diff3:';
    
    return ':rpg-quest-diff4:';
  }

  protected function get_difficulty_legend () {
    return 'Difficulty Legend:'."\n".':rpg-quest-diff0: Impossible, :rpg-quest-diff1: Risky, :rpg-quest-diff2: Difficult, :rpg-quest-diff3: Challenging, :rpg-quest-diff4: Recommended, :skull: Adventurers can die';
  }

  protected function addOrdinalNumberSuffix ($num) {
    if (!in_array(($num % 100), array(11,12,13))) {
      switch ($num % 10) {
        // Handle 1st, 2nd, 3rd
        case 1: return $num.'st';
        case 2: return $num.'nd';
        case 3: return $num.'rd';
      }
    }
    return $num.'th';
  }

  protected function check_for_valid_adventurers ($guild, $names, $check_is_available = true) {
    $response = array(
      'success' => false,
      'msg' => array(),
      'data' => array(),
    );

    // Check if the adventurers are valid.
    $adventurers = array();
    $adventurer_ids = array();

    foreach ($names as $name) {
      $trim_name = trim($name);
      $data = array(
        'name' => $trim_name,
        'gid' => $guild->gid,
      );

      if ($check_is_available) $data['agid'] = 0;

      // Check if the Adventurer is available.
      $adventurer = Adventurer::load($data, true);

      if (empty($adventurer)) {
        $response['msg'][] = 'The adventurer named "'.$trim_name.'" is not valid'.($check_is_available ? ' or available' : '').'. Please double-check that the name is correct.';
        return $response;
      }

      // Check that this adventurer wasn't already included.
      if (in_array($adventurer->aid, $adventurer_ids)) {
        $response['msg'][] = 'The same adventurer was included more than once (named "'.$trim_name.'"). Please use more letters from their name and do not re-use the same character.';
        return $response;
      }

      $adventurers[] = $adventurer;
      $adventurer_ids[] = $adventurer->aid;
    }

    // Success. Return the data.
    $response['success'] = true;
    $response['data'] = compact('adventurers', 'adventurer_ids');
    return $response;
  }

  protected function compact_items ($items) {
    $compact_items = array();

    // Compact same-name items.
    foreach ($items as &$item) {
      if (!isset($compact_items[$item->itid])) $compact_items[$item->itid] = array();
      $compact_items[$item->itid][] = $item;
    }

    return $compact_items;
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
    if (is_array($text)) $text = implode("\n", $text);
    else if (!is_string($text)) $text = '';
    $this->response['text'] = $text;

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