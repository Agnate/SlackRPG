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
  public $location;
  public $duration;
  public $cooldown;
  public $requirements;
  public $party_size;

  // Private vars
  static $fields_int = array('created', 'reward_gold', 'reward_exp', 'reward_fame', 'duration', 'cooldown', 'party_size');
  static $db_table = 'quests';
  static $default_class = 'Quest';
  static $primary_key = 'qid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    if (empty($this->party_size)) $this->party_size = 1;
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

    return array(
      'player' => $guild,
      'text' => $this->name .' was completed!',
    );
  }

  protected function queue_process_reactivate ($queue = null) {
    $this->agid = 0;
    $this->active = true;
    $this->save();
  }
}