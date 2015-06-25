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
  
  // Protected
  protected $_location;

  // Private vars
  static $fields_int = array('stars', 'created', 'reward_gold', 'reward_exp', 'reward_fame', 'duration', 'cooldown', 'party_size_min', 'party_size_max', 'level', 'success_rate', 'death_rate');
  static $db_table = 'quests';
  static $default_class = 'Quest';
  static $primary_key = 'qid';

  // Constants
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

  public function get_party_size () {
    return $this->party_size_min .($this->party_size_max > 0 && $this->party_size_max != $this->party_size_min ? '-'.$this->party_size_max : '');
  }

  public function get_duration ($guild, $adventurers) {
    $duration = $this->duration;
    // Get quest duration modifier.
    $duration_mod = $guild->get_bonus()->get_mod(Bonus::QUEST_SPEED, $this);
    foreach ($adventurers as $adventurer) $duration_mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_SPEED, $this, Bonus::MOD_DIFF);
    // Modify quest duration.
    $duration = ceil($duration * $duration_mod);

    // Load up the location for this Quest.
    $location = Location::load(array('locid' => $this->locid));
    if (!empty($location)) $duration += $location->get_duration($guild, $adventurers);
    return $duration;
  }

  public function get_success_rate ($guild, $adventurers) {
    $rate = $this->success_rate;

    // Adjust it based on the level of the adventurers vs. the level of the quest.
    $levels = 0;
    // Get the quest success rate from the Guild.
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_SUCCESS, $this, Bonus::MOD_HUNDREDS);
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

  public function get_death_rate ($guild, $adventurers) {
    $rate = $this->death_rate;
    // Get the death rate modifier from the Guild.
    $mod = $guild->get_bonus()->get_mod(Bonus::DEATH_RATE, $this);
    // Check if adventurers modify the death rate at all.
    foreach ($adventurers as $adventurer) $mod -= $adventurer->get_bonus()->get_mod(Bonus::DEATH_RATE, $this, Bonus::MOD_DIFF);
    return ceil($rate * $mod);
  }

  public function get_reward_gold ($guild, $adventurers) {
    $gold = $this->reward_gold;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_GOLD, $this);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_GOLD, $this, Bonus::MOD_DIFF);
    return ceil($gold * $mod);
  }

  public function get_reward_fame ($guild, $adventurers) {
    $fame = $this->reward_fame;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_FAME, $this);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_FAME, $this, Bonus::MOD_DIFF);
    return ceil($fame * $mod);
  }

  public function get_reward_exp ($guild, $adventurers) {
    $exp = $this->reward_exp;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_EXP, $this);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_EXP, $this, Bonus::MOD_DIFF);
    return ceil($exp * $mod);
  }

  public function get_reward_items ($guild, $adventurers) {
    $chance_of_item = 5;
    $mod = $guild->get_bonus()->get_mod(Bonus::QUEST_REWARD_ITEM, $this);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::QUEST_REWARD_ITEM, $this, Bonus::MOD_DIFF);
    // Check if any items were found.
    $rarity_min = ($this->stars - 1);
    $rarity_max = $this->stars;
    if (rand(1, 100) <= $chance_of_item) {
      // Generate an item to be found, with the rarity relating to the Quest star rating.
      $templates = ItemTemplate::random(1, $rarity_min, $rarity_max);
      $items = array();
      // Create the items and assign them to the Guild.
      foreach ($templates as $template) {
        $item = $guild->add_item($template);
        if ($item != false) $items[] = $item;
      }
    }
    return $items;
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

    // Disband the adventuring group.
    $advgroup->delete();

    // Determine if the quest was successful.
    $success_rate = $this->get_success_rate($guild, $adventurers);
    $death_rate = $this->get_death_rate($guild, $adventurers);
    // Generate a number between 1-100 and see if it's successful.
    $success = (rand(1, 100) <= $success_rate);

    // If it's successful, give out the rewards.
    $reward_exp = 0;
    $player_text = array();
    if ($success) {
      $player_text[] = '*'. $this->name .'* was completed!';
      $reward_gold = $this->get_reward_gold($guild, $adventurers);
      $reward_fame = $this->get_reward_fame($guild, $adventurers);
      $reward_items = $this->get_reward_items($guild, $adventurers);
      // Calculate the exp per adventurer.
      $reward_exp = ceil($this->get_reward_exp($guild, $adventurers) / count($adventurers));

      if ($reward_gold > 0 || $reward_fame > 0 || $reward_exp > 0) {
        $player_text[] = '';
        $player_text[] = 'You receive:';
      }

      // Give the Guild its reward.
      if ($reward_gold > 0) {
        $guild->gold += $reward_gold;
        $player_text[] = Display::get_currency($reward_gold);
      }
      if ($reward_fame > 0) {
        $guild->fame += $reward_fame;
        $player_text[] = Display::get_fame($reward_fame);
      }
      $guild->save();

      if ($reward_exp > 0) {
        $player_text[] = $reward_exp.' experience points (divided among the participating adventurers)';
      }

      $player_text[] = '';
    }
    else {
      $player_text[] = 'Your adventuring party *FAILED* '. $this->name .'.';
    }

    // Bring all the adventurers home and give them their exp.
    foreach ($adventurers as $adventurer) {
      $adventurer->agid = 0;
      if (isset($reward_exp) && $reward_exp > 0) {
        // Give the exp and if they leveled up, show a message.
        if ($adventurer->give_exp($reward_exp)) {
          $player_text[] = $adventurer->get_display_name().' is now level '.$adventurer->get_level(false).'!';
        }
      }
      // Calculate if the adventurer died during this adventure ONLY if they failed the quest.
      if (!$success && $death_rate > 0 && rand(1, 100) <= $death_rate) {
        $adventurer->dead = true;
        $player_text[] = ':rpg-tomb: RIP '.$adventurer->get_display_name().' died during the quest.';
      }
      $adventurer->save();
    }

    $channel_text = array();

    // If this is an exploration quest, reveal the location.
    if ($this->type == Quest::TYPE_EXPLORE) {
      $location = $this->get_location();
      $location->revealed = true;
      $location->gid = $guild->gid;
      $location->save();

      // Generate new Quests for the revealed location.
      // $quests = Quest::generate_quests($location);

      if (!empty($location->name)) {
        $player_text[] = " You discovered ".$location->name.".";
        $channel_text[] = $guild->get_display_name()." discovered ".$location->name.".";
      }
    }

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

    $result = array(
      'messages' => array(),
    );
    if (isset($player_text) && !empty($player_text)) {
      $result['messages']['instant_message'] = array(
        'text' => implode("\n", $player_text),
        'player' => $guild,
      );
    }
    if (isset($channel_text) && !empty($channel_text)) $result['messages']['channel'] = array('text' => implode("\n", $channel_text));
    return $result;
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
  static function generate_quests ($location, $save = true) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    $quests = array();
    $num_quests = rand(1, 3) + 1;
    // For now, generate a number of quests = star rating.
    for ($i = 0; $i < $num_quests; $i++) {
      // Determine the type.
      $type = Quest::randomize_quest_types($location->type);
      // Generate the quest.
      $quest = Quest::generate_quest_type($location, $type, $save);
      $quests[] = $quest;
    }

    return $quests;
  }

  static function generate_quest_type ($location, $type, $save = true) {
    if (empty($location) || !is_a($location, 'Location')) return false;

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
    $data['name'] = 'Test Quest';
    $data['icon'] = ':pushpin:';

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
      if ($cooldown > 0 && !$quest->active) $quest->queue($cooldown);
    }

    return $quest;
  }

  static function types () {
    // Not included: Quest::TYPE_EXPLORE, Quest::TYPE_TRAIN
    return Quest::$_types;
  }

  /**
   * Calculate multiple randomized numbers and add them together.
   */
  static function sum_multiple_randoms ($num, $min, $max) {
    $value = 0;
    for ($i = 1; $i <= $num; $i++) $value += rand($min, $max);
    return $value;
  }

  static function randomize_quest_types ($loc_type) {
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

  static function quest_probabilities () {
    // Set probabilities based on location type.
    $types = array();
    $types[Location::TYPE_CREATURE] = array(
      Quest::TYPE_FIGHT => 0.45,
      Quest::TYPE_BOSS => 0.15,
      Quest::TYPE_INVESTIGATE => 0.30,
      Quest::TYPE_AID => 0.05,
      Quest::TYPE_SPECIAL => 0.05,
    );

    $types[Location::TYPE_STRUCTURE] = array(
      Quest::TYPE_FIGHT => 0.10,
      Quest::TYPE_BOSS => 0.02,
      Quest::TYPE_INVESTIGATE => 0.25,
      Quest::TYPE_AID => 0.50,
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

}