<?php

class Quest extends RPGEntitySaveable {
  // Fields
  public $qid;
  public $gid;
  public $agid;
  public $locid; // Location the Quest is found at.
  public $name;
  public $icon;
  public $type;
  public $stars;
  public $created;
  public $active;
  public $completed;
  public $reward_gold;
  public $reward_exp;
  public $reward_fame;
  public $duration;
  public $cooldown;
  public $requirements;
  public $party_size_min;
  public $party_size_max;
  public $level;
  public $success_rate;
  public $death_rate;
  public $kit_id; // ID of the Kit item being used on the quest.
  public $multiplayer;
  public $keywords;
  public $boss_aid;
  
  // Protected
  protected $_location;
  protected $_kit;
  protected $_adventurers;
  protected $_adventuring_groups;
  protected $_guilds;
  protected $_bonus;
  protected $_keywords;

  // Private vars
  static $fields_int = array('stars', 'created', 'reward_gold', 'reward_exp', 'reward_fame', 'duration', 'cooldown', 'party_size_min', 'party_size_max', 'level', 'success_rate', 'death_rate');
  static $db_table = 'quests';
  static $default_class = 'Quest';
  static $primary_key = 'qid';

  // Constants
  const FILENAME_QUEST_NAMES_ORIGINAL = '/bin/json/original/quest_names.json';
  const FILENAME_QUEST_NAMES = '/bin/json/quest_names.json';

  const MAX_COUNT = 6;
  const MULTIPLAYER_FAME_COST = 50;

  // Set this to 1 to use normal duration times. Set to a lower number (example 0.05) to reduce the Quest duration for debugging.
  const DEBUG_DURATION_MODIFIER = 0.05;

  const TYPE_EXPLORE = 'explore';
  const TYPE_TRAIN = 'train';
  const TYPE_INVESTIGATE = 'investigate';
  const TYPE_AID = 'aid';
  const TYPE_SPECIAL = 'special';
  const TYPE_FIGHT = 'fight';
  const TYPE_BOSS = 'boss';

