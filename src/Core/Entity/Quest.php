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
  public $permanent; // Always available.
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
  
  // Protected
  protected $_location;
  protected $_kit;

  // Private vars
  static $fields_int = array('stars', 'created', 'reward_gold', 'reward_exp', 'reward_fame', 'duration', 'cooldown', 'party_size_min', 'party_size_max', 'level', 'success_rate', 'death_rate');
  static $db_table = 'quests';
  static $default_class = 'Quest';
  static $primary_key = 'qid';

  // Constants
  const FILENAME_QUEST_NAMES_ORIGINAL = '/bin/json/original/quest_names.json';
  const FILENAME_QUEST_NAMES = '/bin/json/quest_names.json';

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

  public function get_duration ($guild, $adventurers, $kit) {
    $duration = $this->duration;
    // Get quest duration modifier.
    $duration_mod = $guild->get_bonus()->get_mod(Bonus::QUEST_SPEED, $this);
    if (!empty($kit)) $duration_mod += $kit->get_bonus()->get_mod(Bonus::QUEST_SPEED, $this, Bonus::MOD_DIFF);
    foreach ($adventurers as $adventurer) $duration_mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_SPEED, $this, Bonus::MOD_DIFF);
    // Modify quest duration.
    $duration = ceil($duration * $duration_mod);

    // Load up the location for this Quest.
    $location = Location::load(array('locid' => $this->locid));
    if (!empty($location)) $duration += $location->get_duration($guild, $adventurers, $kit);
    return $duration;
  }

  public function get_success_rate ($guild, $adventurers, $kit) {
    $rate = $this->success_rate;

    // Adjust it based on the level of the adventurers vs. the level of the quest.
    $levels = 0;
    // Get the quest success rate from the Guild.
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_SUCCESS, $this, Bonus::MOD_HUNDREDS);
    if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::QUEST_SUCCESS, $this, Bonus::MOD_HUNDREDS);
    foreach ($adventurers as $adventurer) {
      $levels += $adventurer->level;
      $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_SUCCESS, $this, Bonus::MOD_HUNDREDS);
    }
      
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

  public function get_death_rate ($guild, $adventurers, $kit) {
    $rate = $this->death_rate;
    // Get the death rate modifier from the Guild.
    $mod = $guild->get_bonus()->get_mod(Bonus::DEATH_RATE, $this);
    if (!empty($kit)) $mod -= $kit->get_bonus()->get_mod(Bonus::DEATH_RATE, $this, Bonus::MOD_DIFF);
    // Check if adventurers modify the death rate at all.
    foreach ($adventurers as $adventurer) $mod -= $adventurer->get_bonus()->get_mod(Bonus::DEATH_RATE, $this, Bonus::MOD_DIFF);
    return ceil($rate * $mod);
  }

  public function get_reward_gold ($guild, $adventurers, $kit) {
    $gold = $this->reward_gold;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_GOLD, $this);
    if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::QUEST_REWARD_GOLD, $this, Bonus::MOD_DIFF);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_GOLD, $this, Bonus::MOD_DIFF);
    return ceil($gold * $mod);
  }

  public function get_reward_fame ($guild, $adventurers, $kit) {
    $fame = $this->reward_fame;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_FAME, $this);
    if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::QUEST_REWARD_FAME, $this, Bonus::MOD_DIFF);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_FAME, $this, Bonus::MOD_DIFF);
    return ceil($fame * $mod);
  }

  public function get_reward_exp ($guild, $adventurers, $kit) {
    $exp = $this->reward_exp;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_EXP, $this);
    if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::QUEST_REWARD_EXP, $this, Bonus::MOD_DIFF);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_EXP, $this, Bonus::MOD_DIFF);
    return ceil($exp * $mod);
  }

  public function get_reward_items ($guild, $adventurers, $kit) {
    $chance_of_item = 5;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_ITEM, $this);
    if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::QUEST_REWARD_ITEM, $this, Bonus::MOD_DIFF);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_ITEM, $this, Bonus::MOD_DIFF);
    // Check if any items were found.
    $items = array();
    if (rand(1, 100) <= $chance_of_item) {
      $rarity_min = ($this->stars - 1);
      $rarity_max = $this->stars;
      $item_probabilities = $this->get_item_probabilities($guild, $adventurers, $kit);
      // Generate an item to be found, with the rarity relating to the Quest star rating.
      $templates = ItemTemplate::random(1, $rarity_min, $rarity_max, array(), array(), $item_probabilities);
      // Create the items and assign them to the Guild.
      foreach ($templates as $template) {
        $item = $guild->add_item($template);
        if ($item != false) $items[] = $item;
      }
    }
    return $items;
  }

  public function get_item_probabilities ($guild, $adventurers, $kit) {
    $probs = ItemType::PROBABILITIES();
    // Loop through each ItemType and modify the probability.
    foreach ($probs as $type => $value) {
      $mod = $guild->get_bonus()->get_mod(Bonus::ITEM_TYPE_FIND_RATE, "ItemType->".$type, Bonus::MOD_DIFF);
      if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::ITEM_TYPE_FIND_RATE, "ItemType->".$type, Bonus::MOD_DIFF);
      foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::ITEM_TYPE_FIND_RATE, "ItemType->".$type, Bonus::MOD_DIFF);
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
    // Load the Guild this quest is for.
    $guild = Guild::load(array('gid' => $this->gid));
    // Load up the adventuring group.
    $advgroup = AdventuringGroup::load(array('agid' => $this->agid));
    // Get all the adventurers on this quest.
    $adventurers = Adventurer::load_multiple(array('agid' => $this->agid, 'gid' => $this->gid));
    $adv_count = count($adventurers);
    $kit = $this->get_kit();

    // Disband the adventuring group.
    $advgroup->delete();

    // Determine if the quest was successful.
    $success_rate = $this->get_success_rate($guild, $adventurers, $kit);
    $death_rate = $this->get_death_rate($guild, $adventurers, $kit);
    // Generate a number between 1-100 and see if it's successful.
    $success = (rand(1, 100) <= $success_rate);

    // If it's successful, give out the rewards.
    $reward_exp = 0;
    $quest_data = array('text' => array(), 'success' => $success, 'player' => $guild);
    $channel_data = array('text' => array());
    
    if ($success) {
      $quest_data['success_msg'] = 'SUCCESS!';
      $quest_data['text'][] = $this->name .' was completed.';
      $reward_gold = $this->get_reward_gold($guild, $adventurers, $kit);
      $reward_fame = $this->get_reward_fame($guild, $adventurers, $kit);
      $reward_items = $this->get_reward_items($guild, $adventurers, $kit);
      // Calculate the exp per adventurer.
      $reward_exp = ceil($this->get_reward_exp($guild, $adventurers, $kit) / count($adventurers));

      // Give the Guild its reward.
      if ($reward_gold > 0) {
        $guild->gold += $reward_gold;
        $quest_data['reward_gold'] = $reward_gold;
      }
      if ($reward_fame > 0) {
        $guild->fame += $reward_fame;
        $quest_data['reward_fame'] = $reward_fame;
      }
      $guild->save();

      if ($reward_exp > 0) {
        $quest_data['reward_exp'] = $reward_exp;
      }

      if (!empty($reward_items)) {
        $quest_data['reward_items'] = $reward_items;
      }
    }
    else {
      $quest_data['success_msg'] = 'FAIL...';
      $quest_data['text'][] = 'Your adventuring party failed to complete '. $this->name .'.';
    }

    // If this is an exploration quest, reveal the location.
    if ($this->type == Quest::TYPE_EXPLORE) {
      $location = $this->get_location();
      $location->revealed = true;
      $location->gid = $guild->gid;
      $location->save();

      // Regenerate the map now that a new location is revealed.
      $season = Season::current();
      $map = Map::load(array('season' => $season->sid));
      MapImage::generate_image($map);

      // Generate new Quests for the revealed location.
      // $quests = Quest::generate_quests($location);

      if (!empty($location->name)) {
        $quest_data['text'][] = "You discovered ".$location->get_display_name().".";
        $quest_data['text'][] = '';
        $channel_data['text'][] = $guild->get_display_name()." discovered ".$location->get_display_name().".";
        $channel_data['color'] = SlackAttachment::COLOR_GREEN;
      }
    }

    // Bring all the adventurers home and give them their exp.
    foreach ($adventurers as $adventurer) {
      $adventurer->agid = 0;
      if (isset($reward_exp) && $reward_exp > 0) {
        // Give the exp and if they leveled up, show a message.
        if ($adventurer->give_exp($reward_exp)) {
          $quest_data['text'][] = $adventurer->get_display_name().' is now level '.$adventurer->get_level(false).'!';
        }
      }
      // Calculate if the adventurer died during this adventure ONLY if they failed the quest.
      if (!$success && $death_rate > 0 && rand(1, 100) <= $death_rate) {
        $adventurer->dead = true;
        $quest_data['text'][] = ':rpg-tomb: RIP '.$adventurer->get_display_name().' died during the quest.';
      }
      $adventurer->save();
    }

    // Consume the kit item.
    if (!empty($kit)) $kit->delete();

    // Check if we need to reactivate this quest.
    $this->agid = 0;
    $this->gid = 0;
    $cooldown = 0;
    // Reactive if the quest is permanent or if the guild failed to complete it.
    if ($this->permanent || !$success) {
      // Create a temporary cooldown if it was a failed quest attempt.
      if (!$success) $cooldown = (60 * 60) * ($this->stars * rand(3, 6));
      // If it needs a cooldown before activation, remember it.
      else if (!empty($this->cooldown)) $cooldown = $this->cooldown;
      // If there's no cooldown, it's instantly active.
      if ($cooldown == 0) $this->active = true;
    }
    $this->save();

    // If a cooldown was set, we need to queue up the activation.
    if (!empty($cooldown)) $this->queue( $cooldown );


    // Get attachment to display for Quest.
    $quest_message = $this->get_quest_result_as_message($quest_data);
    $quest_message->text = 'Your adventurer'.($adv_count != 1 ? 's' : '').' '.($adv_count != 1 ? 'have' : 'has').' returned home from '.($this->type == Quest::TYPE_EXPLORE ? 'exploring' : 'questing').'.';

    if (!empty($channel_data['text'])) {
      $channel_message = $this->get_quest_channel_result_as_message($channel_data);
    }

    // Send out the messages.
    $result = array('messages' => array($quest_message));
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
      $field->title = 'Experience';
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
      $quest = Quest::generate_quest_type($location, $type, $save);
      $quests[] = $quest;
    }

    // Save JSON file.
    if ($save_json) Quest::save_quest_names_list($json);

    return $quests;
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
      'permanent' => false,
      'stars' => $stars,
      'created' => time(),
      'type' => $type,
      'active' => true,
      'cooldown' => 0,
    );

    // Generate the name and icon.
    $name_and_icon = Quest::generate_quest_name_and_icon($location, $type, $json, $original_json);
    $data['name'] = $name_and_icon['name'];
    $data['icon'] = $name_and_icon['icon'];

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
    if (!isset($data['reward_exp'])) $data['reward_exp'] = Quest::sum_multiple_randoms(($stars * $avg_party_size), 25, 75);
    if (!isset($data['reward_fame'])) $data['reward_fame'] = Quest::sum_multiple_randoms($stars, 3, 8);
    if (!isset($data['level'])) $data['level'] = Quest::sum_multiple_randoms(($avg_party_size * $stars), 1, 4);
    if (!isset($data['success_rate'])) $data['success_rate'] = 100 - Quest::sum_multiple_randoms($stars, 1, 4);

    // Calculate the rewards and information.
    switch ($type) {
      case Quest::TYPE_EXPLORE:
        $data['reward_gold'] = 0;
        $data['reward_exp'] = rand(5, 15);
        $data['reward_fame'] = 0;
        $data['duration'] = 0;
        $data['level'] = 0;
        $data['success_rate'] = 100;
        // Bonus reward if you discover a non-empty location.
        if ($location->type != Location::TYPE_EMPTY) {
          $data['reward_fame'] += $stars * 3;
          $data['reward_exp'] += rand(5, 15);
        }
        break;

      case Quest::TYPE_BOSS:
        $data['active'] = false;
        $data['cooldown'] = (3 * $days) - (5 * $hours); // 3 days less 5 hours.
        $data['party_size_min'] = 2;
        $data['party_size_max'] = $stars > 1 ? rand(3, 5) : $data['party_size_max'];
        $avg_party_size = ($data['party_size_max'] - $data['party_size_min'] / 2) + $data['party_size_min'];
        $data['reward_gold'] = $stars * rand(200, 400);
        $data['reward_exp'] = ($stars * $data['reward_exp']) + 50;
        $data['reward_fame'] = ($data['reward_fame'] * 3) + 5;
        $data['duration'] = (rand(4, 7) * $stars) * (24*$hours);
        $data['success_rate'] = $data['success_rate'] - rand(8, 15);
        break;

      case Quest::TYPE_FIGHT:
        $data['reward_exp'] += floor($data['reward_exp'] * 0.5);
        break;

      case Quest::TYPE_AID:
        $data['reward_fame'] += floor($data['reward_fame'] * 0.5);
        break;

      case Quest::TYPE_INVESTIGATE:
        $data['reward_gold'] += floor($data['reward_gold'] * 0.5);
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

  public static function types () {
    // Not included: Quest::TYPE_EXPLORE, Quest::TYPE_TRAIN
    return Quest::$_types;
  }

  protected static function generate_quest_name_and_icon ($location, $type, &$json, $original_json) {
    $info = array(
      'name' => '',
      'keywords' => array(),
      'icon' => ':pushpin:',
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

    // Randomly generate the name.
    $name_info = JSONList::generate_name($json_list, $original_json_list, $tokens);
    $info = array_merge($info, $name_info);

    // If we're supposed to save the JSON, do so now.
    if ($save_json) Location::save_location_names_list($json);

    return $info;
  }

  /**
   * Calculate multiple randomized numbers and add them together.
   */
  public static function sum_multiple_randoms ($num, $min, $max) {
    $value = 0;
    for ($i = 1; $i <= $num; $i++) $value += rand($min, $max);
    return $value;
  }

  public static function randomize_quest_types ($loc_type) {
    // Set probabilities based on location type.
    $loc_types = Quest::quest_probabilities();

    $list = array();
    if (!isset($loc_types[$loc_type])) return $list;

    // Populate a list full of the types based on the probability given.
    foreach ($loc_types[$loc_type] as $type => $prob) {
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

}