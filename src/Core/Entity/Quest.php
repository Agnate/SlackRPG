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

  // Protected
  protected $_location;

  // Private vars
  static $fields_int = array('created', 'reward_gold', 'reward_exp', 'reward_fame', 'duration', 'cooldown', 'party_size_min', 'party_size_max');
  static $db_table = 'quests';
  static $default_class = 'Quest';
  static $primary_key = 'qid';

  // Constants
  const TYPE_EXPLORE = 'explore';
  const TYPE_TRAIN = 'train';
  const TYPE_INVESTIGATE = 'investigate';
  const TYPE_AID = 'aid';
  const TYPE_FIGHT = 'fight';
  const TYPE_BOSS = 'boss';

  static $_types = array(Quest::TYPE_INVESTIGATE, Quest::TYPE_AID, Quest::TYPE_FIGHT, Quest::TYPE_BOSS);

  
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

  public function get_duration () {
    $duration = $this->duration;
    // Load up the location for this Quest.
    $location = Location::load(array('locid' => $this->locid));
    if (!empty($location)) {
      $duration += $location->get_duration();
    }
    return $duration;
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

    // Give the Guild its reward.
    if ($this->reward_gold > 0) $guild->gold += $this->reward_gold;
    if ($this->reward_fame > 0) $guild->fame += $this->reward_fame;
    $guild->save();

    // Calculate the exp per adventurer.
    $reward_exp = ceil($this->reward_exp / count($adventurers));

    // Bring all the adventurers home and give them their exp.
    foreach ($adventurers as $adventurer) {
      $adventurer->agid = 0;
      if ($reward_exp > 0) $adventurer->give_exp( $reward_exp );
      $adventurer->save();
    }

    $player_text = $this->name .' was completed!';
    $channel_text = '';

    // If this is an exploration quest, reveal the location.
    if ($this->type == Quest::TYPE_EXPLORE) {
      $location = $this->get_location();
      $location->revealed = true;
      $location->gid = $guild->gid;
      $location->save();

      // Generate new Quests for the revealed location.
      // $star_min = 1;
      // $star_max = 2;
      // $quests = Quest::generate_quests($location, $star_min, $star_max);

      if (!empty($location->name)) {
        $player_text .= " You discovered ".$location->name.".";
        $channel_text .= $guild->get_display_name()." discovered ".$location->name.".";
      }
    }

    // Check if we need to reactivate this quest.
    $this->agid = 0;
    $this->gid = 0;
    $cooldown = 0;
    if ($this->permanent) {
      // If it needs a cooldown before activation, remember it.
      if (!empty($this->cooldown)) {
        $cooldown = $this->cooldown;
      }
      else {
        $this->active = true;
      }
    }
    $this->save();

    // If a cooldown was set, we need to queue up the activation.
    if (!empty($cooldown)) $this->queue( $cooldown );

    $result = array(
      'messages' => array(),
    );
    if (isset($player_text) && !empty($player_text)) {
      $result['messages']['instant_message'] = array(
        'text' => $player_text,
        'player' => $guild,
      );
    }
    if (isset($channel_text) && !empty($channel_text)) $result['messages']['channel'] = array('text' => $channel_text);
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
  static function generate_quests ($location) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    $quests = array();
    $num_quests = rand(1, 3) + 1;
    // For now, generate a number of quests = star rating.
    for ($i = 0; $i < $num_quests; $i++) {
      // Determine the type.
      $type = Quest::randomize_quest_types($location->type);
      // Generate the quest.
      $quest = Quest::generate_quest_type($location, $type);
      $quests[] = $quest;
    }

    return $quests;
  }

  static function generate_quest_type ($location, $type) {
    if (empty($location) || !is_a($location, 'Location')) return false;

    // Determine the star rating.
    $stars = rand($location->star_min, $location->star_max);

    $data = array(
      'locid' => $location->locid,
      'permanent' => false,
      'created' => time(),
      'type' => $type,
      'active' => true,
      'cooldown' => 0,
    );

    // Generate the name and icon.
    $data['name'] = 'Test Quest';
    $data['icon'] = ':test:';

    // Set some defaults.
    $data['party_size_min'] = 1;
    $data['party_size_max'] = 3;
    $data['duration'] = (rand(2, 4) * $stars) * (60*60*24);
    $data['reward_gold'] = $stars * rand(50, 250);
    $data['reward_exp'] = ($stars * $data['party_size_max']) * rand(25, 75);
    $data['reward_fame'] = $stars * rand(3, 8);
    
    // Calculate the rewards and information.
    switch ($type) {
      case Quest::TYPE_EXPLORE:
        $data['reward_gold'] = 0;
        $data['reward_exp'] = rand(5, 15);
        $data['reward_fame'] = 0;
        $data['duration'] = 0;
        // Bonus reward if you discover a non-empty location.
        if ($location->type != Location::TYPE_EMPTY) {
          $data['reward_fame'] += $stars * 3;
          $data['reward_exp'] += rand(5, 15);
        }
        break;

      case Quest::TYPE_BOSS:
        $data['active'] = false;
        $data['cooldown'] = (3 * 60 * 60 * 23); // 3 days less 3 hours.
        $data['party_size_min'] = 2;
        $data['party_size_max'] = rand(3, 5);
        $data['reward_gold'] = $stars * rand(200, 400);
        $data['reward_exp'] = ($stars * $data['reward_exp']) + 50;
        $data['reward_fame'] = ($data['reward_fame'] * 3) + 5;
        $data['duration'] = (rand(4, 7) * $stars) * (60*60*24);
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
    /*$quest->save();

    // Queue up the cooldown if we need to.
    if ($data['active'] == false) {
      $quest->queue( $cooldown );
    }*/

    return $quest;
  }

  static function types () {
    // Not included: Quest::TYPE_EXPLORE, Quest::TYPE_TRAIN
    return Quest::$_types;
  }

  static function randomize_quest_types ($loc_type) {
    // Set probabilities based on location type.
    $loc_types = Quest::quest_probabilities();

    $list = array();
    if (!isset($loc_types[$loc_type])) return $list;

    foreach ($loc_types[$loc_type] as $type => $prob) {
      $count = $prob * 1000;
      for ($i = 0; $i < $count; $i++) {
        $list[] = $type;
      }
    }

    $index = array_rand($list);

    return $list[$index];
  }

  static function quest_probabilities () {
    // Set probabilities based on location type.
    $types = array();
    $types[Location::TYPE_CREATURE] = array(
      Quest::TYPE_FIGHT => 0.45,
      Quest::TYPE_BOSS => 0.15,
      Quest::TYPE_INVESTIGATE => 0.35,
      Quest::TYPE_AID => 0.05,
    );

    $types[Location::TYPE_STRUCTURE] = array(
      Quest::TYPE_FIGHT => 0.10,
      Quest::TYPE_BOSS => 0.05,
      Quest::TYPE_INVESTIGATE => 0.25,
      Quest::TYPE_AID => 0.60,
    );

    $types[Location::TYPE_LANDMARK] = array(
      Quest::TYPE_FIGHT => 0.12,
      Quest::TYPE_BOSS => 0.08,
      Quest::TYPE_INVESTIGATE => 0.75,
      Quest::TYPE_AID => 0.05,
    );

    return $types;
  }

}