  static $_types = array(Quest::TYPE_INVESTIGATE, Quest::TYPE_AID, Quest::TYPE_FIGHT, Quest::TYPE_BOSS, Quest::TYPE_SPECIAL);

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    if (empty($this->party_size_min)) $this->party_size_min = 1;
  }

  public function get_display_name ($bold = true) {
    return ($bold ? '*' : '') . $this->name . ($bold ? '*' : '');
  }

  public function load_location () {
    $this->_location = Location::load(array('locid' => $this->locid));
  }

  public function get_location () {
    if (empty($this->_location)) {
      $this->load_location();
    }

    return $this->_location;
  }

  public function load_kit () {
    $this->_kit = Item::load(array('iid' => $this->kit_id));
  }

  public function get_kit () {
    if (empty($this->_kit)) {
      $this->load_kit();
    }

    return $this->_kit;
  }

  public function get_party_size () {
    return $this->party_size_min .($this->party_size_max > 0 && $this->party_size_max != $this->party_size_min ? '-'.$this->party_size_max : '');
  }

  public function get_duration ($bonus = NULL) {
    $duration = $this->duration;
    // Get quest duration modifier.
    $duration_mod = empty($bonus) ? Bonus::DEFAULT_VALUE : $bonus->get_mod(Bonus::QUEST_SPEED, $this);
    // Modify quest duration.
    $duration = ceil($duration * $duration_mod);

    // DEBUGGING - Multiply by the debug modifier to change quest times.
    $duration = ceil($duration * Quest::DEBUG_DURATION_MODIFIER);

    // Load up the location for this Quest.
    $location = Location::load(array('locid' => $this->locid));
    if (!empty($location)) $duration += $location->get_duration($bonus);
    return $duration;
  }

  public function get_success_rate ($bonus, $adventurers = array()) {
    $rate = $this->success_rate;

    // Adjust it based on the level of the adventurers vs. the level of the quest.
    $levels = 0;
    // Get the quest success rate from the Guild.
    $mod = $bonus->get_mod(Bonus::QUEST_SUCCESS, $this, Bonus::MOD_HUNDREDS);
    foreach ($adventurers as $adventurer) $levels += $adventurer->level;

    // Modify the rate based on the level difference, then add the bonuses.
    if ($this->level > 0) {
      $diff = min($levels / $this->level, 1);
      $rate *= $diff;
    }

    $rate += $mod;

    // Prevent odd issues where Explore rate gets reduced.
    if ($this->type == Quest::TYPE_EXPLORE) $rate = 100;

    return $rate < 100 ? floor($rate) : 100;
  }

  public function get_death_rate ($bonus) {
    $rate = $this->death_rate;
    // Get the death rate modifier from the Guild.
    $mod = empty($bonus) ? Bonus::DEFAULT_VALUE : $bonus->get_mod(Bonus::DEATH_RATE, $this);
    return ceil($rate * $mod);
  }

  public function get_reward_gold ($bonus) {
    $gold = $this->reward_gold;
    $mod = empty($bonus) ? Bonus::DEFAULT_VALUE : $bonus->get_mod(Bonus::QUEST_REWARD_GOLD, $this);
    return ceil($gold * $mod);
  }

  public function get_reward_fame ($bonus) {
    $fame = $this->reward_fame;
    $mod = empty($bonus) ? Bonus::DEFAULT_VALUE : $bonus->get_mod(Bonus::QUEST_REWARD_FAME, $this);
    return ceil($fame * $mod);
  }

  public function get_reward_exp ($bonus) {
    $exp = $this->reward_exp;
    $mod = empty($bonus) ? Bonus::DEFAULT_VALUE : $bonus->get_mod(Bonus::QUEST_REWARD_EXP, $this);
    return ceil($exp * $mod);
  }

  public function get_reward_items ($bonus, $default_chance = 10) {
    $mod_diff = empty($bonus) ? 0 : $bonus->get_mod(Bonus::QUEST_REWARD_ITEM, $this, Bonus::MOD_HUNDREDS);
    // Add the bonus mod to the default rate.
    $chance_of_item = $default_chance + $mod_diff;
    // Check if any items were found.
    $items = array();
    if (rand(1, 100) <= $chance_of_item) {
      $rarity_min = ($this->stars - 1);
      $rarity_max = $this->stars;
      $item_probabilities = $this->get_item_probabilities($bonus);
      // Generate an item to be found, with the rarity relating to the Quest star rating.
      $items = ItemTemplate::random(1, $rarity_min, $rarity_max, array(), array(), $item_probabilities);
    }
    return $items;
  }

  public function get_reward_special_items ($bonus, $default_chance = 0) {
    $mod_diff = empty($bonus) ? 0 : $bonus->get_mod(Bonus::QUEST_REWARD_SPECIAL_ITEM, $this, Bonus::MOD_HUNDREDS);
    // Add the bonus mod to the default rate.
    $chance_of_item = $default_chance + $mod_diff;
    // Check if any items were found.
    $items = array();
    if (rand(1, 100) <= $chance_of_item) {
      $rarity_min = ($this->stars - 1);
      $rarity_max = $this->stars;
      // Get the special probabilities.
      $item_probabilities = $this->get_item_probabilities($bonus, ItemType::SPECIAL_PROBABILITIES());
      // Generate an item to be found, with the rarity relating to the Quest star rating.
      $items = ItemTemplate::random(1, $rarity_min, $rarity_max, array(), array(), $item_probabilities);
    }
    return $items;
  }

  public function get_item_probabilities ($bonus, $probs = NULL) {
    if ($probs === NULL) $probs = ItemType::PROBABILITIES();
    // Loop through each ItemType and modify the probability.
    foreach ($probs as $type => $value) {
      $mod = empty($bonus) ? 0 : $bonus->get_mod(Bonus::ITEM_TYPE_FIND_RATE, "ItemType->".$type, Bonus::MOD_DIFF);
      // Add to the existing probability.
      $probs[$type] = ($value + $mod);
    }
    
    return $probs;
  }

  public function queue_process ($queue = null) {
    // If we were give a Queue, destroy it.
    if (!empty($queue)) $queue->delete();

    // If there is no Adventuring Group, it's a reactivation.
    if (empty($this->agid)) {
      return $this->queue_process_reactivate($queue);
    }

    return $this->queue_process_quest($queue);
  }

  protected function queue_process_quest ($queue = null) {
    // Load the Guilds this quest is for.
    $guilds = $this->get_registered_guilds();
    // Load up the adventuring group.
    $advgroups = $this->get_registered_adventuring_groups();
    // Get all the adventurers on this quest.
    $adventurers = $this->get_registered_adventurers();
    // Get the kit used (if any).
    $kit = $this->get_kit();
    // Determine the overall bonus.
    $bonus = $this->get_bonus();
    // Extra calculations.
    $adv_count = count($adventurers);
    $guild_count = count($guilds);

    // Disband the adventuring groups.
    foreach ($advgroups as $advgroup) $advgroup->delete();

    // Determine if the quest was successful.
    $success_rate = $this->get_success_rate($bonus, $adventurers);
    $death_rate = $this->get_death_rate($bonus);
    // Generate a number between 1-100 and see if it's successful.
    $success = (rand(1, 100) <= $success_rate);

    // If it's successful, give out the rewards.
    $reward_exp = 0;
    $channel_data = array('text' => array());
    $quest_data = array();
    foreach ($guilds as $guild) {
      $quest_data[$guild->gid] = array('text' => array(), 'success' => $success, 'player' => $guild);
    }
    
    if ($success) {
      // Set channel message.
      if ($this->multiplayer) {
        $channel_data['title'] = 'Multi-Guild '. ucwords($this->type) .' Quest:';
        $channel_data['text'][] = '_'. $this->get_display_name(false) .'_ was completed.';
        $channel_data['color'] = SlackAttachment::COLOR_GREEN;
      }

      // Mark the quest as completed.
      $this->completed = true;
      $reward_gold = ceil($this->get_reward_gold($bonus) / $guild_count);
      $reward_fame = ceil($this->get_reward_fame($bonus) / $guild_count);
      // Calculate the exp per adventurer.
      $reward_exp = ceil($this->get_reward_exp($bonus) / $adv_count);

      // Give each Guild its reward.
      foreach ($guilds as $guild) {
        // Set personal message.
        $quest_data[$guild->gid]['success_msg'] = 'SUCCESS!';
        $quest_data[$guild->gid]['text'][] = $this->name .' was completed.';

        // Randomize some items for this Guild.
        $reward_items = $this->get_reward_items($bonus);
        // Special items rate is usually 0, but some bonuses can increase this.
        // If this is a Boss quest, increase the rate of a special item.
        $reward_special_items = $this->get_reward_special_items($bonus, ($this->type == Quest::TYPE_BOSS ? 10 : 0));

        if ($reward_gold > 0) {
          $guild->gold += $reward_gold;
          $quest_data[$guild->gid]['reward_gold'] = $reward_gold;
        }
        
        if ($reward_fame > 0) {
          $guild->fame += $reward_fame;
          $quest_data[$guild->gid]['reward_fame'] = $reward_fame;
        }

        if ($reward_exp > 0) {
          $quest_data[$guild->gid]['reward_exp'] = $reward_exp;
        }

        // Give the items this Guild found.
        if (!empty($reward_items)) {
          foreach ($reward_items as $item) {
            $guild->add_item($item);
          }
          if (!isset($quest_data[$guild->gid]['reward_items'])) $quest_data[$guild->gid]['reward_items'] = array();
          $quest_data[$guild->gid]['reward_items'] = array_merge($quest_data[$guild->gid]['reward_items'], $reward_items);
        }

        // Give the special items this Guild found.
        if (!empty($reward_special_items)) {
          $qkeywords = $this->get_keywords();
          foreach ($reward_special_items as $item) {
            // Check if this is a soul stone, and if so, add the Boss' name.
            $extra_data = NULL;
            if ($item->name_id == 'relic_soulstone') {
              $boss_adventurer = Adventurer::load(array('aid' => $this->boss_aid, 'gid' => 0));
              // Change the adventurer's name to their new Boss name from the quest.
              if (!empty($boss_adventurer)) {
                $boss_adventurer->name = $qkeywords[0];
                $boss_adventurer->save();
                $extra_data = $boss_adventurer->name;
              }
              // Create a generic adventurer.
              else {
                $name_parts = Adventurer::generate_adventurer_name();
                $extra_data = $name_parts['first'] .' '. $name_parts['last'];
              }
            }
            $guild->add_item($item, $extra_data);
          }
          if (!isset($quest_data[$guild->gid]['reward_items'])) $quest_data[$guild->gid]['reward_items'] = array();
          $quest_data[$guild->gid]['reward_items'] = array_merge($quest_data[$guild->gid]['reward_items'], $reward_special_items);
        }

        $guild->save();
      }
    }
    else {
      // Set channel message.
      if ($this->multiplayer) {
        $channel_data['title'] = 'Multi-Guild '. ucwords($this->type) .' Quest:';
        $channel_data['text'][] = '_'. $this->get_display_name(false) .'_ was unsuccessful...';
        $channel_data['color'] = SlackAttachment::COLOR_RED;
      }

      // Set personal messages.
      foreach ($guilds as $guild) {
        $quest_data[$guild->gid]['success_msg'] = 'FAIL...';
        $quest_data[$guild->gid]['text'][] = 'Your adventuring party failed to complete '. $this->name .'.';
      }
    }

    // If this is an exploration quest, reveal the location.
    if ($this->type == Quest::TYPE_EXPLORE) {
      // Single player, so just grab the guild.
      $eguild = reset($guilds);

      $location = $this->get_location();
      $location->revealed = true;
      $location->gid = $eguild->gid;
      $location->save();

      // After revealing a location, set all adjacent locations to open.
      $loc_json = Location::load_location_names_list();
      $loc_original_json = Location::load_location_names_list(true);
      $adjacents = $location->get_adjacent_locations(TRUE, $loc_json, $loc_original_json, FALSE);
      // Set them all to open.
      foreach ($adjacents as $adjacent) {
        $adjacent->open = true;
        $adjacent->save();
      }
      Location::save_location_names_list($loc_json);

      // Generate a new Quest for the guild(s) that discovered this new location.
      if ($location->type != Location::TYPE_EMPTY) {
        $new_quest = Quest::generate_personal_quest($eguild, $location);
      }

      // Regenerate the map now that a new location is revealed.
      $season = Season::current();
      $map = Map::load(array('season' => $season->sid));
      MapImage::generate_image($map);

      // If the location has a name, we found a non-empty spot.
      if (!empty($location->name)) {
        $quest_data[$eguild->gid]['text'][] = "You discovered ".$location->get_display_name().".".(isset($new_quest) && !empty($new_quest) ? " A new Quest is available." : '');
        $quest_data[$eguild->gid]['text'][] = '';
        $channel_data['text'][] = $eguild->get_display_name()." discovered ".$location->get_display_name().".";
        $channel_data['color'] = SlackAttachment::COLOR_GREEN;
      }
    }

    // Bring all the adventurers home and give them their exp.
    $time = time();
    foreach ($adventurers as $adventurer) {
      $adventurer->agid = 0;
      if (isset($reward_exp) && $reward_exp > 0) {
        // Give the exp and if they leveled up, show a message.
        if ($adventurer->give_exp($reward_exp)) {
          $quest_data[$adventurer->gid]['text'][] = $adventurer->get_display_name().' is now level '.$adventurer->get_level(false).'!';
        }
      }
      // Calculate if the adventurer died during this adventure ONLY if they failed the quest.
      if (!$success && $death_rate > 0 && rand(1, 100) <= $death_rate) {
        // Try to kill them (undying cannot die) and if it's successful, show the message.
        if ($adventuer->kill(false)) {
          $quest_data[$adventurer->gid]['text'][] = ':rpg-tomb: RIP '.$adventurer->get_display_name().' died during the quest.';
        }
      }
      $adventurer->save();
    }

    // Delete the kit item (was already removed from the player, but might as well clean up the db.
    if (!empty($kit)) $kit->delete();

    // If this is an exploration quest, we can delete it.
    if ($this->type == Quest::TYPE_EXPLORE) {
      $this->delete();
    }
    // Check if we need to reactivate this quest.
    else {
      $this->agid = 0;
      $this->kit_id = 0;
      if ($this->multiplayer) $this->gid = 0;
      $cooldown = 0;
      
      // Reactivate if the guilds failed to complete it.
      if (!$success) {
        // Create a temporary cooldown if it was a failed quest attempt.
        // $cooldown = (60 * 60) * ($this->stars * rand(3, 6));
        // If there's no cooldown, it's instantly active.
        if ($cooldown == 0) $this->active = true;
      }

      // Save before implementing the cooldown.
      $this->save();

      // If a cooldown was set, we need to queue up the activation.
      if (!empty($cooldown)) $this->queue( $cooldown );
    }

    // Get attachment to display for Quest.
    $quest_messages = array();
    foreach ($quest_data as $gid => $qdata) {
      $quest_message = $this->get_quest_result_as_message($qdata);
      $quest_message->text = 'Your adventurer'.($adv_count != 1 ? 's' : '').' '.($adv_count != 1 ? 'have' : 'has').' returned home from '.($this->type == Quest::TYPE_EXPLORE ? 'exploring' : 'questing').'.';
      $quest_messages[] = $quest_message;
    }

    if (!empty($channel_data['text'])) {
      $channel_message = $this->get_quest_channel_result_as_message($channel_data);
    }

    // Send out the messages.
    $result = array('messages' => $quest_messages);
    if (isset($channel_message)) $result['messages'][] = $channel_message;
    return $result;
  }

  protected function get_quest_channel_result_as_message ($channel_data) {
    if (is_array($channel_data['text'])) $channel_data['text'] = implode("\n", $channel_data['text']);
    
    $attachment = new SlackAttachment ($channel_data);
    $attachment->fallback = $channel_data['text'];

    $message = new SlackMessage ();
    $message->add_attachment($attachment);

    return $message;
  }

  protected function get_quest_result_as_message ($quest_data) {
    if (is_array($quest_data['text'])) $quest_data['text'] = implode("\n", $quest_data['text']);

    $attachment = new SlackAttachment ();

    if (isset($quest_data['text'])) $attachment->text = $quest_data['text'];
    
    if (isset($quest_data['color'])) $attachment->color = $quest_data['color'];
    else $attachment->color = $quest_data['success'] ? SlackAttachment::COLOR_GREEN : SlackAttachment::COLOR_RED;

    if (isset($quest_data['success_msg'])) {
      $attachment->fallback = $quest_data['success_msg'];
      $attachment->title = $quest_data['success_msg'];
    }

    $attachment->fallback .= ' '.$quest_data['text'];
    $rewards = array();

    if (isset($quest_data['reward_gold'])) {
      $field = new SlackAttachmentField ();
      $field->title = 'Gold';
      $field->value = Display::get_currency($quest_data['reward_gold']);
      $field->short = 'true';
      $rewards[] = $field->value;
      $attachment->add_field($field);
    }

    if (isset($quest_data['reward_fame'])) {
      $field = new SlackAttachmentField ();
      $field->title = 'Fame';
      $field->value = Display::get_fame($quest_data['reward_fame']);
      $field->short = 'true';
      $rewards[] = $field->value;
      $attachment->add_field($field);
    }

    if (isset($quest_data['reward_exp'])) {
      $field = new SlackAttachmentField ();
      $field->title = 'Experience';
      $field->value = Display::get_exp($quest_data['reward_exp']);
      $field->short = 'true';
      $rewards[] = $field->value.' experience points (divided among the participating adventurers)';
      $attachment->add_field($field);
    }

    if (isset($quest_data['reward_items'])) {
      $field = new SlackAttachmentField ();
      $field->title = 'Items';
      $items = array();
      foreach ($quest_data['reward_items'] as $item) {
        $items[] = $item->get_display_name();
      }
      $field->value = implode("\n", $items);
      $field->short = 'true';
      $rewards[] = implode(', ', $items);
      $attachment->add_field($field);
    }

    if (!empty($rewards)) $attachment->fallback .= ' You receive: '. implode(', ', $rewards);

    $message = new SlackMessage ();
    $message->player = $quest_data['player'];
    $message->add_attachment($attachment);

    return $message;
  }

  protected function queue_process_reactivate ($queue = null) {
    $this->agid = 0;
    $this->active = true;
    $this->save();
  }

  public function load_registered_adventuring_groups () {
    $this->_adventuring_groups = AdventuringGroup::load_multiple(array('task_id' => $this->qid, 'task_type' => 'Quest'));

    return $this->_adventurers;
  }

  public function get_registered_adventuring_groups ($refresh = false) {
    if ($refresh || $this->_adventuring_groups === null) $this->load_registered_adventuring_groups();

    return $this->_adventuring_groups;
  }

  public function load_registered_adventurers ($refresh = false) {
    $this->_adventurers = array();

    // Load up all adventuring groups for this quest.
    $groups = $this->get_registered_adventuring_groups($refresh);
    if (empty($groups)) return $this->_adventurers;

    foreach ($groups as $group) {
      $this->_adventurers = array_merge($this->_adventurers, $group->get_adventurers());
    }

    return $this->_adventurers;
  }

  public function get_registered_adventurers ($refresh = false) {
    if ($refresh || $this->_adventurers === null) $this->load_registered_adventurers($refresh);

    return $this->_adventurers;
  }

  public function load_registered_guilds ($refresh = false) {
    $this->_guilds = array();
    // Load up all adventuring groups for this quest.
    $groups = $this->get_registered_adventuring_groups($refresh);
    if (empty($groups)) return $this->_guilds;

    foreach ($groups as $group) {
      $this->_guilds[] = $group->get_guild();
    }

    return $this->_guilds;
  }

  public function get_registered_guilds ($refresh = false) {
    if ($refresh || $this->_guilds === null) $this->load_registered_guilds($refresh);

    return $this->_guilds;
  }

  public function is_registered_guild ($guild) {
    $guilds = $this->get_registered_guilds();
    if (empty($guilds)) return FALSE;

    foreach ($guilds as $aguild) {
      if ($aguild->gid == $guild->gid) return TRUE;
    }

    return FALSE;
  }

  public function load_bonus () {
    // Get all the data we need.
    $guilds = $this->get_registered_guilds();
    $adventurers = $this->get_registered_adventurers();
    $kit = $this->get_kit();
    
    // Make a new bonus.
    $this->_bonus = Quest::make_bonus($guilds, $adventurers, $kit);

    return $this->_bonus;
  }
  
  public function get_bonus ($refresh = false) {
    if ($refresh || empty($this->_bonus)) $this->load_bonus();

    return $this->_bonus;
  }

  /**
   * Check if there are enough adventurers to go on the quest.
   */
  public function is_ready ($refresh = false) {
    $adventurers = $this->get_registered_adventurers($refresh);
    return (count($adventurers) >= $this->party_size_max);
  }

  public function open_spots ($refresh = false) {
    $adventurers = $this->get_registered_adventurers($refresh);
    return ($this->party_size_max - count($adventurers));
  }

  public function needs_approval ($refresh = false) {
    return ($this->active && $this->is_ready($refresh));
  }

  public function load_keywords () {
    $this->_keywords = $this->__decode_keywords($this->keywords);
  }

  public function get_keywords () {
    if ($this->_keywords === NULL) $this->load_keywords();
    return $this->_keywords;
  }

  public function set_keywords ($list) {
    // Encode the keywords and store them.
    $this->keywords = $this->__encode_keywords($list);
    // Reload the keywords.
    $this->load_keywords();
  }


  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  /**
   * Generate quests for a location.
   */
  public static function generate_quests ($location, $num_quests = 0, &$json = NULL, $original_json = NULL, $save = true) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    $save_json = empty($json);
    if (empty($json)) $json = Quest::load_quest_names_list();
    if (empty($original_json)) $original_json = Quest::load_quest_names_list(true);
    
    $quests = array();
    if ($num_quests <= 0) $num_quests = rand(1, 3) + 1;

    // For now, generate a number of quests = star rating.
    for ($i = 0; $i < $num_quests; $i++) {
      // Determine the type.
      $type = Quest::randomize_quest_types($location->type);
      // Generate the quest.
      $quest = Quest::generate_quest_type($location, $type, $json, $original_json, $save);
      $quests[] = $quest;
    }

    // Save JSON file.
    if ($save_json) Quest::save_quest_names_list($json);

    return $quests;
  }

  public static function generate_personal_quest ($guild, $location, &$json = NULL, $original_json = NULL, $save = true) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    $save_json = empty($json);
    if (empty($json)) $json = Quest::load_quest_names_list();
    if (empty($original_json)) $original_json = Quest::load_quest_names_list(true);
    
    // Determine the type.
    $type = Quest::randomize_quest_types($location->type, array(Quest::TYPE_BOSS));

    // Generate the quest.
    $quest = Quest::generate_quest_type($location, $type, $json, $original_json, false);

    // Assign the quest to the guild.
    $quest->gid = $guild->gid;
    if ($save) $success = $quest->save();

    // Save JSON file.
    if ($save_json) Quest::save_quest_names_list($json);

    return $quest;
  }

  public static function generate_multiplayer_quest ($location, &$json = NULL, $original_json = NULL, $save = true) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    $save_json = empty($json);
    if (empty($json)) $json = Quest::load_quest_names_list();
    if (empty($original_json)) $original_json = Quest::load_quest_names_list(true);
    
    // For now only Boss quests are multiplayer.
    $type = Quest::TYPE_BOSS;

    // Generate the quest.
    $quest = Quest::generate_quest_type($location, $type, $json, $original_json, $save);

    // Save JSON file.
    if ($save_json) Quest::save_quest_names_list($json);

    return $quest;
  }

  public static function generate_quest_type ($location, $type, &$json = NULL, $original_json = NULL, $save = true) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    $save_json = empty($json);
    if (empty($json)) $json = Quest::load_quest_names_list();
    if (empty($original_json)) $original_json = Quest::load_quest_names_list(true);

    $hours = 60 * 60;
    $days = 24 * $hours;

    // Determine the star rating.
    $stars = rand($location->star_min, $location->star_max);

    $data = array(
      'locid' => $location->locid,
      'completed' => false,
      'stars' => $stars,
      'created' => time(),
      'type' => $type,
      'active' => true,
      'cooldown' => 0,
      'multiplayer' => false,
      'death_rate' => 0,
    );

    // Generate the name and icon.
    $name_and_icon = Quest::generate_quest_name_and_icon($location, $type, $json, $original_json);
    $data['name'] = $name_and_icon['name'];
    $data['icon'] = $name_and_icon['icon'];
    $data['keywords'] = Quest::__encode_keywords($name_and_icon['keywords']);
    $data['boss_aid'] = $name_and_icon['boss_aid'];

    // Overrides for 1-star quests.
    if ($stars == 1) {
      $data['party_size_max'] = rand(2, 3);
      $data['level'] = max(1, $data['party_size_max'] * rand(0, 3));
      $data['success_rate'] = 100 - rand(0, 5);
    }

    // Overrides for 5-star quests.
    if ($stars == 5) {
      $data['success_rate'] = 100 - Quest::sum_multiple_randoms($stars, 3, 5);
    }

    // Set some defaults.
    if (!isset($data['party_size_min'])) $data['party_size_min'] = 1;
    if (!isset($data['party_size_max'])) $data['party_size_max'] = 3;
    $avg_party_size = ($data['party_size_max'] - $data['party_size_min'] / 2) + $data['party_size_min'];
    if (!isset($data['duration'])) $data['duration'] = Quest::sum_multiple_randoms($stars, 2, 4) * $hours;
    if (!isset($data['reward_gold'])) $data['reward_gold'] = Quest::sum_multiple_randoms($stars, 50, 250);
    if (!isset($data['reward_exp'])) $data['reward_exp'] = Quest::calc_exploration_exp_reward($location, $data['party_size_max']) + Quest::calc_quest_exp($type, $data['duration'], $stars, $avg_party_size);
    if (!isset($data['reward_fame'])) $data['reward_fame'] = Quest::sum_multiple_randoms($stars, 4, 8);
    if (!isset($data['level'])) $data['level'] = Quest::sum_multiple_randoms(($avg_party_size * $stars), 1, 4);
    if (!isset($data['success_rate'])) $data['success_rate'] = 100 - Quest::sum_multiple_randoms($stars, 1, 4);

    // Calculate the rewards and information.
    switch ($type) {
      case Quest::TYPE_EXPLORE:
        // THIS ALL HAPPENS IN RPGSession.php

        // $data['reward_gold'] = 0;
        // $data['reward_exp'] = Quest::calc_exploration_exp_reward($location, $data['party_size_max']);
        // $data['reward_fame'] = 0;
        // $data['duration'] = 0;
        // $data['level'] = 0;
        // $data['success_rate'] = 100;
        // // Bonus reward if you discover a non-empty location.
        // if ($location->type != Location::TYPE_EMPTY) {
        //   $data['reward_fame'] += $stars * 3;
        //   $data['reward_exp'] += $stars * rand(10, 15);
        // }
        break;

      case Quest::TYPE_BOSS:
        $data['active'] = false;
        $data['cooldown'] = (rand(0, 10) * $hours); // 0-10 hours.
        $data['party_size_min'] = rand(6 + ($stars * 2), 10 + ($stars * 2));
        $data['party_size_max'] = $data['party_size_min'];
        $avg_party_size = ($data['party_size_max'] - $data['party_size_min'] / 2) + $data['party_size_min'];
        // Assume 3 adventurers per Guild + 1 per Guild for every star above 3.
        $avg_num_advs_per_guild = 3 + max(0, ($stars - 3));
        $avg_num_guilds = max(1, ceil($data['party_size_max'] / $avg_num_advs_per_guild));
        $data['duration'] = (rand(8, 14) * $stars) * $hours;
        $data['reward_gold'] = Quest::sum_multiple_randoms($stars, 150, 500) * $avg_num_guilds;
        $data['reward_exp'] = Quest::calc_exploration_exp_reward($location, $data['party_size_max']) + Quest::calc_quest_exp($type, $data['duration'], $stars, $avg_party_size);
        $data['reward_fame'] = (Quest::sum_multiple_randoms($stars, 12, 24) + Quest::MULTIPLAYER_FAME_COST) * $avg_num_guilds;
        $data['success_rate'] = 80 - Quest::sum_multiple_randoms($stars, 8, 12);
        $data['death_rate'] = $stars * rand(5, 8);
        $data['multiplayer'] = true;
        break;

      case Quest::TYPE_FIGHT:
        $data['reward_exp'] += floor($data['reward_exp'] * 0.10);
        $data['death_rate'] = $stars * rand(1, 3);
        break;

      case Quest::TYPE_AID:
        $data['reward_fame'] += floor($data['reward_fame'] * 0.15);
        break;

      case Quest::TYPE_INVESTIGATE:
        $data['reward_gold'] += floor($data['reward_gold'] * 0.25);
        break;
    }

    // Create the Quest.
    $quest = new Quest ($data);
    
    // If we need to save it, do so and queue up the cooldown if there is one.
    if ($save) {
      $quest->save();
      // Queue up the cooldown if we need to.
      if ($quest->cooldown > 0 && !$quest->active) $quest->queue($quest->cooldown);
    }

    // Save JSON file.
    if ($save_json) Quest::save_quest_names_list($json);

    return $quest;
  }

  public static function calc_exploration_exp_reward ($location, $num_adventurers) {
    return $location->get_exploration_exp() * $num_adventurers;
  }

  public static function types () {
    // Not included: Quest::TYPE_EXPLORE, Quest::TYPE_TRAIN
    return Quest::$_types;
  }

  protected static function generate_quest_name_and_icon ($location, $type, &$json, $original_json) {
    $info = array(
      'name' => '',
      'keywords' => array(),
      'icon' => ':pushpin:',
      'boss_aid' => 0,
    );
    if ($location->type == Location::TYPE_EMPTY) return $info;

    // Load up the list of location names.
    $save_json = empty($json);
    if (empty($json)) $json = Quest::load_quest_names_list();
    if (empty($original_json)) $original_json = Quest::load_quest_names_list(true);

    // Get the JSON for this location type.
    $json_list =& $json[$type];
    $original_json_list = $original_json[$type];

    // Create any extra tokens that should be passed into the format-type.
    $tokens = $location->get_tokens_from_keywords();

    // Add a token for a dead adventurer name for Boss quests.
    if ($type == Quest::TYPE_BOSS) {
      $dead_adventurer = Quest::get_random_dead_adventurer(false);
      // Set the adventurer to "bossed" (aka turned into a boss).
      $dead_adventurer->bossed = true;
      $dead_adventurer->class = AdventurerClass::UNDEAD;
      $dead_adventurer->save();
      // Create the token.
      $dead_name = $dead_adventurer->get_name_parts();
      $tokens['!boss_name'] = $dead_name['first'];
      $info['boss_aid'] = $dead_adventurer->aid;
    }

    // Randomly generate the name.
    $name_info = JSONList::generate_name($json_list, $original_json_list, $tokens);
    $info = array_merge($info, $name_info);

    // Replace the keywords with the boss name.
    if ($type == Quest::TYPE_BOSS) {
      $info['keywords'] = $name_info['tokens']['!boss_name'] .' '. $name_info['tokens']['!boss_suffix'];
    }

    // If we're supposed to save the JSON, do so now.
    if ($save_json) Location::save_location_names_list($json);

    return $info;
  }

  protected static function get_random_dead_adventurer ($save_new_adventurer = true) {
    $deads = Adventurer::get_all_dead_adventurers(false);

    // If there are no dead adventurers ever, create a random dead adventurer.
    if (empty($deads)) {
      $dead = Adventurer::generate_new_adventurer(false, false);
      $dead->dead = true;
      $dead->revivable = false;
      $dead->available = false;
      if ($save_new_adventurer) $dead->save();
    }
    // If we've got dead adventurers, get a name.
    else {
      $dead = $deads[array_rand($deads)];
    }

    return $dead;
  }

  /**
   * Calculate multiple randomized numbers and add them together.
   */
  public static function sum_multiple_randoms ($num, $min, $max) {
    $value = 0;
    for ($i = 1; $i <= $num; $i++) $value += rand($min, $max);
    return $value;
  }

  protected static function calc_quest_exp ($type, $duration, $stars, $avg_adventurers) {
    $exp = 0;
    $hours = floor($duration / 60 / 60);

    // Boss:
    // (8-14 x stars) hours
    // 1-star -> 15 exp/hour
    // 2-star -> 18 exp/hour
    // 3-star -> 21 exp/hour
    // 4-star -> 25 exp/hour
    // 5-star -> 30 exp/hour
    if ($type == Quest::TYPE_BOSS) {
      if ($stars == 1) $exp = $hours * $avg_adventurers * 15;
      else if ($stars == 2) $exp = $hours * $avg_adventurers * 18;
      else if ($stars == 3) $exp = $hours * $avg_adventurers * 21;
      else if ($stars == 4) $exp = $hours * $avg_adventurers * 25;
      else $exp = $hours * $avg_adventurers * 30;
    }
    // Normal:
    // (2-4 x stars) hours
    // 1-star -> 10 exp/hour
    // 2-star -> 12 exp/hour
    // 3-star -> 14 exp/hour
    // 4-star -> 17 exp/hour
    // 5-star -> 20 exp/hour
    else {
      if ($stars == 1) $exp = $hours * $avg_adventurers * 10;
      else if ($stars == 2) $exp = $hours * $avg_adventurers * 12;
      else if ($stars == 3) $exp = $hours * $avg_adventurers * 14;
      else if ($stars == 4) $exp = $hours * $avg_adventurers * 17;
      else $exp = $hours * $avg_adventurers * 20;
    }

    return $exp;
  }

  public static function randomize_quest_types ($loc_type, $exclude_types = array()) {
    // Set probabilities based on location type.
    $loc_types = Quest::quest_probabilities();

    $list = array();
    if (!isset($loc_types[$loc_type])) return $list;

    // Populate a list full of the types based on the probability given.
    foreach ($loc_types[$loc_type] as $type => $prob) {
      if (in_array($type, $exclude_types)) continue;

      $count = $prob * 1000;
      for ($i = 0; $i < $count; $i++) {
        $list[] = $type;
      }
    }

    // Choose an entry randomly from the list.
    $index = array_rand($list);

    return $list[$index];
  }

  public static function quest_probabilities () {
    // Set probabilities based on location type.
    $types = array();
    $types[Location::TYPE_DOMICILE] = array(
      Quest::TYPE_FIGHT => 0.15,
      Quest::TYPE_BOSS => 0.05,
      Quest::TYPE_INVESTIGATE => 0.25,
      Quest::TYPE_AID => 0.45,
      Quest::TYPE_SPECIAL => 0.10,
    );

    $types[Location::TYPE_CREATURE] = array(
      Quest::TYPE_FIGHT => 0.45,
      Quest::TYPE_BOSS => 0.15,
      Quest::TYPE_INVESTIGATE => 0.30,
      Quest::TYPE_AID => 0.05,
      Quest::TYPE_SPECIAL => 0.05,
    );

    $types[Location::TYPE_STRUCTURE] = array(
      Quest::TYPE_FIGHT => 0.25,
      Quest::TYPE_BOSS => 0.02,
      Quest::TYPE_INVESTIGATE => 0.50,
      Quest::TYPE_AID => 0.10,
      Quest::TYPE_SPECIAL => 0.13,
    );

    $types[Location::TYPE_LANDMARK] = array(
      Quest::TYPE_FIGHT => 0.17,
      Quest::TYPE_BOSS => 0.08,
      Quest::TYPE_INVESTIGATE => 0.65,
      Quest::TYPE_AID => 0.05,
      Quest::TYPE_SPECIAL => 0.05,
    );

    return $types;
  }

  /**
   * $json -> The JSON-decoded list of quest names. Pass this in if you're doing bulk operations and only want to load and save once.
   */
  public static function recycle_quest ($quest, &$json = null) {
    // If there's no name, we can't categorize it, so we're done.
    if (empty($quest->name)) return false;

    // Load up the list of quest names.
    /*$save_json = empty($json);
    if (empty($json)) $json = Quest::load_quest_names_list();

    // Determine the name and add it back.
    $name = $quest->name;
    $json['names'][] = $name;

    // Add icon back to the icons list.
    //if (!empty($quest->icon)) $json['icons'][] = $quest->icon;

    // Add the names back to the JSON file.
    if ($save_json) Quest::save_quest_names_list($json);*/

    return true;
  }

  /**
   * Load up the list of Quest names that are still available.
   */
  public static function load_quest_names_list ($original = false) {
    $file_name = RPG_SERVER_ROOT .($original ? Quest::FILENAME_QUEST_NAMES_ORIGINAL : Quest::FILENAME_QUEST_NAMES);
    $names_json_string = file_get_contents($file_name);
    return json_decode($names_json_string, true);
  }

  /**
   * $data -> An array that can be properly encoded using PHP's json_encode function.
   */
  public static function save_quest_names_list ($data) {
    // Write out the JSON file to prevent names from being reused.
    $fp = fopen(RPG_SERVER_ROOT . Quest::FILENAME_QUEST_NAMES, 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
  }

  /**
   * Replace the working Quest names list with a copy of the original.
   */
  public static function refresh_original_quest_names_list () {
    // Load the original JSON list.
    $json = Quest::load_quest_names_list(true);

    // Overwrite the working copy with the new list.
    Quest::save_quest_names_list($json);

    return $json;
  }

  public static function get_type_name ($type) {
    switch ($type) {
      case Quest::TYPE_EXPLORE: return 'exploring';
      case Quest::TYPE_INVESTIGATE: return 'an investigation quest';
      case Quest::TYPE_AID: return 'an aiding quest';
      case Quest::TYPE_FIGHT: return 'a fighting quest';
      case Quest::TYPE_BOSS: return 'a boss quest';
      case Quest::TYPE_SPECIAL: return 'a special quest';
    }

    return $type;
  }

  public static function make_bonus ($guilds, $adventurers, $kit = NULL) {
    if (!is_array($guilds)) $guilds = array($guilds);

    $bonus = new Bonus ();

    // Merge in the Guild bonuses.
    foreach ($guilds as $guild) {
      $bonus->merge($guild->get_bonus());
    }

    // Merge in the Adventurer bonuses.
    foreach ($adventurers as $adventurer) {
      $bonus->merge($adventurer->get_bonus());
    }

    // Merge in the Kit bonus.
    if (!empty($kit)) $bonus->merge($kit->get_bonus());

    return $bonus;
  }

  protected static function __encode_keywords ($list) {
    return is_array($list) ? implode('|', $list) : '';
  }

  protected static function __decode_keywords ($string) {
    return empty($string) ? array() : explode('|', $string);
  }

  /**
   * From a low and high range of adventurer levels, calculate an appropriate star rating range.
   *
   * Note: The max is intentionally 4 so that 5-star quests are still somewhat rare.
   */
  public static function calculate_appropriate_star_range ($level_lo, $level_hi) {
    $star = array(
      'lo' => 1,
      'hi' => 4,
    );

    if ($level_lo < 5) $star['lo'] = 1;
    else if ($level_lo < 10) $star['lo'] = 2;
    else if ($level_lo < 15) $star['lo'] = 3;
    else $star['lo'] = 4;

    if ($level_hi < 5) $star['hi'] = 1;
    else if ($level_hi < 10) $star['hi'] = 2;
    else if ($level_hi < 15) $star['hi'] = 3;
    else $star['hi'] = 4;

    return $star;
  }

}