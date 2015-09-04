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
  
  function __construct() {
    // NOTE: For command keys (the actual text the user will type), short-forms should come AFTER the long forms.
    //     Example:
    //     $this->register_callback(array('updates', 'update'), 'cmd_updates');
    //     'updates' needs to come before 'update' or it won't properly recognize the 'update' command.

    $this->register_callback(array('help'), 'cmd_help');
    $this->register_callback(array('updates', 'update'), 'cmd_updates');

    $this->register_callback(array('register'), 'cmd_register');
    $this->register_callback(array('edit'), 'cmd_edit');
    $this->register_callback(array('upgrades', 'upgrade'), 'cmd_upgrade');
    $this->register_callback(array('shop'), 'cmd_shop');
    $this->register_callback(array('items', 'item', 'inventory', 'inv'), 'cmd_inventory');

    $this->register_callback(array('recruit'), 'cmd_recruit');
    $this->register_callback(array('status'), 'cmd_status');
    $this->register_callback(array('dismiss'), 'cmd_dismiss');
    $this->register_callback(array('champion'), 'cmd_champion');
    $this->register_callback(array('powerup', 'power'), 'cmd_powerup');
    $this->register_callback(array('revive'), 'cmd_revive');
    
    $this->register_callback(array('quest'), 'cmd_quest');
    $this->register_callback(array('map'), 'cmd_map');
    $this->register_callback(array('explore'), 'cmd_explore');

    $this->register_callback(array('challenge'), 'cmd_challenge');

    $this->register_callback(array('report'), 'cmd_report');
    $this->register_callback(array('leaderboard', 'leader'), 'cmd_leaderboard');


    $this->register_callback(array('admin'), 'cmd_admin');
  }



  /**
   * Display the available commands.
   */
  protected function cmd_help ($args = array()) {
    $response = array();

    if (!empty($args) && $args[0] == 'intro') {
      $response[] = $this->introduction_message();
      $this->respond($response);
      return TRUE;
    }

    $response[] = '*Introduction*:';
    $response[] = 'To see the introduction and explanation of the game, type: `help intro`';
    $response[] = '';

    $response[] = '*Commands:*';
    $response[] = 'Register Guild: `register [GUILD EMOJI] [GUILD NAME]` (example: `register :fake-icon: Death\'s Rattle`)';
    $response[] = 'Leaderboard rankings: `leaderboard [all]`';
    $response[] = '';

    $response[] = 'Guild information: `report [GUILD NAME]` (leave guild name blank to see your own report)';
    $response[] = 'Set your Guild\'s Champion: `champion [NAME]`';
    $response[] = 'Edit your Guild\'s information: `edit`';
    $response[] = '';

    //$response[] = 'View the Map: `map`';
    $response[] = 'Colosseum - Challenge another Guild: `challenge`';
    $response[] = 'Explore the Map: `explore`';
    $response[] = 'Quests: `quest`';
    $response[] = 'Dismiss a Quest: `quest dismiss`';
    $response[] = 'Approve a Multi-Guild Quest: `quest approve`';
    $response[] = 'Cancel a Multi-Guild Quest: `quest cancel`';
    $response[] = '';

    $response[] = 'Recruit Adventurers: `recruit`';
    $response[] = 'View an Adventurer\'s status: `status [ADVENTURER NAME]`';
    $response[] = 'Dismiss an Adventurer: `dismiss`';
    $response[] = 'Power Up an Adventurer: `powerup`';
    $response[] = 'Revive an Adventurer: `revive`';
    $response[] = '';

    $response[] = 'Upgrade your Guild: `upgrade`';
    $response[] = 'Shop for items: `shop`';
    $response[] = 'Inventory: `inv`';
    $response[] = 'View an item: `item [ITEM NAME]`';
    $response[] = '';
    
    $response[] = '*Other*';
    $response[] = 'See recent updates: `updates [VERSION]` (Exclude the version to show the newest updates)';

    $this->respond($response);
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
      $this->respond($response, (isset($args[1]) && $args[1] == 'monkey123' ? RPGSession::CHANNEL : RPGSession::PERSONAL) );
    }
  }



  /**
   * Register your Guild to play in the current season.
   */
  protected function cmd_register ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'register';

    $player = $this->get_current_player();
    // If the player exists already, no need to register.
    if (!empty($player)) {
      $this->respond('You have already registered this season with *'.$player->name.'*.');
      return FALSE;
    }

    // Get current season.
    $season = Season::current();
    if (empty($season)) {
      $this->respond('You must wait for a new Season to begin.');
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Check if they gave a Guild name.
    if (empty($args) || count($args) < 2 || empty($args[0]) || empty($args[1])) {
      $this->respond('Please select a Guild name. Type: `register [GUILD EMOJI] [GUILD NAME]` (example: `register :skull: Death\'s Rattle`).');
      return FALSE;
    }

    // Extract the icon and confirm the format.
    $icon = array_shift($args);
    if (strpos($icon, ':') !== 0 || strrpos($icon, ':') !== strlen($icon)-1) {
      $this->respond('You must enter an emoji icon as the first argument (example: `register :skull: Death\'s Rattle`).');
      return FALSE;
    }

    $name = implode(' ', $args);
    if (empty($name)) {
      $this->respond('You must choose a name for your Guild.');
      return FALSE;
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Make sure Guild only contains alpha-numeric characters and a few extra symbols.
    $name = preg_replace("/[^A-Za-z0-9!&%$#\.\-\,\(\)\+<>\/\\ ]/", '', $name);

    // Create the new player.
    $data = array(
      'slack_user_id' => $this->data['user_id'],
      'username' => $this->data['user_name'],
      'name' => $name,
      'icon' => $icon,
      'season' => $season->sid,
      'gold' => 500, // Starting gold.
    );
    $player = new Guild ($data);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = "Are you sure you want to register as ".$player->get_display_name()."?";
      $response[] = "Once you've chosen a name, you cannot change it.";
      $response[] = "_Note: If some of your special characters are missing from your name, it means they are not allowed. Sorry!_";
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

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

    // Check if we should add a starting Quest (based on whether or not there are revealed locations).
    $season = Season::current();
    $map = $season->get_map();
    // Get list of all revealed locations.
    $types = Location::types();
    $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    // Looks like there's some revealed locations, so let's generate a personal quest for this new Guild.
    if (!empty($locations)) {
      $location = $locations[array_rand($locations)];
      if (!empty($location)) {
        $new_quest = Quest::generate_personal_quest($player, $location);
      }
    }

    $this->respond('@'.$player->username.' just registered a Guild called '.$player->get_display_name().'!', RPGSession::CHANNEL);

    $response = array();
    $response[] = 'You just registered '.$player->get_display_name().' and '.$num_adventurers.' new adventurer'.($num_adventurers > 1 ? 's have' : ' has').' joined you!';
    $response[] = 'Welcome,';
    foreach ($adventurers as $adventurer) $response[] = $adventurer->get_display_name();

    if (isset($new_quest) && !empty($new_quest)) {
      $response[] = '';
      $response[] = 'It looks like other Guilds have been exploring the region around the Capital, so there is a Quest waiting for you. Type `quest` to view the list of available Quests.';
    }

    $response[] = '';
    $response[] = $this->introduction_message();
    $response[] = '';
    $response[] = '_For more help, type:_ `help`';

    $this->respond($response);
  }



  /**
   * Show your Guild report.
   */
  protected function cmd_report ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
    $guild = $player;
    $guild_is_player = true;

    // Check if they want to find the status of another guild.
    if (!empty($args) && !empty($args[0])) {
      $season = Season::current();
      $guild_name = implode(' ', $args);
      $guild = Guild::load(array('name' => $guild_name, 'season' => $season->sid), true);
      if (empty($guild)) {
        $this->respond('There is no guild by the name of "'.$guild_name.'".');
        return FALSE;
      }
      $guild_is_player = false;
    }

    $adventurers = $guild->get_adventurers();
    
    $response = array();
    $response[] = '*Guild name*: '.$guild->get_display_name(false);
    $response[] = '*Fame*: '.Display::get_fame($guild->fame);
    $response[] = '*Founder:* @'.$guild->username;
    $response[] = '*Founded on:* '.date('F j, Y \a\t g:i A', $guild->created);
    if ($guild_is_player) $response[] = '*Gold*: '.Display::get_currency($guild->gold);

    // Show adventurers.
    $adv_total = $guild->get_adventurers_count();
    $adv_alive = $guild->get_adventurers_count(false);
    $adv_undead = $adv_total - $adv_alive;
    $response[] = '*Adventurers*:'.($guild_is_player ? ' '.$adv_alive.' / '.$guild->adventurer_limit.($adv_undead <= 0 ? '' : ' (+'.$adv_undead.' Undead)') : '');
    foreach ($adventurers as $adventurer) {
      $adv_status = !empty($adventurer->agid) ? ' [Adventuring]' : '';
      $response[] = $adventurer->get_display_name(false, true, true, true, true, $guild_is_player).($guild_is_player ? $adv_status : '');
    }

    // Show upgrades.
    $upgrades = $guild->get_upgrades();
    if ($guild_is_player && count($upgrades) > 0) {
      $response[] = '';
      $response[] = '*Upgrades*:';
      foreach ($upgrades as $upgrade) {
        $response[] = $upgrade->get_display_name(true, false);
      }
    }

    // Show exploration/quests/challenges the adventurers are currently on.
    $groups = AdventuringGroup::load_multiple(array('gid' => $guild->gid));
    if (!empty($groups)) {
      $expeditions = array();
      foreach ($groups as $group) {
        if (!isset($expeditions[$group->task_type])) $expeditions[$group->task_type] = array();
        $expeditions[$group->task_type][] = $group;
      }

      foreach ($expeditions as $exped => $list) {
        $response[] = '';
        $response[] = '*Current '.$exped.'s*:';
        foreach ($list as $group) {
          $task = $group->get_task();
          if (empty($task)) continue;
          $gadventurers = $group->get_adventurers();
          
          if ($exped == 'Quest' && $task->type == Quest::TYPE_EXPLORE) {
            $location = $task->get_location();
            $name = 'Exploring the map at '.$location->get_coord_name();
          }
          else {
            $name = $task->get_display_name();
          }

          $queue = $task->get_queue();
          if (!empty($queue)) {
            $eta = $queue->execute - time();
            $response[] = $name .' _(returning '.($eta > 0 ? 'in '.Display::get_duration($eta) : 'now').')_';
          }
          else {
            $response[] = $name .($exped == 'Quest' ? ' _(waiting for more Guilds)_' : ' _(waiting for opponent to respond)_');
          }

          foreach ($gadventurers as $adventurer) {
            $response[] = '— '.$adventurer->get_display_name(false, true, true, true, true, $include_level);
          }
        }
      }
    }

    $this->respond($response);
  }



  /**
   * Recruit new Adventurers.
   */
  protected function cmd_recruit ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'recruit';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
    
    $response = array();

    // If no Adventurer is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      // Load up any adventurers in the tavern.
      $adventurers = Adventurer::load_multiple(array('available' => true, 'gid' => 0));

      if (empty($adventurers)) {
        $this->respond('There are no adventurers in the Tavern.');
        return FALSE;
      }

      $response[] = 'Adventurers in the Tavern:';
      foreach ($adventurers as $adventurer) {
        $adventurer_cost = $adventurer->level * 250;
        $response[] = $adventurer->get_display_name().' for '.Display::get_currency($adventurer_cost).'  `recruit '.$adventurer->name.'`';
      }

      $this->respond($response);
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Check if the player is at their max (excluding Undead).
    if ($player->get_adventurers_count(false) >= $player->adventurer_limit) {
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

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = $this->show_adventurer_status($adventurer);
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
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

    $this->respond($player->get_display_name().' just recruited a new adventurer, '.$adventurer->get_display_name().'.', RPGSession::CHANNEL);
    $this->respond('You recruited '.$adventurer->get_display_name().' for '.Display::get_currency($adventurer_cost).'.');
  }



  /**
   * See status of an Adventurer.
   */
  protected function cmd_status ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'status';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();

    // If no Adventurer is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      $response[] = '*Your Adventurers*:';
      $adventurers = $player->get_adventurers();
      foreach ($adventurers as $adventurer) {
        $response[] = $adventurer->get_display_name(false);
      }
      $response[] = '';
      $response[] = 'Type `status [ADVENTURER NAME]` to see the status of that Adventurer.';
      $this->respond($response);
      return FALSE;
    }

    // They chose a name, so let's check if that adventurer is available.
    $adventurer_name = implode(' ', $args);
    $adventurer = Adventurer::load(array('name' => $adventurer_name, 'gid' => $player->gid, 'dead' => false), true);
    if (empty($adventurer)) {
      $this->respond('You do not have an Adventurer by the name of "'.$adventurer_name.'".');
      return FALSE;
    }

    // Show the status.
    $response[] = $this->show_adventurer_status($adventurer);
    $this->respond($response);
  }



  /**
   * Dismiss a current Adventurer.
   */
  protected function cmd_dismiss ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'dismiss';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
    
    $response = array();

    // If no Adventurer is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      $adventurers = $player->get_adventurers();
      if (!empty($adventurers)) {
        $response[] = "You must select an Adventurer to dismiss.";
        $response[] = '';
        foreach ($adventurers as $adventurer) {
          $response[] = $adventurer->get_display_name();
        }
      }
      else {
        $response[] = "You have no Adventurers to dismiss.";
      }
      $this->respond($response);
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // They chose a name, so let's check if that adventurer is available.
    $adventurer = Adventurer::load(array('name' => implode(' ', $args), 'gid' => $player->gid, 'dead' => false), true);
    if (empty($adventurer)) {
      $this->respond('You do not have an Adventurer by the name of "'.implode(' ', $args).'".');
      return FALSE;
    }

    // If the adventurer is out adventuring, error out.
    if (!empty($adventurer->agid)) {
      $this->respond($adventurer->get_display_name().' is currently out on an adventure. You can only dismiss an Adventurer once they have returned.');
      return FALSE;
    }

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
      $response[] = $this->show_adventurer_status($adventurer);
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
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

    $this->respond($player->get_display_name().' has dismissed an adventurer, '.$adventurer->get_display_name().', who is now available in the Tavern.', RPGSession::CHANNEL);
    $this->respond('You have dismissed '.$adventurer->get_display_name().'.');
  }



  /**
   * See and go on quests.
   */
  protected function cmd_quest ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'quest';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();

    // If no Quest is selected, list the available ones.
    if (empty($args) || empty($args[0])) {
      // Load up all Global Quests.
      $quests = Quest::load_multiple(array('active' => true, 'multiplayer' => true));
      // Load up all private Quests.
      $private_quests = $player->get_quests();

      if (empty($quests) && empty($private_quests)) {
        $this->respond("There are no quests to undertake.\n\nTo open up quests, new locations need to be discovered. Type `explore` to start exploring the map.");
        return FALSE;
      }

      $response[] = '*Multi-Guild Quests available* (costs '.Display::get_fame(Quest::MULTIPLAYER_FAME_COST).' per Guild to register):';
      $quest_available = array();
      foreach ($quests as $quest) {
        // Skip quests that are full-up but aren't yet confirmed.
        if ($quest->is_ready()) continue;
        $quest_available[] = $quest;
        $cur_advs = $quest->get_registered_adventurers();
        $adv_count = count($cur_advs);
        $response[] = '`b'.$quest->qid.'` — '.ucwords($quest->type).' — _'. $quest->get_display_name(false) .'_ — '. Display::get_difficulty_stars($quest->stars, 30) . ($quest->death_rate > 0 ? ' — :skull:' : ''). ' — '. Display::show_adventurer_count($quest->party_size_max, $adv_count);
      }
      if (empty($quest_available)) $response[] = '_None_';

      $private_active_quests = array();
      foreach ($private_quests as $quest) {
        if ($quest->active == false || !empty($quest->agid)) continue;
        $private_active_quests[] = $quest;
      }

      $response[] = '';
      $response[] = '*Quests available* ('.count($private_active_quests).' / '.Quest::MAX_COUNT.'):';
      foreach ($private_active_quests as $quest) {
        // Get the best adventurers available for questing.
        $best_adventurers = $player->get_best_adventurers($quest->party_size_max);
        $best_bonus = Quest::make_bonus($player, $best_adventurers);
        $success_rate = $quest->get_success_rate($best_bonus, $best_adventurers);
        $response[] = '`q'.$quest->qid.'` — '.ucwords($quest->type).' — _'. $quest->get_display_name(false) .'_ — '. Display::get_difficulty_stars($quest->stars, $success_rate) . ($quest->death_rate > 0 ? ' — :skull:' : ''). ' — '. Display::show_adventurer_count($quest->get_party_size());
      }
      if (empty($private_active_quests)) $response[] = '_None_';

      // Also show the list of available adventurers.
      $response[] = '';
      $response[] = '*Adventurers available for Questing*:';
      $response[] = $this->show_available_adventurers($player);

      // Also show the list of available item modifiers.
      $response[] = '';
      $response[] = '*Modifier items*:';
      // Compact same-name items.
      $items = $player->get_items();
      $compact_items = $this->compact_items($items);
      foreach ($compact_items as $citemid => $citems) {
        $count = count($citems);
        if ($count <= 0) continue;
        if ($citems[0]->type != ItemType::KIT) continue;
        $kits[] = $citems[0];
        $response[] = '`i'.$citems[0]->iid.'` '. ($count > 1 ? $count.'x ' : ''). $citems[0]->get_display_name(false);
      }
      if (empty($kits)) $response[] = '_None_';

      $response[] = '';
      $response[] = 'To embark on a Quest, type: `quest [QUEST ID] [MODIFIER ITEM ID (optional)] [ADVENTURER NAMES (comma-separated)]` (example: `quest q23 `)';
      $response[] = 'To see a list of Quests you\'re participating in, type: `quest underway`';
      $response[] = 'To dismiss a Quest, type: `quest dismiss [QUEST ID]`';
      $response[] = 'To approve a Multi-Guild Quest, type: `quest approve [QUEST ID]`';
      $response[] = 'To cancel a Multi-Guild Quest, type: `quest cancel [QUEST ID]`';

      $this->respond($response);
      return FALSE;
    }

    // Check if the first argument is a secondary command, pass all of the information off to the secondary functions.
    $cmd_options = array('dismiss', 'approve', 'cancel', 'underway');
    if (in_array($args[0], $cmd_options)) {
      $cmd_secondary = array_shift($args);
      $this->{'cmd_quest_'.$cmd_secondary}($args);
      return TRUE;
    }

    // Check if Quest ID is available.
    $qarg = array_shift($args);
    $qtype = substr($qarg, 0, 1);
    $qid = substr($qarg, 1);
    if ($qtype == 'q') $quest = Quest::load(array('qid' => $qid, 'gid' => $player->gid, 'completed' => false, 'active' => true));
    else if ($qtype == 'b') $quest = Quest::load(array('qid' => $qid, 'multiplayer' => true, 'completed' => false, 'active' => true));

    if (!isset($quest) || empty($quest)) {
      $this->respond("This quest is not available or does not exist.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // If the quest is multiplayer, check if they're already registered for it.
    if ($quest->multiplayer) {
      $pgroup = AdventuringGroup::load(array('gid' => $player->gid, 'task_id' => $quest->qid, 'task_type' => 'Quest'));
      if (!empty($pgroup)) {
        if ($quest->is_ready()) {
          if ($quest->gid == $player->gid) {
            $this->respond("This Quest is waiting for you to give the final approval. Type: `quest approve b".$quest->qid."`".$this->get_typed($cmd_word, $orig_args));
          }
          else {
            $this->respond("You have already registered for this Quest. Please wait for the Quest Leader to give the final approval.".$this->get_typed($cmd_word, $orig_args));
          }
        }
        else {
          $this->respond("You have already registered for this Quest. Please wait for other Guilds to register.".$this->get_typed($cmd_word, $orig_args));
        }
        return FALSE;
      }
    }

    // Check if they can afford to pay the multiplayer fame cost.
    if ($quest->multiplayer && $player->fame < Quest::MULTIPLAYER_FAME_COST) {
      $this->respond("You do not have ".Display::get_fame(Quest::MULTIPLAYER_FAME_COST)." to register for this multi-guild Quest.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Get the adventurers going.
    if (empty($args)) {
      $response[] = 'Please choose Adventurers to go on this quest.';
      $response[] = '';
      $response[] = '*Adventurers available for Questing*:';
      $response[] = $this->show_available_adventurers($player);
      $response[] = '';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Check if there is an optional modifier item ID available.
    $kit = null;
    $iid = intval(substr($args[0], 1));
    if (!empty($args[0]) && substr($args[0], 0, 1) == 'i' && $iid > 0) {
      $iarg = array_shift($args);
      $kit = Item::load(array('iid' => $iid, 'gid' => $player->gid, 'type' => ItemType::KIT, 'on_hold' => false));
      if (empty($kit)) {
        $this->respond("This item is not available to use as a modifier. Please choose a different item or remove it.".$this->get_typed($cmd_word, $orig_args));
        return FALSE;
      }
    }

    // If this is a multiplayer quest and we already have a kit used, don't allow a second.
    if ($quest->multiplayer && !empty($quest->kit_id) && !empty($kit)) {
      $this->respond("Another Guild already chose an item to use on this Quest, so no other items can be used.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Get the adventurers going.
    if (empty($args)) {
      $response[] = 'Please choose Adventurers to go on this quest.';
      $response[] = '';
      $response[] = '*Adventurers available for Questing*:';
      $response[] = $this->show_available_adventurers($player);
      $response[] = '';
      $response[] = $this->get_typed($cmd_word, $orig_args);
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
      $this->respond($success['msg']);
      return FALSE;
    }
    // Get the list of adventurers.
    $adventurers = $success['data']['adventurers'];
    $num_adventurers = count($adventurers);

    // Check the party size requirement.
    if ($quest->multiplayer == false) {
      $too_few = $num_adventurers < $quest->party_size_min;
      $too_many = $num_adventurers > $quest->party_size_max;
      $adventurer_diff = $num_adventurers - $quest->party_size_max;
      $group_size = $quest->party_size_max;
    }
    // Allow up to the max (because it is multiplayer).
    else if ($quest->multiplayer) {
      $too_few = false;
      $cur_adventurers = $quest->get_registered_adventurers();
      $open_spots = $quest->party_size_max - count($cur_adventurers);
      $too_many = $num_adventurers > $open_spots;
      $adventurer_diff = $num_adventurers - $open_spots;
      $group_size = $open_spots;
    }

    if ($too_few || $too_many) {
      if ($too_few) {
        $response[] = 'You need at least '.$quest->party_size_min.' adventurer'.($quest->party_size_min == 1 ? '' : 's').' to embark on this quest.';
      }
      
      if ($too_many) {
        $response[] = 'There are '.abs($adventurer_diff).' too many adventurers for this quest. Please reduce the group to '.$group_size.' adventurer'.($group_size == 1 ? '' : 's').'.';
      }

      $response[] = '';
      $response[] = '*Adventurers available for Questing*:';
      $response[] = $this->show_available_adventurers($player);
      $response[] = '';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Get the bonuses that this player brings to the table.
    $quest_bonus = Quest::make_bonus($player, $adventurers, $kit);
    // If this is a multiplayer quest, merge in the rest of the participating Guilds' bonuses.
    if ($quest->multiplayer) {
      $registered_bonus = $quest->get_bonus();
      $quest_bonus->merge($registered_bonus);
    }

    // Check if the success rate is above 0%.
    if ($quest->multiplayer == false) {
      $success_rate = $quest->get_success_rate($quest_bonus, $adventurers);
      if ($success_rate <= 0) {
        $response[] = 'Your adventuring party has no chance of completing this quest. Please choose stronger adventurer(s).';
        $response[] = '';
        $response[] = '*Adventurers available for Questing*:';
        $response[] = $this->show_available_adventurers($player);
        $response[] = '';
        $response[] = $this->get_typed($cmd_word, $orig_args);
        $this->respond($response);
        return FALSE;
      }
    }
    
    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    $duration = $quest->get_duration($quest_bonus);
    $death_rate = $quest->get_death_rate($quest_bonus);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      // Set a fake success rate for multi-guild quests
      if (!isset($success_rate)) $success_rate = 30;
      $response[] = '*Quest*: '.$quest->get_display_name(false);
      $response[] = '*Type*: '.ucwords($quest->type);
      $response[] = '*Rating*: '.Display::get_difficulty_stars($quest->stars, $success_rate);
      if ($quest->multiplayer == false) {
        $response[] = '*Duration*: '.Display::get_duration($duration);
        $response[] = '*Chance of Success*: '.Display::get_difficulty($success_rate).' '.$success_rate.'%';
        if ($death_rate > 0) $response[] = '*Chance of Death*: :skull: '.$death_rate.'%';
      }
      else {
        if ($death_rate > 0) $response[] = '*Chance of Death*: :skull: _Yes_';
      }

      if (isset($kit)) $response[] = '*Modifier Item*: '. $kit->get_display_name(false);
      
      $response[] = '*Adventuring party ('.count($adventurers).')*:';
      foreach ($adventurers as $adventurer) $response[] = $adventurer->get_display_name(false);
      $response[] = '';
      
      // Add a multiplayer note.
      if ($quest->multiplayer) {
        // Show a note about being the quest leader.
        if (empty($quest->gid)) {
          $response[] = '*IMPORTANT NOTE*: _As the first registrant for this Quest, you will be deemed the Quest Leader and get the final say on whether or not the registered Guilds will embark on the Quest. You will receive a separate notification requiring a final approval before all Guilds depart on the Quest._';
          $response[] = '';
        }
        // Add the generic multiplayer note.
        else {
          $response[] = '_Note: These values will adjust as more Guilds register for this Quest._';
          $response[] = '';
        }
      }

      $response[] = $this->get_confirm($cmd_word, $orig_args);
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
    $quest_ready = $quest->is_ready(true);
    // If this is single-player, assign guild an deactivate.
    if ($quest->multiplayer == false) {
      $quest->agid = $advgroup->agid;
      $quest->gid = $player->gid;
      $quest->active = false;
    }
    // If this is multiplayer, only assign the guild if this is the first person to register.
    else if ($quest->multiplayer) {
      if (empty($quest->gid)) $quest->gid = $player->gid;
      if (empty($quest->agid)) $quest->agid = $advgroup->agid;
      // If the quest is ready now, deactivate it and let the Quest Leader know that they need to confirm the quest.
      // if ($quest_ready) $quest->active = false;
    }
    // Add the kit to the quest.
    if (isset($kit)) $quest->kit_id = $kit->iid;
    $success = $quest->save();
    if ($success === false) {
      $this->respond('There was a problem saving the quest. Please talk to Paul.');
      return FALSE;
    }

    // Put the kit on hold.
    if (isset($kit)) $player->remove_item($kit, true);

    // If this is a multiplayer quest, remove the fame cost.
    if ($quest->multiplayer) {
      $player->fame -= Quest::MULTIPLAYER_FAME_COST;
      $success = $player->save();
      if ($success === false) {
        $this->respond('There was a problem paying the fame cost for the multi-guild Quest. Please talk to Paul.');
        return FALSE;
      }
    }

    // Get list of adventurer names.
    $name_count = count($names);
    $last_name = ($name_count > 1) ? ', and '.array_pop($names) : '';
    $names = implode(', ', $names).$last_name;

    // Queue the single player quest for completion.
    if ($quest->multiplayer == false) {
      $queue = $quest->queue( $duration );
      if (empty($queue)) {
        $this->respond('There was a problem adding the quest to the queue. Please talk to Paul.');
        return FALSE;
      }

      $this->respond($names.' embark'.($name_count == 1 ? 's' : '').' on the quest of _'.$quest->name.'_, returning in '.Display::get_duration($duration).'.');

      return TRUE;
    }
    else if ($quest->multiplayer) {
      // Get the leader of the quest.
      $season = Season::current();
      $leader = Guild::load(array('gid' => $quest->gid, 'season' => $season->sid));

      if (empty($leader)) {
        $this->respond('There was a problem sending the notification to the Quest Leader. Please talk to Paul.');
        return FALSE;
      }

      // Send out the final approval message to the Quest leader.
      if ($quest_ready) {        
        $approval_message = 'The following Multi-Guild Quest had a status update:';

        // If this player is not the leader, show the "wait for authorization" message.
        if ($player->gid != $leader->gid) {
          $this->respond($names.' '.($name_count == 1 ? 'is' : 'are').' waiting for the Quest Leader to authorize the team.');
        }
        // Change the approval message if this is the Quest Leader.
        else {
          $approval_message = $names.' '.($name_count == 1 ? 'is' : 'are').' waiting for you (the Quest Leader) to authorize the team.';
        }

        $attachment = new SlackAttachment ();
        $attachment->title = 'APPROVAL NEEDED';
        $attachment->text = 'Multi-Guild Quest named _'.$quest->name.'_ requires the final approval before departure. Please review the Quest to either approve or cancel by typing: `quest approve b'.$quest->qid.'`';
        $attachment->fallback = $attachment->title .' - '. $attachment->text;
        $attachment->color = SlackAttachment::COLOR_BLUE;

        // Notify the Leader.
        $this->respond($approval_message, RPGSession::PERSONAL, $leader, $attachment);
        return TRUE;
      }
      // Otherwise, just let the user know they registered for the multiplayer quest and more spots need to be filled.
      else {
        $open_spots = $quest->open_spots();
        $this->respond($names.' '.($name_count == 1 ? 'is' : 'are').' waiting for '.$open_spots.' more adventurer'.($open_spots > 1 ? 's' : '').' to join in on the quest of *'.$quest->name.'*.');
        // Also let the Quest Leader know that someone else registered.
        if ($player->gid != $leader->gid) {
          $attachment = new SlackAttachment ();
          $attachment->title = 'NEW MULTI-GUILD REGISTRATION';
          $attachment->text = $name_count.' new Adventurer'.($name_count == 1 ? '' : 's').' registered for the Multi-Guild Quest named _'.$quest->name.'_, but there are still '.$open_spots.' open spot'.($open_spots > 1 ? 's' : '').'.';
          $attachment->fallback = $attachment->text;
          $attachment->color = SlackAttachment::COLOR_BLUE;
          $this->respond($approval_message, RPGSession::PERSONAL, $leader, $attachment);
        }
        return TRUE;
      }
    }
  }


  /**
   * Show a list of Quests that the Guild is participating in.
   */
  protected function cmd_quest_underway ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'quest underway';
    $response = array();

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    // Load up all Global Quests.
    $quests = Quest::load_multiple(array('multiplayer' => true));
    // Show quests currently on.
    $response[] = '*Multi-Guild Quests underway*:';
    $quest_underway = array();
    $quest_need_approval = array();
    foreach ($quests as $quest) {
      // Only show Quests that this Guild has participated in.
      if ($quest->is_registered_guild($player) == FALSE) continue;
      // Skip quests that need approval.
      if ($quest->needs_approval()) {
        $quest_need_approval[] = $quest;
        continue;
      }
      
      $quest_underway[] = $quest;
      $queue = $quest->get_queue();
      if (!empty($queue)) {
        $eta = $queue->execute - time();
        $response[] = '`b'.$quest->qid.'` _'. $quest->name .' (returning '.($eta > 0 ? 'in '.Display::get_duration($eta) : 'now').')_';
      }
      else {
        $response[] = '`b'.$quest->qid.'` _'. $quest->name .' (waiting for more Guilds)_';
      }
    }
    if (empty($quest_underway)) $response[] = '_None_';



    // Load up any multiplayer Quests that this player is leading.
    $response[] = '';
    $response[] = '*Multi-Guild Quests that need approval*:';
    foreach ($quest_need_approval as $quest) {
      $quest_bonus = $quest->get_bonus();
      $adventurers = $quest->get_registered_adventurers();
      $adv_count = count($adventurers);
      $success_rate = $quest->get_success_rate($quest_bonus, $adventurers);
      $response[] = '`b'.$quest->qid.'` _'. $quest->get_display_name(false) .'_ — '. Display::get_difficulty_stars($quest->stars, $success_rate) . ($quest->death_rate > 0 ? ' — :skull:' : ''). ' — '. Display::show_adventurer_count($quest->party_size_max, $adv_count);
    }
    if (empty($quest_need_approval)) $response[] = '_None_';


    // Load up all private Quests.
    $private_quests = $player->get_quests();
    $response[] = '';
    $response[] = '*Quests underway*:';
    $private_underway_quests = array();
    foreach ($private_quests as $quest) {
      if ($quest->active || empty($quest->agid)) continue;
      $private_underway_quests[] = $quest;
      $pqueue = $quest->get_queue();
      $eta = (!empty($pqueue) ? $pqueue->execute - time() : 0);
      $response[] = '`q'.$quest->qid.'` _'.$quest->get_display_name(false).' (returning '.($eta > 0 ? 'in '.Display::get_duration($eta) : 'now').')_';
    }
    if (empty($private_underway_quests)) $response[] = '_None_';

    $this->respond($response);
  }


  /**
   * Allow Guilds to dismiss a Quest and remove it from their Quest list.
   */
  protected function cmd_quest_dismiss ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'quest dismiss';
    $response = array();

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    // Info must be submitted to properly dismiss.
    if (empty($args) || empty($args[0])) {
      $response[] = 'You must select a Quest to dismiss from your list by typing: `quest dismiss [QUEST ID]` (example: `quest dismiss q12`)';
      $response[] = '';
      $response[] = '*Quest available to dismiss*:';

      // Load up all private Quests.
      $private_quests = $player->get_quests();
      $private_active_quests = array();
      foreach ($private_quests as $quest) {
        if ($quest->active == false || !empty($quest->agid)) continue;
        $private_active_quests[] = $quest;

        // Get the best adventurers available for questing.
        $best_adventurers = $player->get_best_adventurers($quest->party_size_max);
        $best_bonus = Quest::make_bonus($player, $best_adventurers);
        $success_rate = $quest->get_success_rate($best_bonus, $best_adventurers);
        $response[] = '`q'.$quest->qid.'` _'. $quest->get_display_name(false) .'_ — '. Display::get_difficulty_stars($quest->stars, $success_rate) . ($quest->death_rate > 0 ? ' — :skull:' : ''). ' — '. Display::show_adventurer_count($quest->get_party_size());
      }
      if (empty($private_active_quests)) $response[] = '_None_';

      $this->respond($response);
      return FALSE;
    }

    // Check if Quest ID is available for removal.
    $qarg = array_shift($args);
    $qtype = substr($qarg, 0, 1);
    $qid = substr($qarg, 1);
    if ($qtype == 'q') $quest = Quest::load(array('qid' => $qid, 'gid' => $player->gid, 'agid' => 0, 'completed' => false, 'active' => true, 'multiplayer' => false));

    if (!isset($quest) || empty($quest)) {
      $this->respond("This quest does not exist or cannot be dismissed.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    $best_adventurers = $player->get_best_adventurers($quest->party_size_max);
    $best_bonus = Quest::make_bonus($player, $best_adventurers);
    $success_rate = $quest->get_success_rate($best_bonus, $best_adventurers);
    $duration = $quest->get_duration($best_bonus);
    $death_rate = $quest->get_death_rate($best_bonus);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = 'The following Quest will be *DISMISSED* from your Quest list:';
      $response[] = '';
      $response[] = '*Quest*: '.$quest->get_display_name(false);
      $response[] = '*Rating*: '.Display::get_difficulty_stars($quest->stars, $success_rate);
      $response[] = '*Duration*: '.Display::get_duration($duration);
      $response[] = '*Chance of Success*: '.Display::get_difficulty($success_rate).' '.$success_rate.'%';
      if ($death_rate > 0) $response[] = '*Chance of Death*: :skull: '.$death_rate.'%';

      $response[] = '';
      $response[] = 'Are you sure you want to *dismiss* this Quest?';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Delete the quest.
    $success = $quest->delete();
    if ($success === false) {
      $this->respond('There was a problem removing the Quest. Please talk to Paul.');
      return FALSE;
    }

    // Recycle the Quest name(s) if necessary.
    $success = Quest::recycle_quest($quest);
    if ($success === false) {
      $this->respond('There was a problem recycling the Quest information. Please talk to Paul.');
      return FALSE;
    }

    $response[] = '_'.$quest->get_display_name(false).'_ was dismissed and removed from your Quest list.';
    $this->respond($response);
  }



  /**
   * Approve multi-guild quests.
   */
  protected function cmd_quest_approve ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'quest approve';
    $response = array();

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    // Info must be submitted to properly approve.
    if (empty($args) || empty($args[0])) {
      $response[] = '*Quests that need to be approved*:';

      // Load up any multiplayer Quests that this player is leading.
      $quests = Quest::load_multiple(array('gid' => $player->gid, 'active' => true, 'multiplayer' => true, 'completed' => false));
      $ready_quests = array();
      foreach ($quests as $quest) {
        if ($quest->is_ready() == false) continue;
        $ready_quests[] = $quest;

        $quest_bonus = $quest->get_bonus();
        $adventurers = $quest->get_registered_adventurers();
        $adv_count = count($adventurers);
        $success_rate = $quest->get_success_rate($quest_bonus, $adventurers);
        $response[] = '`b'.$quest->qid.'` _'. $quest->get_display_name(false) .'_ — '. Display::get_difficulty_stars($quest->stars, $success_rate) . ($quest->death_rate > 0 ? ' — :skull:' : ''). ' — '. Display::show_adventurer_count($quest->party_size_max, $adv_count);
      }
      if (empty($ready_quests)) $response[] = '_None_';

      $this->respond($response);
      return FALSE;
    }

    // Check if Quest ID is available.
    $qarg = array_shift($args);
    $qtype = substr($qarg, 0, 1);
    $qid = substr($qarg, 1);
    if ($qtype == 'b') $quest = Quest::load(array('qid' => $qid, 'active' => true, 'multiplayer' => true, 'completed' => false));

    if (!isset($quest) || empty($quest)) {
      $this->respond("This quest is not available or does not exist.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    $quest_bonus = $quest->get_bonus();
    $adventurers = $quest->get_registered_adventurers();
    $adv_count = count($adventurers);

    $success_rate = $quest->get_success_rate($quest_bonus, $adventurers);
    $duration = $quest->get_duration($quest_bonus);
    $death_rate = $quest->get_death_rate($quest_bonus);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = '*Multi-Guild Quest approvals*';
      $response[] = '';
      $response[] = '*Quest*: '.$quest->get_display_name(false);
      $response[] = '*Rating*: '.Display::get_difficulty_stars($quest->stars, $success_rate);
      $response[] = '*Duration*: '.Display::get_duration($duration);
      $response[] = '*Chance of Success*: '.Display::get_difficulty($success_rate).' '.$success_rate.'%';
      if ($death_rate > 0) $response[] = '*Chance of Death*: :skull: '.$death_rate.'%';

      if (isset($kit)) $response[] = '*Modifier Item*: '. $kit->get_display_name(false);
      
      $response[] = '*Adventuring party ('.count($adventurers).')*:';
      foreach ($adventurers as $adventurer) $response[] = $adventurer->get_display_name(false, true, true, true, true, true);
      $response[] = '';

      $response[] = 'To cancel this Quest instead, type: `quest cancel b'.$quest->qid.'`';
      $response[] = '';

      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Set quest to active.
    $quest->active = false;
    $success = $quest->save();
    if (empty($success)) {
      $this->respond('There was a problem saving the multiplayer quest. Please talk to Paul.');
      return FALSE;
    }

    // Update all the adventuring groups ETAs with the final values.
    $groups = $quest->get_registered_adventuring_groups();
    foreach ($groups as $group) {
      $group->task_eta = $duration;
      $success = $group->save();
      if (empty($success)) {
        $this->respond('There was a problem saving an Adventuring Group\'s new ETA on approval. Please talk to Paul.');
        return FALSE;
      }
    }

    // Queue up the Quest.
    $queue = $quest->queue( $duration );
    if (empty($queue)) {
      $this->respond('There was a problem adding the multiplayer quest to the queue. Please talk to Paul.');
      return FALSE;
    }

    // Send out a special multiplayer message.
    $rguilds = $quest->get_registered_guilds();
    $count_guilds = count($rguilds);

    $attachment = new SlackAttachment ();
    $attachment->title = 'Multi-Guild '.ucwords($quest->type).' Quest:';
    $attachment->text = $count_guilds.' Guild'.($count_guilds == 1 ? '' : 's').' embarked on the quest of _'.$quest->get_display_name(false).'_!';
    $attachment->fallback = $attachment->text;
    $attachment->color = SlackAttachment::COLOR_BLUE;

    $this->respond(null, RPGSession::CHANNEL, null, $attachment);

    // Send a message out to every other Guild telling them they are leaving.
    foreach ($rguilds as $rguild) {
      // Get the adventurers they are sending out.
      $rgroup = AdventuringGroup::load(array('gid' => $rguild->gid, 'task_id' => $quest->qid, 'task_type' => 'Quest'));
      if (empty($rgroup)) continue;
      $radventurers = $rgroup->get_adventurers();
      if (empty($radventurers)) continue;
      // Get the names of the adventurers.
      $names = array();
      foreach ($radventurers as $radv) $names[] = $radv->get_display_name();
      $name_count = count($names);
      $last_name = ($name_count > 1) ? ', and '.array_pop($names) : '';
      $names = implode(', ', $names).$last_name;
      // Message the user to let them know their adventurers are leaving.
      $this->respond($names.' embark'.($name_count == 1 ? 's' : '').' on the quest of _'.$quest->name.'_ returning in '.Display::get_duration($duration).'.', RPGSession::PERSONAL, $rguild);
    }
  }



  /**
   * Cancel multi-guild quests.
   */
  protected function cmd_quest_cancel ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'quest cancel';
    $response = array();

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    // Info must be submitted to properly cancel.
    if (empty($args) || empty($args[0])) {
      $response[] = '*Quests that can to be canceled*:';

      // Load up any multiplayer Quests that this player is leading.
      $quests = Quest::load_multiple(array('gid' => $player->gid, 'active' => true, 'multiplayer' => true, 'completed' => false));
      $ready_quests = array();
      foreach ($quests as $quest) {
        if ($quest->is_ready() == false) continue;
        $ready_quests[] = $quest;

        $quest_bonus = $quest->get_bonus();
        $adventurers = $quest->get_registered_adventurers();
        $adv_count = count($adventurers);
        $success_rate = $quest->get_success_rate($quest_bonus, $adventurers);
        $response[] = '`b'.$quest->qid.'` _'. $quest->get_display_name(false) .'_ — '. Display::get_difficulty_stars($quest->stars, $success_rate) . ($quest->death_rate > 0 ? ' — :skull:' : ''). ' — '. Display::show_adventurer_count($quest->party_size_max, $adv_count);
      }
      if (empty($ready_quests)) $response[] = '_None_';

      $this->respond($response);
      return FALSE;
    }

    // Check if Quest ID is available.
    $qarg = array_shift($args);
    $qtype = substr($qarg, 0, 1);
    $qid = substr($qarg, 1);
    if ($qtype == 'b') $quest = Quest::load(array('qid' => $qid, 'active' => true, 'multiplayer' => true, 'completed' => false));

    if (!isset($quest) || empty($quest)) {
      $this->respond("This quest is not available or does not exist.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    $quest_bonus = $quest->get_bonus();
    $adventurers = $quest->get_registered_adventurers();
    $adv_count = count($adventurers);

    $success_rate = $quest->get_success_rate($quest_bonus, $adventurers);
    $duration = $quest->get_duration($quest_bonus);
    $death_rate = $quest->get_death_rate($quest_bonus);

    $reimburse_amount = ceil(Quest::MULTIPLAYER_FAME_COST / 2);
    $leader_cancel_cost = Quest::MULTIPLAYER_FAME_COST;

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = 'The following Quest will be *CANCELED*:';
      $response[] = '';
      $response[] = '*Quest*: '.$quest->get_display_name(false);
      $response[] = '*Rating*: '.Display::get_difficulty_stars($quest->stars, $success_rate);
      $response[] = '*Duration*: '.Display::get_duration($duration);
      $response[] = '*Chance of Success*: '.Display::get_difficulty($success_rate).' '.$success_rate.'%';
      if ($death_rate > 0) $response[] = '*Chance of Death*: :skull: '.$death_rate.'%';

      if (isset($kit)) $response[] = '*Modifier Item*: '. $kit->get_display_name(false);
      
      $response[] = '*Adventuring party ('.count($adventurers).')*:';
      foreach ($adventurers as $adventurer) $response[] = $adventurer->get_display_name(false);
      $response[] = '';

      $response[] = 'Are you sure you wish to *CANCEL* this Quest?';
      $response[] = 'As the Leader, you will lose your '.Display::get_fame($leader_cancel_cost).' registration fee if you cancel. All other participating Guilds will be reimbursed '. Display::get_fame($reimburse_amount) .' (half of the fee) if the Quest is canceled.';
      $response[] = '';
      $response[] = 'To depart on this Quest instead, type: `quest approve b'.$quest->qid.'`';
      $response[] = '';

      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Start the cancelation process.
    $guilds = $quest->get_registered_guilds();
    $adventurers = $quest->get_registered_adventurers();
    $groups = $quest->get_registered_adventuring_groups();
    $leader = null;
    $kit_guild = null;
    $kit = !empty($quest->kit_id) ? $quest->get_kit() : null;

    // Reimburse all other Guilds.
    foreach ($guilds as $guild) {
      // Find who (if anyone) contributed the kit.
      if (!empty($kit) && $guild->gid == $kit->gid) $kit_guild = $guild;
      // Skip the leader for reimbursement.
      if ($guild->gid == $quest->gid) {
        $leader = $guild;
        continue;
      }
      // Reimburse.
      $guild->fame += $reimburse_amount;
      $guild->save();
    }

    // Return all Adventurers to their Guilds.
    foreach ($adventurers as $adventurer) {
      $adventurer->agid = 0;
      $adventurer->save();
    }

    // Remove the groups.
    foreach ($groups as $group) {
      $group->delete();
    }

    // Return the kit.
    if (!empty($kit)) {
      if (empty($kit_guild)) $kit_guild = Guild::load(array('gid' => $kit->gid));
      // If we have the Guild, return the item to them.
      if (!empty($kit_guild)) {
        $kit_guild->return_item($kit);
      }
    }

    // Remove the Quest Leader information.
    $quest->gid = 0;
    $quest->agid = 0;
    $quest->kit_id = 0;
    $quest->active = true;
    $success = $quest->save();
    if (empty($success)) {
      $this->respond('There was a problem saving the multiplayer quest. Please talk to Paul.');
      return FALSE;
    }

    // // Send out a special multiplayer message.
    $count_guilds = count($guilds);

    $attachment = new SlackAttachment ();
    $attachment->text = $leader->get_display_name(). ' canceled the quest _'.$quest->get_display_name().'_.';
    $attachment->fallback = $attachment->text;
    $attachment->color = SlackAttachment::COLOR_RED;

    $this->respond('Multi-Guild '.ucwords($quest->type).' Quest:', RPGSession::CHANNEL, null, $attachment);

    // // Send a message out to every other Guild telling them they are leaving.
    foreach ($guilds as $guild) {
      if ($guild->gid == $leader->gid) continue;
      // Message the user to let them know their adventurers are leaving.
      $this->respond($leader->get_display_name().' canceled the quest _'.$quest->get_display_name().'_. You have been reimbursed '.Display::get_fame($reimburse_amount).' and your Adventurers are available again.', RPGSession::PERSONAL, $guild);
    }
  }



  /**
   * View the map.
   */
  protected function cmd_map ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();
    
    if (empty($args) || empty($args[0])) {
      $response[] = 'To explore a location on the map, type: `explore`';
      $response[] = 'To see a list of all location names and their coordinates, type: `map list`';

      $attachment = $this->get_map_attachment();
      $this->respond($response, RPGSession::PERSONAL, null, $attachment);
      return TRUE;
    }

    if (strtolower($args[0]) == 'list') {
      $season = Season::current();
      $map = $season->get_map();

      $response[] = 'List of all discovered locations:';
      // Get list of all revealed locations.
      $types = Location::types();
      $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
      foreach ($locations as $location) {
        $response[] = $location->get_display_name();
      }
      if (empty($locations)) $response[] = '_None_';

      $this->respond($response);
    }
  }



  /**
   * Go explore the map.
   */
  protected function cmd_explore ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'explore';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    // Load the Map.
    $season = Season::current();
    $map = Map::load(array('season' => $season->sid));

    // If there's no coordinates entered, show the map.
    if (empty($args) || empty($args[0])) {
      $response = array();
      $response[] = 'To explore a location on the map, type: `explore [LETTER][NUMBER] [MODIFIER ITEM ID (optional)] [ADVENTURER NAMES (comma-separated)]`';
      $response[] = '_*Note:* Black tiles cannot be explored until an adjacent tile is explored._';

      // Also show the list of available adventurers.
      $response[] = '';
      $response[] = '*Adventurers available for exploring*:';
      $response[] = $this->show_available_adventurers($player);
      $response[] = '';

      // Also show the list of available item modifiers.
      $response[] = '';
      $response[] = '*Modifier Items*:';
      // Compact same-name items.
      $items = $player->get_items();
      $compact_items = $this->compact_items($items);
      foreach ($compact_items as $citemid => $citems) {
        $count = count($citems);
        if ($count <= 0) continue;
        if ($citems[0]->type != ItemType::KIT) continue;
        $kits[] = $citems[0];
        $response[] = '`i'.$citems[0]->iid.'` '. ($count > 1 ? $count.'x ' : ''). $citems[0]->get_display_name(false);
      }
      if (empty($kits)) $response[] = '_None_';

      $response[] = '';
      $response[] = 'See the map below for coordinates:';

      $attachment = $this->get_map_attachment();
    
      $this->respond($response, RPGSession::PERSONAL, null, $attachment);
      return true;
    }

    // Get the coordinates: ex. A4.
    $coord = strtoupper(array_shift($args));
    // Regex the line to scrub out the letters.
    $row = preg_replace('/[^0-9]/', '', $coord);
    $col = preg_replace('/[^a-zA-Z]/', '', $coord);
    if (empty($row) || empty($col)) {
      $this->respond('Please enter the coordinates without any spaces. Example: `explore A4 [ADVENTURER NAMES (comma-separated)]`');
      return false;
    }

    // Load the Location.
    $location = Location::load(array(
      'row' => $row,
      'col' => Location::get_number($col),
      'mapid' => $map->mapid,
    ));

    // Check if the Location exists or can actually be explored.
    if (empty($location) || $location->open == false) {
      $response = array();
      $response[] = 'Location '.$coord.' cannot be explored until an adjacent tile has been explored.';
      $response[] = '';
      $response[] = 'See the map below for coordinates (hint: choose non-black tiles):';
      $attachment = $this->get_map_attachment();
      $this->respond($response, RPGSession::PERSONAL, null, $attachment);
      return;
    }

    // Check if the Location is already revealed.
    if ($location->revealed) {
      // Load up the Guild that revealed this location.
      $guild = Guild::load(array('gid' => $location->gid, 'season' => $season->sid));
      $revealed_text = empty($guild) ? '' : ' by '.$guild->get_display_name();
      if ($guild->gid == $player->gid) $revealed_text = ' by you';
      $this->respond('Location '.$coord.' was already explored'.$revealed_text.'.');
      $response[] = '';
      $response[] = 'See the map below for coordinates (hint: choose non-black tiles):';
      $attachment = $this->get_map_attachment();
      $this->respond($response, RPGSession::PERSONAL, null, $attachment);
      return false;
    }

    // Check if there is an optional modifier item ID available.
    if (!empty($args) && substr($args[0], 0, 1) == 'i') {
      $iid = substr(array_shift($args), 1);
      $kit = Item::load(array('iid' => $iid, 'gid' => $player->gid, 'type' => ItemType::KIT, 'on_hold' => false));
      if (empty($kit)) {
        $this->respond("This item is not available to use as a modifier. Please choose a different item or remove it.".$this->get_typed($cmd_word, $orig_args));
        return FALSE;
      }
    }

    // Get the adventurers going.
    if (empty($args)) {
      $response = array();
      $response[] = 'Please choose Adventurers to go exploring.';
      // Also show the list of available adventurers.
      $response[] = '';
      $response[] = '*Adventurers available for exploring*:';
      $response[] = $this->show_available_adventurers($player);
      $response[] = '';
      $response[] = $this->get_typed($cmd_word, $orig_args);
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
      $this->respond($success['msg']);
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
    $explore_bonus = Quest::make_bonus($player, $adventurers, (isset($kit) ? $kit : NULL));
    $duration = $location->get_duration($explore_bonus);

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = '*Quest*: Exploration';
      if (isset($kit)) $response[] = '*Modifier Item*: '. $kit->get_display_name(false);
      $response[] = '*Duration*: '.Display::get_duration($duration);
      $response[] = '*Adventuring party ('.count($adventurers).')*:';
      foreach ($adventurers as $adventurer) {
        $response[] = $adventurer->get_display_name(false);
      }
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Create the exploration "quest".
    $quest_data = array(
      'gid' => $player->gid,
      'locid' => $location->locid,
      'type' => Quest::TYPE_EXPLORE,
      'name' => 'Explore '.$location->get_coord_name(),
      'icon' => ':mag_right:',
      'created' => time(),
      'active' => false,
      'permanent' => false,
      'reward_gold' => 0,
      'reward_exp' => $location->get_exploration_exp() * count($adventurers),
      'reward_fame' => 0,
      'duration' => 0,
      'cooldown' => 0,
      'min_party_size' => 2,
      'max_party_size' => 0,
      'success_rate' => 100,
      'death_rate' => 0,
      'kit' => (isset($kit) ? $kit->iid : 0),
    );

    // Bonus reward if you discover a non-empty location.
    if ($location->type != Location::TYPE_EMPTY) {
      $quest_data['reward_fame'] += $location->star_max * 3;
      $quest_data['reward_exp'] += $location->star_max * rand(10, 15);
    }

    $quest = new Quest ($quest_data);
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

    // Mark the Kit as on hold.
    if (isset($kit) && !empty($kit)) $player->remove_item($kit, true);

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

    $this->respond($names.' set'.($name_count == 1 ? 's' : '').' off to explore '.$location->get_coord_name().' returning in '.Display::get_duration($duration).'.');
  }



  /**
   * Select your champion.
   */
  protected function cmd_champion ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
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
    $champion = Adventurer::load(array('name' => $name, 'gid' => $player->gid, 'dead' => false), true);
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
    if (!($player = $this->load_current_player())) return;

    $orig_max = 10;
    $max = $orig_max;
    // Set the max based on the argument provided by user.
    if (!empty($args) && !empty($args[0])) {
      $max = (strtolower($args[0]) == 'all') ? 0 : (int)$args[0];
    }

    $response = array();
    $response[] = ($max > 0 ? 'Top '.$max.' ' : '') .'Guild Ranking:';

    // Load all Guilds.
    $season = Season::current();
    $guilds = Guild::load_multiple(array('season' => $season->sid));
    if (empty($guilds)) {
      $this->respond('There are no Guilds? Go talk to Paul because that seems like an error.');
      return FALSE;
    }

    // Sort Guilds by fame.
    usort($guilds, array('Guild','sort'));

    $count = 0;
    foreach ($guilds as $guild) {
      $count++;
      $response[] = Display::addOrdinalNumberSuffix($count).' — ('.Display::get_fame($guild->get_total_points()).') '.$guild->get_display_name().' — @'.$guild->username;
      if ($count == $max) break;
    }

    if ($max == $orig_max) {
      $response[] = '';
      $response[] = 'To view all Guilds, type: `leader all`.';
    }

    $this->respond($response);
  }



  /**
   * Edit your Guild information.
   */
  protected function cmd_edit ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
    $response = array();

    if (empty($args) || empty($args[0])) {
      $response[] = 'You may edit the following information:';
      $response[] = '- Guild emoji: `edit icon [ICON]`';

      $this->respond($response);
      return FALSE;
    }

    if ($args[0] == 'icon') {
      if (!isset($args[1])) {
        $this->respond('You must include the new emoji icon alias (example: `edit icon :skull:`).');
        return FALSE;
      }

      $icon = $args[1];
      if (strpos($icon, ':') !== 0 || strrpos($icon, ':') !== strlen($icon)-1) {
        $this->respond('You must include a valid emoji icon alias (example: `edit icon :skull:`).');
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
    if (!($player = $this->load_current_player())) return;

    // If there are no arguments, list the upgrades.
    if (empty($args) || empty($args[0])) {
      $response = array();
      $response[] = 'You can purchase the following upgrades:';
      $response[] = '';

      $upgrades = $player->get_available_upgrades();
      foreach ($upgrades as $upgrade) {
        $required_items = $upgrade->get_requires();
        $item_names = array();
        $upgrade_names = array();
        foreach ($required_items as $item_requirement) {
          if ($item_requirement->type == Requirement::TYPE_ITEM) {
            $ritem = ItemTemplate::load(array('name_id' => $item_requirement->name_id));
            if (empty($ritem)) continue;
            $item_names[] = ($item_requirement->qty > 1 ? $item_requirement->qty.'x ' : '') . $ritem->get_display_name(false);
          }
          else if ($item_requirement->type == Requirement::TYPE_UPGRADE) {
            $rupgrade = Upgrade::load(array('name_id' => $item_requirement->name_id));
            if (empty($rupgrade)) continue;
            $upgrade_names[] = $rupgrade->get_display_name(false, false);
          }
        }
        $response[] = $upgrade->get_display_name() .' `upgrade '.$upgrade->name_id.'` — '. Display::get_currency($upgrade->cost)
          .(count($item_names) > 0 ? ' — ' . implode(', ', $item_names) : '')
          .(count($upgrade_names) > 0 ? ' — (requires: ' . implode(', ', $upgrade_names) .')' : '');
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

    $response = array();

    // Check that they meet the requirements.
    if (!$player->meets_requirement($upgrade)) {
      $response[] = 'You do not meet the requirements of '.$upgrade->get_display_name(false).'.';
      $response[] = '';
      $response[] = $this->show_upgrade($upgrade);
      $this->respond($response);
      return FALSE;
    }

    // See if they have the required items.
    $items = $player->has_required_items($upgrade);
    if ($items === FALSE) {
      $response[] = 'You do not have all of the required items to upgrade to '.$upgrade->get_display_name(false).'.';
      $response[] = '';
      $response[] = $this->show_upgrade($upgrade);
      $this->respond($response);
      return FALSE;
    }

    // Try to purchase the upgrade.
    if ($player->gold < $upgrade->cost) {
      $response[] = 'You do not have enough gold to purchase the upgrade '.$upgrade->get_display_name(false).'.';
      $response[] = '';
      $response[] = $this->show_upgrade($upgrade);
      $this->respond($response);
      return FALSE;
    }

    // Check that they confirmed the upgrade.
    $confirm = array_pop($args);
    if ($confirm != 'CONFIRM') {
      $response[] = $this->show_upgrade($upgrade);
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return TRUE;
    }

    // Remove the items they used to purchase the upgrade.
    foreach ($items as $item) {
      $player->remove_item($item);
      $item->delete();
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

    $this->respond($upgrade->get_display_name(false) .' was purchased and will be upgraded in '.Display::get_duration($duration).'.');
  }



  /**
   * See your inventory of items.
   */
  protected function cmd_inventory ($args = array()) {
    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
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
    $response[] = $this->show_item_information($item);

    $this->respond($response);
  }

  /**
   * Power up an Adventurer.
   */
  protected function cmd_powerup ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'powerup';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
    $response = array();
    
    // You must specify class name and then the adventurer name.
    if (empty($args) || empty($args[0])) {
      $response[] = 'You must specify the adventurer name and then the class name. Example: `powerup Morgan LeClair Shaman`';
      $response[] = '';

      // Show class powerstones.
      $items = $player->get_items();
      $powerstones = array();
      foreach ($items as $item) {
        if ($item->type != ItemType::POWERSTONE) continue;
        if (isset($powerstones[$item->name_id])) continue;
        $powerstones[$item->name_id] = $item;
      }

      $response[] = '*Powerstones available to use*:';
      foreach ($powerstones as $powerstone) {
        $response[] = $powerstone->get_display_name(false);
      }
      if (empty(($powerstones))) $response[] = '_None_';
      
      // Show adventurers.
      $response[] = '';
      $response[] = '*Adventurers available for a Power Up*:';
      $adventurers = $player->get_adventurers();
      foreach ($adventurers as $adventurer) {
        // Skip adventurers that already have a class.
        if ($adventurer->has_adventurer_class()) continue;
        // Skip adventurers that are out adventuring.
        if (!empty($adventurer->agid)) continue;
        $response[] = $adventurer->get_display_name(false);
      }
      if (empty(($adventurers))) $response[] = '_None_';

      $this->respond($response);
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Get the class name.
    $class_name = array_pop($args);
    $adventurer_class = AdventurerClass::load(array('name_id' => $class_name), true);
    if (empty($adventurer_class)) {
      $response[] = 'Please specify a valid class name. Example: `powerup Morgan LeClair Shaman`';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // If they didn't specify an adventurer name, error out.
    if (empty($args) || empty($args[0])) {
      $response[] = 'Please specify an adventurer to Power Up. Example: `powerup Morgan LeClair Shaman`';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Get the adventurer name.
    $adventurer_name = implode(' ', $args);
    $adventurer = Adventurer::load(array('gid' => $player->gid, 'name' => $adventurer_name, 'dead' => false), true);
    if (empty($adventurer)) {
      $response[] = 'Please specify a valid adventurer name. Example: `powerup Morgan LeClair Shaman`';
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

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = "Are you sure you want to power up ".$adventurer->get_display_name()."?";
      $response[] = '';
      $response[] = $this->show_adventurer_status($adventurer);
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Power up the Adventurer.
    $adventurer->set_adventurer_class($adventurer_class);
    // $adventurer->icon = ':rpg-adv-'.$adventurer->gender.'-'.$adventurer_class->name_id.':';
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



  /**
   * Challenge another Guild to a fight in the Colosseum.
   */
  protected function cmd_challenge ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'challenge';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();

    // If there are no arguments, show the help.
    if (empty($args) || empty($args[0])) {
      // Show any challenges pending.
      $your_challenges = Challenge::load_multiple(array('challenger_id' => $player->gid, 'confirmed' => false, 'winner' => 0));
      if (!empty($your_challenges)) {
        $response[] = '*Your Challenges*:';
        foreach ($your_challenges as $challenge) {
          $response[] = $challenge->get_opponent()->get_display_name() .' (you wagered '. Display::get_fame($challenge->wager) .')';
        }
        $response[] = '';
      }

      // Show any challenges requested by others.
      $other_challenges = Challenge::load_multiple(array('opponent_id' => $player->gid, 'confirmed' => false, 'winner' => 0));
      if (!empty($other_challenges)) {
        $response[] = '*Guilds Challenging You*:';
        foreach ($other_challenges as $challenge) {
          $response[] = $challenge->get_challenger()->get_display_name() .' wagered '. Display::get_fame($challenge->wager);
        }
        $response[] = '';
      }

      // Show help message.
      $response[] = 'For more information about the Colosseum, type: `challenge help`';
      $response[] = 'To challenge another Guild, type: `challenge [FAME WAGER] [GUILD NAME] [6 CHALLENGE MOVES (comma-separated)]`';
      $response[] = 'To cancel challenges you started, type: `challenge cancel`';
      $response[] = '';
      $response[] = 'Moves:';
      $response[] = '`attack` (or `a`) Attack wins against Break, but loses against Defend.';
      $response[] = '`defend` (or `d`) Defend wins against Attack, but loses against Break.';
      $response[] = '`break` (or `b`) Break wins against Defend, but loses against Attack.';
      $this->respond($response);
      return TRUE;
    }

    // Check if the first argument is a secondary command, pass all of the information off to the secondary functions.
    $cmd_options = array('help', 'cancel');
    if (in_array($args[0], $cmd_options)) {
      $cmd_secondary = array_shift($args);
      $this->{'cmd_challenge_'.$cmd_secondary}($args);
      return TRUE;
    }

    // Confirm that they have chosen a Champion before challenging.
    $champion = $player->get_champion();
    if (empty($champion)) {
      $this->respond("Please choose your Guild's Champion before challenging another Guild. Type `champion` to select one.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Make sure the champion is not already away.
    if (!empty($champion->agid)) {
      $this->respond("Your Guild's Champion ".$champion->get_display_name()." is currently busy. Please wait for ".$champion->get_other_pronoun()." to return from ".$champion->get_possessive_pronoun()." current engagement.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Get the wager.
    $wager = intval(array_shift($args));
    if ($wager <= 0) {
      $this->respond("Please choose an amount of Fame to wager.\n(Example: `challenge 15 The Aristocats attack,attack,defend,break,attack,defend`)".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check that they can afford the wager of Fame.
    if ($player->fame < $wager) {
      $this->respond("You do not have ".Display::get_fame($wager)." to wager.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Recompile the arguments to find the name (might be space-separated) and moves.
    $orig_string = implode(' ', $args);
    $name = '';
    $moves = '';

    // Remove every thing after the first comma.
    $comma = strpos($orig_string, ',');
    if ($comma !== false) {
      $moves = substr($orig_string, $comma);
      $name = substr($orig_string, 0, $comma);

      // Remove the last space-separated word (as it is the first move command).
      $space = strrpos($name, ' ');
      if ($space !== false) {
        $moves = substr($name, $space) . $moves;
        $name = substr($name, 0, $space);
      }
    }

    // Trim the name and moves list.
    $name = trim($name);
    $moves = trim($moves);

    // Get the name of the Guild they want to challenge.
    $season = Season::current();
    $opponent = Guild::load(array('name' => $name, 'season' => $season->sid), true);
    if (empty($opponent)) {
      $this->respond("Your opponent \"".$name."\" does not exist.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check if there is an existing Challenge between the two.
    $challenge = Challenge::load(array('challenger_id' => $player->gid, 'opponent_id' => $opponent->gid, 'confirmed' => false));
    // Confirm that they are not trying to re-challenge the same Guild.
    if (!empty($challenge) && $challenge->challenger_id == $player->gid) {
      $this->respond("You have already challenged ".$opponent->get_display_name().". Please choose a different Guild to challenge.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }
    // Check if there is an existing Challenge between the two.
    $challenge = Challenge::load(array('challenger_id' => $opponent->gid, 'opponent_id' => $player->gid, 'confirmed' => false));
    // Confirm the wager they typed equals the same amount.
    if (!empty($challenge) && $challenge->wager != $wager) {
      $this->respond("Your opponent wagered ".Display::get_fame($challenge->wager).". To accept their challenge, you need to match the wager and you only wagered ".Display::get_fame($wager).".".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check if the opponent can afford the wager.
    if (empty($challenge) && $opponent->fame < $wager) {
      $this->respond($opponent->get_display_name(). " does not have ".Display::get_fame($wager)." to match your wager. Try a lower amount or check their report to see how much they have (type `report ".$opponent->name."`).".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check if the moves are valid.
    $move_list = explode(',', $moves);
    foreach ($move_list as $key => &$move) {
      $move = strtolower(trim($move));
      // Convert short forms to long forms.
      if ($move == 'a') $move = Challenge::MOVE_ATTACK;
      else if ($move == 'd') $move = Challenge::MOVE_DEFEND;
      else if ($move == 'b') $move = Challenge::MOVE_BREAK;
      if (!Challenge::valid_move($move)) {
        $this->respond("The move \"".$move."\" is not valid. Please choose one of the following: `".implode('`,`', Challenge::moves())."`".$this->get_typed($cmd_word, $orig_args));
        return FALSE;
      }
    }

    // Count if there are enough moves.
    $num_moves = count($move_list);
    $move_limit = 6;
    if ($num_moves < $move_limit) {
      $this->respond("You are missing ".($num_moves == 5 ? 'the tie-breaker move' : ($move_limit - $num_moves).' moves').".".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }
    else if ($num_moves > $move_limit) {
      $this->respond("You have ".($num_moves - $move_limit)." too many moves.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = '*Opponent*: '.$opponent->get_display_name();
      $response[] = '*Wager*: '.Display::get_fame($wager);
      $response[] = '*Your Champion*: '.$champion->get_display_name();
      $response[] = '*Your Moves*: ';
      $mcount = 0;
      foreach ($move_list as $amove) {
        $mcount++;
        $response[] = '_'. ($mcount <= 5 ? Display::addOrdinalNumberSuffix($mcount) .': ' : 'Tie-breaker: '). ucwords($amove) .'_ ';
      }
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }


    // Delay before starting challenge in seconds.
    $duration = 10; // (60 * 30) = 30 minutes
    $new_challenge = empty($challenge);

    // If there's no existing challenge, create a new one.
    if ($new_challenge) {
      $challenge_data = array(
        'challenger_id' => $player->gid,
        'challenger_champ' => $champion->aid,
        'opponent_id' => $opponent->gid,
        'wager' => $wager,
        'confirmed' => false,
      );
      $challenge = new Challenge ($challenge_data);
      $challenge->set_challenger_moves($move_list);
      $challenge->save();
    }

    // Send the player's Champion off to prepare for battle.
    $ag_data = array(
      'gid' => $player->gid,
      'created' => time(),
      'task_id' => $challenge->chid,
      'task_type' => 'Challenge',
      'task_eta' => $duration,
      'completed' => false,
    );
    $advgroup = new AdventuringGroup ($ag_data);
    $success = $advgroup->save();
    if ($success === false) {
      $this->respond('There was a problem saving the adventuring group. Please talk to Paul.');
      return FALSE;
    }

    // Assign the Champion to the new group.
    $champion->agid = $advgroup->agid;
    $success = $champion->save();
    if ($success === false) {
      $this->respond('There was a problem saving your Champion to the adventuring group. Please talk to Paul.');
      return FALSE;
    }

    // Remove the fame from the person typing the command.
    $player->fame -= $wager;
    $success = $player->save();
    if ($success === false) {
      $this->respond('There was a problem saving your wagered amount for the challenge. Please talk to Paul.');
      return FALSE;
    }

    // If this is an existing Challenge, confirm it and queue it up.
    if (!$new_challenge) {
      $challenge->opponent_champ = $champion->aid;
      $challenge->set_opponent_moves($move_list);
      $challenge->reward = ($wager * 2);
      $challenge->confirmed = true;
      $challenge->save();

      // Create the queue.
      $queue = $challenge->queue( $duration );
      if (empty($queue)) {
        $this->respond('There was a problem adding the challenge to the queue. Please talk to Paul.');
        return FALSE;
      }

      // Notify everyone.
      $this->respond("You have accepted the Colosseum challenge from ".$opponent->get_display_name().". The fight will finish in ".Display::get_duration($duration).".");
      $this->respond($player->get_display_name()." has accepted your Colosseum challenge! The fight will finish in ".Display::get_duration($duration).".", RPGSession::PERSONAL, $opponent);
      return TRUE;
    }

    // Notify everyone.
    $this->respond("You just wagered ".Display::get_fame($wager)." that your Champion can beat ".$opponent->get_display_name()." in the Colosseum! Please wait for them to confirm.");
    $this->respond($player->get_display_name()." has wagered ".Display::get_fame($wager)." that their Champion can beat yours in a match in the Colosseum! Please confirm your Champion and match the wager to agree to this challenge (type: `challenge ".$wager." ".$player->name." [6 CHALLENGE MOVES (comma-separated)]`).", RPGSession::PERSONAL, $opponent);
  }


  /**
   * Show additional help for the Challenge command.
   */
  protected function cmd_challenge_help ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'challenge help';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();

    // For more help, show the help.
    $response[] = '*Colosseum Challenge*';
    $response[] = '';
    $response[] = 'Moves:';
    $response[] = '`attack` Attack wins against Break, but loses against Defend.';
    $response[] = '`defend` Defend wins against Attack, but loses against Break.';
    $response[] = '`break` Break wins against Defend, but loses against Attack.';
    $response[] = '';
    $response[] = '*Attack-Defend-Break-Miss-Crit*';
    $response[] = 'Challenging another Guild is very much like a Rock-Paper-Scissors match. Each Guild uses their Champion to fight against the other by typing in a list of actions ("moves") that are used sequentially in a 5-round battle. Each Guild chooses five (5) moves (one for each round) and one (1) tie-breaker move. The tie-breaker move is used if at the end of the 5th round, both Champions are tied in points.';
    $response[] = '';
    $response[] = 'Each round consists of both Champions using the chosen move. If both Champions choose the same move, the round is considered a tie and neither Champion receives points. If the Champions choose a different move, whichever move has higher priority wins and the Champion receives 1 point.';
    $response[] = '';
    $response[] = 'Where this differs from Rock-Paper-Scissors is that Champions also have a chance to Miss and Crit. If Champions vary in level, the lower-level Champion has an increased chance of Missing their successful move (receiving 0 points instead of 1) and the higher-level Champion has an increased chance of Criting their successful move (receiving 2 points instead of 1).';
    $response[] = '';
    $response[] = 'To challenge another Guild, type:';
    $response[] = '`challenge [FAME WAGER] [GUILD NAME] [6 CHALLENGE MOVES (comma-separated)]`';
    $response[] = 'Example: `challenge 15 The Aristocats attack,attack,defend,break,attack,defend`';
    $this->respond($response);
    return TRUE;
  }



  /**
   * Allow the challenger to cancel the Challenge at any time.
   */
  protected function cmd_challenge_cancel ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'challenge cancel';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();

    // If they didn't choose a challenge to cancel, show the list.
    if (empty($args) || empty($args[0])) {
      // Show any challenges pending.
      $your_challenges = Challenge::load_multiple(array('challenger_id' => $player->gid, 'confirmed' => false, 'winner' => 0));
      if (!empty($your_challenges)) {
        $response[] = '*Your Challenges*:';
        foreach ($your_challenges as $challenge) {
          $response[] = '`c'.$challenge->chid.'` '. $challenge->get_opponent()->get_display_name() .' (you wagered '. Display::get_fame($challenge->wager) .')';
        }
        if (empty($your_challenges)) $response[] = '_None_';
      }

      $response[] = '';
      $response[] = 'To cancel a challenge, type: `challenge cancel [CHALLENGE ID]` (example: `challenge cancel c12`)';
      $this->respond($response);
      return true;
    }

    // Load up the challenge.
    $carg = array_shift($args);
    $ctype = substr($carg, 0, 1);
    $chid = substr($carg, 1);
    if ($ctype == 'c') $challenge = Challenge::load(array('chid' => $chid, 'challenger_id' => $player->gid, 'confirmed' => false, 'winner' => 0));

    if (!isset($challenge) || empty($challenge)) {
      $this->respond("This challenge is not yours or does not exist.".$this->get_typed($cmd_word, $orig_args));
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    $opponent = $challenge->get_opponent();
    $wager = $challenge->wager;
    $champion = $challenge->get_challenger_champ();
    $move_list = $challenge->get_challenger_moves();

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = 'The following Colosseum challenge will be *cancelled*:';
      $response[] = '*Opponent*: '.$opponent->get_display_name();
      $response[] = '*Wager*: '.Display::get_fame($wager);
      $response[] = '*Your Champion*: '.$champion->get_display_name();
      $response[] = '*Your Moves*: ';
      $mcount = 0;
      foreach ($move_list as $move) {
        $mcount++;
        $response[] = '_'. ($mcount <= 5 ? Display::addOrdinalNumberSuffix($mcount) .': ' : 'Tie-breaker: '). ucwords($move) .'_ ';
      }
      $response[] = '';
      $response[] = '_Are you sure you want to *cancel* this challenge?_';
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Delete the adventuring group for the challenger.
    $advgroup = AdventuringGroup::load(array('gid' => $player->gid, 'task_id' => $challenge->chid, 'task_type' => 'Challenge'));
    if (!empty($advgroup)) {
      $advgroup->delete();
    }

    // Remove the champion from their obligation.
    $champion->agid = 0;
    $success = $champion->save();
    if ($success === false) {
      $this->respond('There was a problem cancelling your Champion from the challenge. Please talk to Paul.');
      return FALSE;
    }

    // Add the fame back to the challenger.
    $player->fame += $wager;
    $success = $player->save();
    if ($success === false) {
      $this->respond('There was a problem giving back your wagered amount from the challenge. Please talk to Paul.');
      return FALSE;
    }

    // Delete the challenge.
    $challenge->delete();

    $this->respond('The Colosseum challenge against '.$opponent->get_display_name().' was successfully cancelled and your wager of '.Display::get_fame($wager).' was returned to you.');
    $this->respond($player->get_display_name()." cancelled the Colosseum challenge against you.", RPGSession::PERSONAL, $opponent);
  }


  /**
   * Revive a fallen Adventurer.
   */
  protected function cmd_revive ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'revive';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;

    $response = array();

    $cost_rate = 350;
    $revival_template = ItemTemplate::load(array('name_id' => 'revival_phoenixfeather'));

    // Show list of fallen adventurers.
    if (empty($args) || empty($args[0])) {
      $response[] = 'To revive a fallen adventurer, type: `revive [ADVENTURER NAME]`';
      $response[] = "All revivals cost a ".$revival_template->get_display_name()." and ".Display::get_currency($cost_rate)." per Adventurer Level.";
      $response[] = '';
      $response[] = "Graveyard:";
      $adventurers = Adventurer::load_multiple(array('gid' => $player->gid, 'dead' => true, 'revivable' => true));
      if (!empty($adventurers)) {
        foreach ($adventurers as $adventurer) {
          $cost = $adventurer->get_revive_cost($cost_rate);
          $ascends = $adventurer->get_soul_ascend_time();
          $response[] = ':rpg-tomb: '.$adventurer->get_display_name(true, false).($ascends === FALSE ? '' : ' _(soul ascends '.Display::get_remaining_time($ascends).')_').' — '.Display::get_currency($cost);
        }
        $response[] = '';
        $response[] = '_Note: When an adventurer\'s soul ascends, that adventurer can no longer be revived._';
      }
      else {
        $response[] = "_There are no adventurers here._";
      }

      $this->respond($response);
      return TRUE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // They chose a name, so let's check if that adventurer is available.
    $adventurer = Adventurer::load(array('name' => implode(' ', $args), 'gid' => $player->gid, 'dead' => true, 'revivable' => true), true);
    if (empty($adventurer)) {
      $this->respond('There is no deceased Adventurer by the name of "'.implode(' ', $args).'".');
      return FALSE;
    }

    // Check that there's enough room to revive an Adventurer.
    if ($player->get_adventurers_count(false) >= $player->adventurer_limit) {
      $this->respond("There is no room in your Guild to revive this Adventurer.");
      return FALSE;
    }

    // Check that they can afford to revive an adventurer.
    $cost = $adventurer->get_revive_cost($cost_rate);
    if ($player->gold < $cost) {
      $this->respond("You do not have ".Display::get_currency($cost)." to revive ".$adventurer->name.".");
      return FALSE;
    }

    // Check that they have the revival item.
    $requirement = Requirement::from("item,".$revival_template->name_id);
    $items = $player->has_required_items($requirement);
    if ($items === FALSE) {
      $this->respond("You do not have a ".$revival_template->get_display_name()." which is needed to revive ".$adventurer->name.".");
      return FALSE;
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = 'Are you sure you want to revive *'.$adventurer->name.'*?';
      $response[] = '';
      $response[] = $this->show_adventurer_status($adventurer);
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Remove the items they used to revive.
    foreach ($items as $item) {
      $player->remove_item($item);
      $item->delete();
    }

    // Pay for the revival.
    $player->gold -= $cost;
    $success = $player->save();
    if ($success === false) {
      $this->respond('There was a problem saving your Guild when reviving '.$adventurer->name.'. Please talk to Paul.');
      return FALSE;
    }

    // Revive the Adventurer.
    $adventurer->champion = false;
    $adventurer->agid = 0;
    $adventurer->dead = false;
    $success = $adventurer->save();
    if ($success === false) {
      $this->respond('There was a problem reviving '.$adventurer->name.'. Please talk to Paul.');
      return FALSE;
    }

    $this->respond($adventurer->get_display_name().' has risen from the dead and rejoined your party.');
  }



  /**
   * Shop for items.
   */
  protected function cmd_shop ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'shop';

    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player())) return;
    $response = array();
    
    // Show the list of available items.
    if (empty($args) || empty($args[0])) {
      $response[] = 'Welcome to the *Shop*';
      $response[] = '';
      $response[] = '_Note: All items are one-time use._';
      $response[] = '';
      $response[] = 'To purchase an item, type: `shop [ITEM NAME]` (example: `shop Shepherd`)';
      $response[] = '';
      $response[] = 'Items for sale:';

      // Show items for sale.
      $items = ItemTemplate::load_multiple(array('for_sale' => true));
      foreach ($items as $item) {
        $response[] = $item->get_display_name() .' (_'. $item->get_description() .'_) — '. Display::get_currency($item->cost);
      }

      if (empty($items)) $response[] = '_None_';
      
      $this->respond($response);
      return FALSE;
    }

    // Check the last argument for the confirmation code.
    $confirmation = false;
    if (!empty($args) && strpos($args[count($args)-1], 'CONFIRM') === 0) {
      $confirmation = array_pop($args);
    }

    // Get the item name.
    $item_name = implode(' ', $args);
    $item_template = ItemTemplate::load(array('name' => $item_name, 'for_sale' => true), true);
    if (empty($item_template)) {
      $response[] = 'There is no item named "'.$item_name.'" for sale.';
      $response[] = $this->get_typed($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Check if the player can afford the item.
    if ($player->gold < $item_template->cost) {
      $this->respond("You cannot afford to purchase ".$item_template->get_display_name().".");
      return FALSE;
    }

    // Check for a valid confirmation code.
    if (!empty($confirmation) && $confirmation != 'CONFIRM') {
      $response[] = 'The confirmation code "'.$confirmation.'" is invalid. The code should be: `CONFIRM`.';
      $response[] = '';
      // Re-display the confirmation text.
      $confirmation = false;
    }

    // Display the confirmation message and code.
    if (empty($confirmation)) {
      $response[] = "Are you sure you want to purchase a ".$item_template->get_display_name()."?";
      $response[] = '';
      $response[] = $this->show_item_information($item_template, true);
      $response[] = '';
      $response[] = $this->get_confirm($cmd_word, $orig_args);
      $this->respond($response);
      return FALSE;
    }

    // Purchase the item.
    $player->gold -= $item_template->cost;
    $success = $player->save();
    if ($success === false) {
      $this->respond('There was a problem saving your Guild information after paying. Please talk to Paul.');
      return FALSE;
    }

    // Receive the item.
    $success = $player->add_item($item_template);
    if ($success === false) {
      $this->respond('There was a problem giving you the item you purchased. Please talk to Paul.');
      return FALSE;
    }
    
    $this->respond("You purchased a ".$item_template->get_display_name()." for ".Display::get_currency($item_template->cost).".");
  }



  protected function cmd_admin ($args = array()) {
    $orig_args = $args;
    $cmd_word = 'admin';
    // Load the player and fail out if they have not created a Guild.
    if (!($player = $this->load_current_player()) || $player->admin != true) {
      if (!empty($args) && empty($args[0])) array_shift($args);
      array_unshift($args, 'admin');
      $this->_bad_command($args);
      return;
    }
    $response = array();

    // Command list.
    $cmd_options = array('test', 'gen_spritesheet', 'gen_map', 'gen_multi_quest', 'gen_quest', 'gen_item');

    if (empty($args) || empty($args[0])) {
      $response[] = '*List of commands*:';
      foreach ($cmd_options as $cmd) {
        $response[] = $cmd;
      }
      $this->respond($response);
      return TRUE;
    }

    // Delegate the command.
    if (in_array($args[0], $cmd_options)) {
      $cmd_secondary = array_shift($args);
      $this->{'cmd_admin_'.$cmd_secondary}($args, $player);
      return TRUE;
    }
  }


  /**
   * Generate the sprite sheet.
   */
  protected function cmd_admin_gen_spritesheet ($args, $player) {
    // Rough spritesheets directory.
    foreach(glob(RPG_SERVER_ROOT.'/icons/rough/*.*') as $file) {
      $file_name = explode('/', $file);
      $file_name = array_pop($file_name);
      $lined = SpriteSheet::add_grid_to_sheet('/'.$file_name, true);
      $this->respond("*".$file_name."*\n".'<img class="map" src="'.$lined['debug'].'">');      
    }
  }

  /**
   * Generate the sprite sheet.
   */
  protected function cmd_admin_gen_map ($args, $player) {
    $season = Season::current();
    $map = $season->get_map();

    $mapimage = MapImage::generate_image($map);
    $this->respond('<img class="map" src="'.$mapimage->url.'">');
  }

  /**
   * Generate the sprite sheet.
   */
  protected function cmd_admin_gen_multi_quest ($args, $player) {
    // Generate a multiplayer quest.
    $season = Season::current();
    $map = $season->get_map();

    // Get list of all revealed locations.
    $types = Location::types();
    $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    // Get the first location.
    $location = array_shift($locations);

    $quest = Quest::generate_multiplayer_quest($location);

    $this->respond('Generated mutil-guild quest: _'.$quest->get_display_name(false).'_.');
  }

  /**
   * Generate the sprite sheet.
   */
  protected function cmd_admin_gen_quest ($args, $player) {
    $season = Season::current();
    $map = $season->get_map();

    // Get list of all revealed locations.
    $types = Location::types();
    $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    // Get the first location.
    $location = array_shift($locations);

    $quest = Quest::generate_personal_quest($player, $location);

    $this->respond('Generated personal quest: _'.$quest->get_display_name(false).'_.');
  }

  /**
   * Generate the sprite sheet.
   */
  protected function cmd_admin_gen_item ($args, $player) {
    // Generate a random non-special item.
    if (empty($args) || empty($args[0])) {
      $item_template = ItemTemplate::random();
      if (empty($item_template)) {
        $this->respond('There was a problem generating a regular item?...');
        return FALSE;
      }
    }
    // Check if it's a special item.
    else if ($args[0] == 'special') {
      $special_probs = ItemType::SPECIAL_PROBABILITIES();
      $item_template = ItemTemplate::random(1, 0, 5, array(), array(), $special_probs);
      if (empty($item_template)) {
        $this->respond('There was a problem generating a special item?...');
        return FALSE;
      }
    }
    // Assume it's a template name_id.
    else {
      $item_template = ItemTemplate::load(array('name_id' => $args[0]));
      if (empty($item_template)) {
        $this->respond('There is no item with the ID of `'.$args[0].'`.');
        return FALSE;
      }
    }

    // Check if there's a receiver.
    $receiver = $player;
    if (!empty($args[1])) {
      $season = Season::current();
      $receiver = Guild::load(array('name' => $args[1], 'season' => $season->sid), true);
      if (empty($receiver)) {
        $this->respond('There is no Guild by the name of "'.$args[1].'"');
        return FALSE;
      }
    }

    // Give the receiver the item.
    $receiver->add_item($item_template);

    if ($player->gid != $receiver->gid) {
      $this->respond($receiver->get_display_name(false).' has been given a '.$item_template->get_display_name(false).'.');
      $this->respond('The RPG gods have bestowed you with a gift: '.$item_template->get_display_name(false), RPGSession::PERSONAL, $receiver);
    }
    else {
      $this->respond('You have been given a '.$item_template->get_display_name(false).'.');
    }
  }


  protected function cmd_admin_test ($args, $player) {
    return FALSE;

    // Test completing a Quest.
    $season = Season::current();
    $map = $season->get_map();

    // Get list of all revealed locations.
    $types = Location::types();
    $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    // Get the first location.
    $location = array_shift($locations);

    d($location->get_distance());
    d($location->get_exploration_exp());

    $quest = Quest::generate_personal_quest($player, $location);

    d($quest);

    // Register adventurers for this quest.
    $adventurers = $player->get_best_adventurers($quest->party_size_max);
    $bonus = Quest::make_bonus($player, $adventurers);

    // Put together the adventuring party.
    $data = array(
      'gid' => $player->gid,
      'created' => time(),
      'task_id' => $quest->qid,
      'task_type' => 'Quest',
      'task_eta' => $quest->get_duration($bonus),
      'completed' => false,
    );
    $advgroup = new AdventuringGroup ($data);
    $success = $advgroup->save();
    if ($success === false) {
      d('There was a problem saving the adventuring group. Please talk to Paul.');
      return FALSE;
    }

    // Assign all the adventurers to the new group.
    foreach ($adventurers as $adventurer) {
      $adventurer->agid = $advgroup->agid;
      $success = $adventurer->save();
      if ($success === false) {
        d('There was a problem saving an adventurer to the adventuring group. Please talk to Paul.');
        return FALSE;
      }
    }

    // Assign adventuring group to the quest.
    $quest->gid = $player->gid;
    $quest->agid = $advgroup->agid;
    $quest->active = false;
    $success = $quest->save();
    if ($success === false) {
      d('There was a problem saving the quest. Please talk to Paul.');
      return FALSE;
    }

    // Process the "completed" quest.
    $response = $quest->queue_process();

    d($response);

    return FALSE;


    // Test killing an adventurer and letting them revive.
    // $adventurers = $player->get_adventurers();
    // $adventurer = array_shift($adventurers);
    // $adventurer->kill();
    // return FALSE;



    // Test code for selecting locations for quests.
    // $season = Season::current();
    // $map = $season->get_map();

    // // Get list of all revealed locations.
    // $types = Location::types();
    // $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));

    // d($locations);

    // // Sort out locations by star-rating.
    // $all_locations = array('all' => $locations);
    // foreach ($locations as &$location) {
    //   for ($star = $location->star_min; $star <= $location->star_max; $star++) {
    //     if ($star == 0) continue;
    //     if (!isset($all_locations[$star])) $all_locations[$star] = array();
    //     $all_locations[$star]['loc'.$location->locid] = $location;
    //   }
    // }

    // d($all_locations);

    // // Determine the location list that the generator should pull from,
    // // choosing lower-star locations for Guilds with weaker adventurers.
    // $level = $player->calculate_adventurer_level_info();
    // d($level);
    // $star = Quest::calculate_appropriate_star_range($level['lo'], $level['hi']);
    // d($star);
    // $original_locations = array();
    // // Loop through all locations and merge together the viable locations.
    // for ($s = $star['lo']; $s <= $star['hi']; $s++) {
    //   if (!isset($all_locations[$s])) continue;
    //   $original_locations = array_merge($original_locations, $all_locations[$s]);
    // }

    // d($original_locations);

    // // If there are no locations for this star range (for whatever reason), default to all locations.
    // if (empty($original_locations)) $original_locations = $all_locations['all'];

    // d($original_locations);

    // return false;



    // Test generating personal quests.
    // $season = Season::current();
    // $map = $season->get_map();

    // $types = Location::types();
    // $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    // $location = array_splice($locations, array_rand($locations), 1);
    // $location = array_pop($location);

    // $quest = Quest::generate_personal_quest($player, $location);
    // d($quest);

    // return false;



    // $item_template = ItemTemplate::load(array('name_id' => 'ore_iron'));
    // $player->add_item($item_template);
    // return FALSE;


    // Test loading dead adventurers.
    // $deads = Adventurer::get_all_dead_adventurers();
    // d($deads);
    // return FALSE;


    // Test creating a Soul Stone and add it to a Guild's inventory.
    // $item_template = ItemTemplate::load(array('name_id' => 'relic_soulstone'));
    // $extra_data = 'Mortimer Magner';
    // $player->add_item($item_template, $extra_data);

    // d($player->get_items());

    // Test removing the Soul Stone item.
    // $item = Item::load(array('gid' => $player->gid, 'name_id' => 'relic_soulstone'));

    // $player->remove_item($item);

    // return FALSE;



    // Test generating a regular adventurer.
    // $reg = Adventurer::generate_new_adventurer(false, false);
    // d($reg);

    // Test generating an Undead adventurer.
    // $adventurer = Adventurer::generate_undead_adventurer('Mortimer Magner', false);
    // d($adventurer);

    // return FALSE;



    // Test get_task in AdventuringGroup class.
    // $data = array(
    //   'gid' => 1,
    //   'task_id' => 24,
    //   'task_type' => 'Quest',
    // );
    // $group = new AdventuringGroup ($data);

    // d($group->get_task());

    // return FALSE;



    // Test generating a multiplayer quest.
    $json = Quest::load_quest_names_list();
    $original_json = Quest::load_quest_names_list(true);

    $season = Season::current();
    $map = $season->get_map();

    // Get list of all revealed locations.
    $types = Location::types();
    $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    $location = array_splice($locations, array_rand($locations), 1);
    $location = array_pop($location);

    $mquest = Quest::generate_multiplayer_quest($location, $json, $original_json, false);

    d($mquest);

    // Create a soul stone from boss.
    // $item_template = ItemTemplate::load(array('name_id' => 'relic_soulstone'));

    // $qkeywords = $mquest->get_keywords();
    // d($qkeywords);

    // for ($i = 0; $i < 2; $i++) {
    //   $boss_adventurer = Adventurer::load(array('aid' => $mquest->boss_aid, 'gid' => 0));
    //   // Change the adventurer's name to their new Boss name from the quest.
    //   if (!empty($boss_adventurer)) {
    //     $boss_adventurer->name = $qkeywords[0];
    //     $boss_adventurer->save();
    //     $extra_data = $boss_adventurer->name;
    //   }
    //   // Create a generic adventurer.
    //   else {
    //     $name_parts = Adventurer::generate_adventurer_name();
    //     $extra_data = $name_parts['first'] .' '. $name_parts['last'];
    //   }

    //   d(compact('boss_adventurer', 'extra_data'));

    //   $player->add_item($item_template, $extra_data);
    // }

    return false;


    // Test generating personal quests.
    // $output_information = true;
    // $num_quests = 0;


    // $season = Season::current();
    // $map = $season->get_map();
    
    // // Load up the list of quest names.
    // $json = Quest::load_quest_names_list();
    // $original_json = Quest::load_quest_names_list(true);

    // // Get list of all revealed locations.
    // $types = Location::types();
    // $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
    // $original_locations = $locations;

    // // If there are no revealed locations, we can't generate quests yet.
    // if (empty($locations)) {
    //   if ($output_information) print "No new quests were created because no locations have been revealed.\n";
    //   return;
    // }

    // // Randomly choose a number of personal quests for all Guilds.
    // if ($num_quests <= 0) $num_quests = rand(2, 4);

    // // Get all the active Guilds this season.
    // $time = time();
    // $guilds = Guild::load_multiple(array('season' => $season->sid));

    // // Generate quests for each Guild.
    // foreach ($guilds as $guild) {
    //   $quests = array();

    //   // Check the number of current quests, as we do not want to exceed the max quest allowance.
    //   $cur_quests = $guild->get_quests();
    //   $quest_count = count($cur_quests);
    //   $num_new_quests = min($num_quests, Quest::MAX_COUNT - $quest_count);
    //   // If we've hit or exceeded the limit, do not create any quests.
    //   if ($num_new_quests <= 0) {
    //     if ($output_information) print $guild->name.' has hit or exceeded the quest limit ('.Quest::MAX_COUNT.').';
    //     continue;
    //   }

    //   for ($i = 0; $i < $num_new_quests; $i++) {
    //     // Choose a location to theme the quest with.
    //     if (empty($locations)) $locations = $original_locations;
    //     $location = array_splice($locations, array_rand($locations), 1);
    //     $location = array_pop($location);
    //     // If we still don't have a location, there's a problem so break out.
    //     if (empty($location)) break;
    //     // Generate the quest.
    //     $gquest = Quest::generate_personal_quest($guild, $location, $json, $original_json, true);
    //     if (!empty($gquest)) $quests[] = $gquest;
    //   }

    //   if ($output_information) print 'Generated '.count($quests).' quest(s) for '.$guild->name.'.';
    // }

    // return false;


    // Generate a bunch of quests to balance gold and exp.
    // $season = Season::current();
    // $map = Map::load(array('season' => $season->sid));
    // $location = Location::load(array('mapid' => $map->mapid, 'row' => 12, 'col' => 10));

    // // Overwrite star rating.
    // $location->star_min = 1;
    // $location->star_max = 1;

    // $json = Quest::load_quest_names_list();
    // $original_json = Quest::load_quest_names_list(true);
    // $num_quests = 20;
    // $quests = Quest::generate_quests($location, $num_quests, $json, $original_json, false);

    // $total_gold = 0;
    // $total_exp = 0;
    // $total_level = 0;

    // $response = array();
    // foreach ($quests as $quest) {
    //   $response[] = $quest->stars .' stars -- '. Display::get_currency($quest->reward_gold) .' -- Exp: '. $quest->reward_exp .' -- Party size: '. $quest->party_size_min .($quest->party_size_min != $quest->party_size_max ? '-'.$quest->party_size_max : '').' -- Qlevel: '. $quest->level;
    //   $total_gold += $quest->reward_gold;
    //   $total_exp += $quest->reward_exp;
    // }

    // $response[] = '';
    // $response[] = '*Average Gold*: '. Display::get_currency(floor($total_gold / $num_quests));
    // $response[] = '*Average Exp*: '. floor($total_exp / $num_quests);
    // $this->respond($response);

    // return false;


    // Test distance formula.
    // $season = Season::current();
    // $map = Map::load(array('season' => $season->sid));
    
    // $locations = $map->get_locations();
    // foreach ($locations as $location) {
    //   if ($location->type == Location::TYPE_EMPTY) continue;
    //   $dist = $location->get_distance();
    //   // 0-2.5 = 1-star
    //   // 2.6-5 = 2-star
    //   // 5.1-7.5 = 3-star
    //   // 7.6-10 = 4-star
    //   // 10+ = 5-star
    //   if ($dist <= 2.5) $location->star_max = 1;
    //   else if ($dist <= 5) $location->star_max = 2;
    //   else if ($dist <= 7.5) $location->star_max = 3;
    //   else if ($dist <= 10) $location->star_max = 4;
    //   else $location->star_max = 5;

    //   if ($location->star_max > 1) $location->star_min = $location->star_max - rand(0, 1);
    //   else $location->star_min = $location->star_max;

    //   $location->save();
    // }

    // return false;


    // Give all guilds some fame.
    // $season = Season::load(array('active' => true));
    // $guilds = Guild::load_multiple(array('season' => $season->sid));
    // foreach ($guilds as $guild) {
    //   $guild->fame += 20;
    //   $guild->save();
    // }



    // Test enhancing an adventurer.
    // $adventurer = Adventurer::load(array('name' => 'Auberon Cullen'));
    // $enhs = $adventurer->get_enhancements();
    // $enhancement = Bonus::QUEST_SUCCESS.',0.05,Quest->'.Quest::TYPE_BOSS;
    // $enh = Enhancement::from($enhancement);
    // $enhancement2 = Bonus::QUEST_SUCCESS.',0.10';
    // $enh2 = Enhancement::from($enhancement2);
    // $adventurer->add_enhancement($enh);
    // d($adventurer);
    // $adventurer->add_enhancement($enh2);
    // d($adventurer);
    // $adventurer->set_level(9, true);
    // d($adventurer);
    // $adventurer->save();
    // $adventurer->set_level(20);
    // d($adventurer);


    // Test Enhancement encoding and decoding.
    // $enhancement = Bonus::QUEST_SUCCESS.',-0.05,Quest->'.Quest::TYPE_BOSS;
    // $enhancement = Bonus::QUEST_SUCCESS.',-0.05';
    // d($enhancement);
    // $from = Enhancement::from($enhancement);
    // d($from);
    // $new = new Enhancement ();
    // $new->decode($enhancement);
    // d($new);
    // $encoded = $new->encode();
    // d($encoded);



    // Generate a quest based on a location.
    // $json = Quest::load_quest_names_list();
    // $original_json = Quest::load_quest_names_list(true);
    // d($json);
    // $location = Location::load(array('locid' => 5));
    // $quest = Quest::generate_quest_type($location, Quest::TYPE_BOSS, $json, $original_json, false);
    // d($quest);
    // d($json);




    // $lined = SpriteSheet::add_grid_to_sheet('/terrain.png', true);
    // $this->respond('<img class="map" src="'.$lined['debug'].'">');

    // $lined = SpriteSheet::add_grid_to_sheet('/capital.png', true);
    // $this->respond('<img class="map" src="'.$lined['debug'].'">');


    // Make the capital.
    // $season = Season::load(array('sid' => 1));
    // $map = Map::load(array('season' => $season->sid));

    // // Create Capital somewhere in the middle.
    // $capital_row = 50;
    // $capital_col = 50;

    // $capital_data = array(
    //   'mapid' => $map->mapid,
    //   'gid' => 0,
    //   'name' => 'The Capital',
    //   'row' => $capital_row,
    //   'col' => $capital_col,
    //   'type' => Location::TYPE_CAPITAL,
    //   'created' => time(),
    //   'revealed' => true,
    //   'open' => true,
    // );
    // $capital = new Location ($capital_data);
    // $success = $capital->save();

    // d($capital);
    // d($success);

    // return false;

    // Set all locations adjacent to revealed locations to visible.
    $season = Season::current();
    $map = Map::load(array('season' => $season->sid));

    /*$loc_json = Location::load_location_names_list();
    $loc_original_json = Location::load_location_names_list(true);

    $locations = Location::load_multiple(array('mapid' => $map->mapid, 'revealed' => true));
    $all = array();
    $new = array();
    foreach ($locations as $location) {
      $location->open = true;
      $location->save();
      $all[] = $location;

      // Get all adjacent locations and set them to open.
      $adjacents = $location->get_adjacent_locations(TRUE, $loc_json, $loc_original_json, FALSE);
      // Set them all to open.
      foreach ($adjacents as $adjacent) {
        $is_new = false;
        if (empty($adjacent->locid)) {
          $is_new = true;
          $new[] = $adjacent;
        }
        $adjacent->open = true;
        $adjacent->save();
        $all[] = $adjacent;
      }
    }

    Location::save_location_names_list($loc_json);

    d($new);*/

    // Test generating initial locations.
    /*$locations = $map->generate_locations(false);
    d($locations);
    return false;*/

    // Generate the new map.
    $mapimage = MapImage::generate_image($map);
    $this->respond('<img class="map" src="'.$mapimage->url.'">');

    return false;


    // Test location adjacents.
    // $season = Season::load(array('active' => true));
    // $map = Map::load(array('season' => $season->sid));
    // $location = Location::load(array('mapid' => $map->mapid, 'row' => 15, 'col' => 26));

    // $loc_json = Location::load_location_names_list();
    // $loc_original_json = Location::load_location_names_list(true);

    // $adjacents = $location->get_adjacent_locations(TRUE, $loc_json, $loc_original_json, FALSE);
    
    // d($adjacents);

    // return false;


    // Create the sprite sheet.
    // $spritesheet = SpriteSheet::generate(true);
    // $this->respond('<img class="map" src="'.$spritesheet['debug'].'">');

    // Create the Map image.
    // $season = Season::load(array('active' => true));
    // $map = Map::load(array('season' => $season->sid));
    // $mapimage = MapImage::generate_image($map);
    // $this->respond('<img class="map" src="'.$mapimage->url.'">');
    // return false;



    // Make a random type and create a name.
    // $json = Location::load_location_names_list();
    // $original_json = Location::load_location_names_list(true);
    // $types = Location::types();
    // $names = array();
    // d($json);
    // // for ($i = 0; $i < 20; $i++)
    //   $names[] = Location::generate_name(Location::TYPE_CREATURE, $json, $original_json); // Location::generate_name($types[array_rand($types)], $json, $original_json);

    // d($json);
    // d($names);



    // Generate new locations for the season.
    // $season = Season::load(array('active' => true));
    // $map = Map::load(array('season' => $season->sid));
    // $locations = $map->generate_locations(false);


    // $item_template = ItemTemplate::load(array('name_id' => 'kit_seisreport'));
    // $player->add_item($item_template);
    // return FALSE;


    // Create a kit to test.
    // $item_template = ItemTemplate::load(array('name_id' => 'kit_seisreport'));
    // $kit = new Item (array('gid' => $player->gid), $item_template);

    // // Make a fake quest to test out item probabilities.
    // $quest_data = array(
    //   'locid' => 10,
    //   'name' => 'Test Quest',
    //   'type' => Quest::TYPE_INVESTIGATE,
    //   'stars' => 1,
    //   'active' => true,
    //   'reward_gold' => 300,
    //   'reward_exp' => 200,
    //   'reward_fame' => 100,
    //   'duration' => 1000,
    //   'party_size_min' => 1,
    //   'party_size_max' => 3,
    //   'level' => 6,
    //   'success_rate' => 75,
    //   'death_rate' => 25,
    // );
    // $quest = new Quest ($quest_data);

    // $best_adventurers = $player->get_best_adventurers($quest->party_size_max);

    // d($quest->get_item_probabilities($player, $best_adventurers, $kit));


    
    // Test creating a Challenge and processing it.
    $guilds = Guild::load_multiple(array('season' => $player->season));

    // Create a challenge using the first 2 found.
    $challenger = array_pop($guilds);
    $opponent = array_pop($guilds);
    $challenger_champ = $challenger->get_champion();
    $opponent_champ = $opponent->get_champion();

    // Create a new challenge.
    $challenge_data = array(
      'challenger_id' => $challenger->gid,
      'challenger_champ' => $challenger_champ->aid,
      'challenger_moves' => 'attack,attack,attack,attack,attack,break', //'attack,attack,break,attack,attack,attack',
      'opponent_id' => $opponent->gid,
      'opponent_champ' => $opponent_champ->aid,
      'opponent_moves' => 'defend,defend,defend,defend,defend,defend', //'attack,defend,break,attack,defend,break',
      'created' => time(),
      'wager' => 5,
      'confirmed' => TRUE,
      'winner' => '',
      'reward' => 10,
    );
    $challenge = new Challenge ($challenge_data);
    $challenge->save();
    d($challenge);


    $c_ag_data = array(
      'gid' => $challenger->gid,
      'created' => time(),
      'task_id' => $challenge->chid,
      'task_type' => 'Challenge',
      'task_eta' => 10,
      'completed' => false,
    );
    $c_advgroup = new AdventuringGroup ($c_ag_data);
    $c_advgroup->save();
    $challenger_champ->agid = $c_advgroup->agid;
    $challenger_champ->save();


    $o_ag_data = array(
      'gid' => $opponent->gid,
      'created' => time(),
      'task_id' => $challenge->chid,
      'task_type' => 'Challenge',
      'task_eta' => 10,
      'completed' => false,
    );
    $o_advgroup = new AdventuringGroup ($o_ag_data);
    $o_advgroup->save();
    $opponent_champ->agid = $o_advgroup->agid;
    $opponent_champ->save();

    // Process the challenge.
    d($challenge->queue_process());



    // Test loading multiples with arrays.
    // $types = Location::types();
    // $locations = Location::load_multiple(array('type' => $types, 'revealed' => true));

    // d($locations);


    // Get a quest and fake-test finishing it.
    // $quest = Quest::load(array('locid' => 3));
    
    // $adventurers = $player->get_best_adventurers($quest->party_size_max);

    // // Put together the adventuring party.
    // $data = array(
    //   'gid' => $player->gid,
    //   'created' => time(),
    //   'task_id' => $quest->qid,
    //   'task_type' => 'Quest',
    //   'task_eta' => $quest->get_duration($player, $adventurers),
    //   'completed' => false,
    // );
    // $advgroup = new AdventuringGroup ($data);
    // $success = $advgroup->save();
    // if ($success === false) {
    //   $this->respond('There was a problem saving the adventuring group. Please talk to Paul.');
    //   return FALSE;
    // }

    // // Assign all the adventurers to the new group.
    // foreach ($adventurers as $adventurer) {
    //   $adventurer->agid = $advgroup->agid;
    //   $success = $adventurer->save();
    //   if ($success === false) {
    //     $this->respond('There was a problem saving an adventurer to the adventuring group. Please talk to Paul.');
    //     return FALSE;
    //   }
    // }

    // // Assign adventuring group to the quest.
    // $quest->gid = $player->gid;
    // $quest->agid = $advgroup->agid;
    // $quest->active = false;
    // $success = $quest->save();
    // if ($success === false) {
    //   $this->respond('There was a problem saving the quest. Please talk to Paul.');
    //   return FALSE;
    // }

    // // Process the "completed" quest.
    // $response = $quest->queue_process();

    // d($response);



    // Test new slack attachments.
    // $attachment = new SlackAttachment ();
    // $attachment->fallback = 'fallback text';
    // $attachment->color = '#000000';
    // $attachment->pretext = 'pretext';
    // $attachment->author_name = 'author name';
    // $attachment->author_link = 'author link';
    // $attachment->author_icon = 'author icon';
    // $attachment->title = 'title';
    // $attachment->title_link = 'title link';
    // $attachment->text = 'text';
    // $attachment->image_url = 'image url';
    // $attachment->thumb_url = 'thumb url';

    // $field1 = new SlackAttachmentField ();
    // $field1->title = 'Field1';
    // $field1->value = 'value1';
    // $field1->short = true;

    // d($field1->encode());

    // $field2 = new SlackAttachmentField ();
    // $field2->title = 'Field2';
    // $field2->value = 'value2';

    // d($field2->encode());

    // $attachment->add_field($field1);
    // $attachment->add_field($field2);

    // d($attachment->encode());

    // $attachment2 = new SlackAttachment ();
    // $attachment2->fallback = 'fallback text';
    // $attachment2->text = 'text';
    
    // d($attachment2->encode());


    // $message = new SlackMessage ();
    // $message->channel = 'channel';
    // $message->text = 'text';
    // $message->parse = 'parse';
    // $message->link_names = 'link names';
    // $message->unfurl_links = 'unfurl links';
    // $message->unfurl_media = 'unfurl media';

    // d($message->encode());

    // $message->add_attachment($attachment);
    // $message->add_attachment($attachment2);

    // d($message->encode());



    // Test encoding and decoding upgrade requirements.
    // $list = array(
    //   new Requirement (array('type' => 'item', 'name_id' => 'ore_steel', 'qty' => 3)),
    //   new Requirement (array('type' => 'item', 'name_id' => 'ore_steel')),
    //   Requirement::from('item,ore_iron'),
    //   Requirement::from('item,ore_iron,2'),
    //   Requirement::from('upgrade,equip1'),
    // );

    // d($list);

    // $upgrade = Upgrade::load(array('name_id' => 'equip2'));

    // d($upgrade);

    // return false;



    // Generate a random item.
    // $items = ItemTemplate::random(20, 3, 4);
    // d($items);

    // return false;



    // Give the player a powerstone.
    // $item_template = ItemTemplate::load(array('name_id' => 'powerstone_shaman'));
    
    // $items = &$player->get_items();
    
    // d($items);
    // $player->add_item($item_template);
    // d($items);
    // $item = $items[0];
    // $player->remove_item($item);
    // d($items);

    // return false;


    
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

  protected function load_current_player ($allow_error = true) {
    $player = $this->get_current_player();

    if (empty($player)) {
      // Check if the season is empty to show a different message.
      $season = Season::current();
      if (empty($season)) $this->respond('Please wait for a new Season to begin.');
      else $this->respond('You must register your Guild before you can begin playing. Type: `register [GUILD EMOJI] [GUILD NAME]`');
      return FALSE;
    }

    return $player;
  }

  protected function get_current_player () {
    // If we've already loaded this one, we're done.
    if (!empty($this->curplayer)) return $this->curplayer;

    // Get the currently-active season so that we pick the right Guild.
    $season = Season::current();
    if (empty($season)) return FALSE;

    // First time loading the player, so we need the data.
    $data = array(
      'slack_user_id' => $this->data['user_id'],
      'username' => $this->data['user_name'],
      'season' => $season->sid,
    );

    $this->curplayer = Guild::load($data);

    return $this->curplayer;
  }

  protected function create_temp_player () {
    $data = array(
      'slack_user_id' => $this->data['user_id'],
      'username' => $this->data['user_name'],
    );
    $player = new Guild ($data);
    return $player;
  }

  protected function get_typed ($cmd, $args, $include_space = true) {
    $arg_text = implode(' ', $args);
    return ($include_space ? "\n" : "")."(You typed: `".$cmd.(!empty($arg_text) ? " ".$arg_text : '')."`)";
  }

  protected function get_confirm ($cmd, $args) {
    //return "`".$cmd." ".implode(' ', $args).(!empty($confirm) ? " ".$confirm : "")."`";
    return "Type `+:confirm:` to confirm.".$this->get_typed($cmd, $args);
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
      if (empty($trim_name)) continue;

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

  protected function compact_items ($items, $exclude_on_hold = true) {
    $compact_items = array();

    // Compact same-name items.
    foreach ($items as &$item) {
      if ($exclude_on_hold && $item->on_hold) continue;
      $comp_id = $item->itid;
      if (!empty($item->extra_data)) $comp_id .= '.'.$item->iid;
      if (!isset($compact_items[$item->itid])) $compact_items[$comp_id] = array();
      $compact_items[$comp_id][] = $item;
    }

    return $compact_items;
  }

  protected function show_adventurer_status ($adventurer) {
    $response = array();
    // Show the status.
    $response[] = $adventurer->get_display_name(true, true, false, false, true);
    $response[] = '*Gender*: '.$adventurer->get_gender(true);
    $response[] = '*Class*: '.($adventurer->has_adventurer_class() ? $adventurer->get_adventurer_class()->get_display_name() : '_None_');
    $response[] = '*Level*: '.$adventurer->level;
    //$response[] = '*Popularity*: '.$adventurer->popularity;
    $response[] = '*Experience*: '.$adventurer->exp;
    $response[] = '*Experience to Next Level*: '.$adventurer->exp_tnl;
    
    $enhancements = $adventurer->get_enhancements();
    if (count($enhancements) > 0) {
      $response[] = '*Enhancements*:';
      foreach ($enhancements as $enhancement) {
        $response[] = $enhancement->get_display_name();
      }
    }

    return implode("\n", $response);
  }

  protected function show_upgrade ($upgrade) {
    $response = array();
    $response[] = $upgrade->get_display_name(false);
    if (!empty($upgrade->description)) $response[] = '*Description*: '.$upgrade->description;
    $response[] = '*Time to complete*: '.Display::get_duration($upgrade->duration);
    $response[] = '*Cost*: '.Display::get_currency($upgrade->cost);

    $required_items = $upgrade->get_required_type('item');
    if (count($required_items) > 0) $response[] = '*Required Items*:';
    foreach ($required_items as $requirement) {
      $item = ItemTemplate::load(array('name_id' => $requirement->name_id));
      if (empty($item)) continue;
      $response[] = ($requirement->qty > 1 ? $requirement->qty.'x ' : ''). $item->get_display_name(false);
    }

    $required_upgrades = $upgrade->get_required_type('upgrade');
    if (count($required_upgrades) > 0) $response[] = '*Prerequisite Upgrades*:';
    foreach ($required_upgrades as $requirement) {
      $req_upgrade = Upgrade::load(array('name_id' => $requirement->name_id));
      if (empty($req_upgrade)) continue;
      $response[] = $req_upgrade->get_display_name(false, false);
    }

    return implode("\n", $response);
  }

  protected function show_item_information ($item, $include_cost = false) {
    $response = array();
    $response[] = $item->get_display_name();
    $response[] = $item->get_description();
    if ($include_cost && $item->for_sale) $response[] = '*Cost*: '. Display::get_currency($item->cost);

    return implode("\n", $response);
  }

  protected function get_map_attachment () {
    $attachment = new SlackAttachment ();
    $attachment->title = 'World Map';
    $attachment->image_url = RPG_SERVER_PUBLIC_URL . MapImage::DEFAULT_IMAGE_URL . '?timestamp=' . time();
    $attachment->title_link = $attachment->image_url;

    return $attachment;
  }

  protected function show_available_adventurers ($guild, $include_level = true) {
    $response = array();
    $adventurers = $guild->get_adventurers();
    $available_adventurers = array();
    foreach ($adventurers as $adventurer) {
      if (!empty($adventurer->agid)) continue;
      $available_adventurers[] = $adventurer;
      $response[] = $adventurer->get_display_name(false, true, true, true, true, $include_level);
    }
    if (empty($available_adventurers)) $response[] = '_None_';
    return implode("\n", $response);
  }

  protected function introduction_message () {
    $fame = Display::fame();
    $gold = Display::currency();

    $response = array();
    $response[] = '*Introduction to the Slack RPG*';
    $response[] = "Congratulations on registering your Guild! Below is a brief introduction to the game and how it works. I hope you enjoy it!";
    $response[] = '';
    $response[] = '*Purpose*:';
    $response[] = 'The purpose of this game is to acquire the most '.$fame.' points. These points represent how well-known you are in the world. You earn '.$fame.' by completing exploring the map, completing quests, and dueling in the Colosseum.';
    $response[] = '';
    $response[] = '*Adventurers*:';
    $response[] = 'Adventurers perform various tasks for your Guild. As your Adventurers complete these tasks, they will receive experience points and level up. Higher level Adventurers increase your chances of successfully completing quests. As they level up, they will also randomly develop enhancements that help you out in various ways. Some tasks include:';
    $response[] = '- Exploring: Most people start out by sending their Adventurers out exploring the world. To do this, type `explore` and a map will appear with more instructions.';
    $response[] = '- Questing: Once new locations have been revealed in the map, quests will appear for you to complete. Type `quest` to see the list.';
    $response[] = '- Colosseum: After your Adventurers have leveled up a bit from exploring and questing, you may want to wager some '.$fame.' and see how your Champion compares to other Guilds by challenging them to a fight. Type `challenge` for more information.';
    $response[] = '';
    $response[] = '*Gold and Items*:';
    $response[] = 'Throughout your adventurers you will find '.$gold.' and items. These can be used for various things including:';
    $response[] = '- Upgrading your Guild (type `upgrade`)';
    $response[] = '- Purchasing items from the Shop (type `shop`)';
    $response[] = '- Reviving fallen Adventurers (type `revive`)';
    $response[] = '- Powering Up your Adventurers (type `powerup`)';
    $response[] = '';
    $response[] = '*Report and Status Information*:';
    $response[] = '- To see a report about your Guild, type `report`. You can add another Guild\'s name to see a summary of their information (example: `report [GUILD NAME]`).';
    $response[] = '- To see the Leaderboard and how Guilds are doing for '.$fame.', type `leaderboard`.';
    $response[] = '- To see information about your Adventurers, type `status`. You can add an Adventurer\'s name to see more detailed information (example: `status Aerith Gainsborough`)';
    $response[] = '';
    $response[] = '*Conclusion*:';
    $response[] = "I think that's enough reading for now. Get out there and explore!";
    return implode("\n", $response);
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
    
    foreach ($this->commands as $cmd_key => $cmd) {
      $input_to_check = substr($input, 0, strlen($cmd_key));
      // Check the fully lower-cased cmd.
      $check = strpos(strtolower($input_to_check), $cmd_key);
      if ($check !== false && $check === 0) {
        // Cut the command out of the input.
        $input = substr($input, strlen($input_to_check));
        $args = explode(' ', trim($input));

        $cmd_args = array();
        if (is_array($cmd)) {
          $callback = $cmd['callback'];
          if (isset($cmd['args']) && is_array($cmd['args']) && !empty($cmd['args'])) {
            $cmd_args = $cmd['args'];
          }
        }
        else {
          $callback = $cmd;
        }
        break;
      }
    }

    if (!empty($cmd_args)) $this->{$callback}($cmd_args, $args);
    else $this->{$callback}($args);
    return $this->response;
  }

  protected function _convert_to_markup ( $string ) {
    $info = array(
      '/:([A-Za-z0-9_\-\+]+?):/' => '<img class="icon" src="/debug/icons/\1.png" width="22px" height="22px">',
      '/\\n/' => '<br>',
      '/\*(.*?)\*/' => '<strong>\1</strong>',
      '/\b_((?:__|[\s\S])+?)_\b|^\*((?:\*\*|[\s\S])+?)\*(?!\*)/' => '<em>\1</em>',
      '/(`+)\s*([\s\S]*?[^`])\s*\1(?!`)/' => '<code>\2</code>',
    );

    return preg_replace(array_keys($info), array_values($info), $string);
  }

  /**
   * $text -> The text that RPG bot will respond with.
   * $location -> The type of message (private IM or public channel).
   * $player -> If set to PERSONAL, this is the player it should go to (default is the player typing the command).
   * $attachment -> A SlackAttachment object containing any attachment info that should be sent.
   *                See -- https://api.slack.com/docs/attachments
   */
  public function respond ($text = null, $location = RPGSession::PERSONAL, $player = null, $attachment = null) {
    if (is_array($text)) $text = implode("\n", $text);
    else if (!is_string($text)) $text = '';

    // Operate on the channel message.
    if ($location == RPGSession::CHANNEL) {
      // Create a message if we don't have one.
      if (!isset($this->response['channel'])) $this->response['channel'] = new SlackMessage ();

      // All messages to the public channel are attachments, so create one for the text if need be.
      if (!empty($text)) {
        $data = compact('text');
        $text_attachment = new SlackAttachment ($data);
        // Add the attachment to the message.
        $this->response['channel']->add_attachment($text_attachment);
      }

      // Add the attachment to the message.
      if (!empty($attachment)) $this->response['channel']->add_attachment($attachment);
    }
    // Operate on the personal message.
    else if ($location == RPGSession::PERSONAL) {
      // Create a list of messages if it hasn't happened already.
      if (!isset($this->response['personal'])) $this->response['personal'] = array();

      // If no player is set, it means we need to send to the current player.
      if (empty($player)) {
        // Get the current player.
        $player = $this->get_current_player();

        // If the current player isn't registered yet, create a fake one so we can send the message.
        if (empty($player)) $player = $this->create_temp_player();
      }

      // Set up the message for this player if we haven't already.
      if (!isset($this->response['personal'][$player->slack_user_id])) $this->response['personal'][$player->slack_user_id] = new SlackMessage (array('player' => $player));

      // If we added some attachment properties, create an attachment.
      if (!empty($attachment)) $this->response['personal'][$player->slack_user_id]->add_attachment($attachment);

      // Add the text to the message.
      if (!empty($text)) $this->response['personal'][$player->slack_user_id]->text .= $text;
    }

    // If this is debug mode through the browser, just spit it out.
    if (isset($this->data['forced_debug_mode']) && $this->data['forced_debug_mode'] == 'true') {
      if (!isset($this->response['added_debug_head'])) {
        $this->response['added_debug_head'] = true;
        echo '<head><link rel="stylesheet" type="text/css" href="debug/css/debug.css"></head>';
      }
      echo '<u>CHANNEL: '. $location .($player != null ? ' ('.$player->username.')' : '').'</u><br><br>';
      if (!empty($text)) echo '<div class="channel-'.$location.'">'.$this->_convert_to_markup($text);
      if (!empty($attachment)) {
        $style = !empty($attachment->color) ? ' style="border-color: '.$attachment->color.';"' : '';
        echo '<div class="attachment"'.$style.'>';
        if (!empty($attachment->pretext)) echo $this->_convert_to_markup($attachment->pretext).'<br>';
        if (!empty($attachment->title)) echo $this->_convert_to_markup($attachment->title).'<br>';
        if (!empty($attachment->text)) echo $this->_convert_to_markup($attachment->text).'<br>';
        if (!empty($attachment->image_url)) echo '<br><img class="map" src="'.$attachment->image_url.'" />';
        echo '</div>';
      }
      if (!empty($text)) echo '</div><br><br>';
      return;
    }
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