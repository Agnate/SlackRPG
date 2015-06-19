<?php

class Upgrade extends RPGEntitySaveable {
  // Fields
  public $upid;
  public $name_id;
  public $name;
  public $description;
  public $cost;
  public $duration;
  public $requires;

  // Protected
  protected $_requires;

  // Private vars
  static $fields_int = array('cost', 'duration');
  static $db_table = 'upgrades';
  static $default_class = 'Upgrade';
  static $primary_key = 'upid';

  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Convert requirements.
    $this->load_requires();
  }

  public function get_display_name ($include_desc = true) {
    return $this->name .($include_desc && !empty($this->description) ? ' ('. $this->description .')' : '');
  }

  public function load_requires () {
    if ($this->requires == '') $this->_requires = array();
    else $this->_requires = explode(',', $this->requires);
  }

  protected function _update_upgrades_to_string () {
    $this->requires = implode(',', $this->_requires);
  }

  public function get_requires () {
    if (empty($this->_requires)) $this->load_requires();
    return $this->_requires;
  }

  public function queue_process ($queue = null) {
    // Can't process an upgrade without the $queue item.
    if (empty($queue)) return FALSE;

    // Load the Guild from the queue data.
    $guild = Guild::load(array('gid' => $queue->gid));
    if (empty($guild)) return FALSE;

    $result = array(
      'messages' => array(
        'instant_message' => array(
          'text' => '',
          'player' => $guild,
        ),
      ),
    );

    // Add the upgrade to the Guild.
    $guild->add_upgrade($this->name_id);
    $success = $guild->save();
    if ($success === false) {
      $result['messages']['instant_message']['text'] = 'There was an error saving your *'.$this->get_display_name(false).'* upgrade. Please talk to Paul.';
      return $result;
    }

    $result['messages']['instant_message']['text'] = '*'. $this->get_display_name() .'* upgrade is complete.';

    // If we were give a Queue, destroy it.
    if (!empty($queue)) $queue->delete();

    return $result;
  }

  public function apply_bonus ($guild) {
    if (empty($guild)) return FALSE;

    $bonus = $guild->get_bonus();

    switch ($this->name_id) {
      case 'speed1':
      case 'speed2':
        $bonus->add_mod(Bonus::TRAVEL_SPEED, -0.05);
        break;

      case 'speed3':
        $bonus->add_mod(Bonus::TRAVEL_SPEED, -0.10);
        break;

      case 'dorm1':
        $guild->adventurer_limit = 5;
        break;

      case 'dorm2':
        $guild->adventurer_limit = 7;
        break;

      case 'dorm3':
        $guild->adventurer_limit = 10;
        break;

      case 'equip1':
      case 'equip2':
      case 'equip3':
      case 'equip4':
      case 'equip5':
      case 'equip6':
      case 'equip7':
      case 'equip8':
        $bonus->add_mod(Bonus::QUEST_SUCCESS, 0.02);
        break;

      case 'heal1':
      case 'heal2':
        $bonus->add_mod(Bonus::DEATH_RATE, -0.02);
        break;
    }

    return TRUE;
  }

}