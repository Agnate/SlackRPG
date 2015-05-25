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
}