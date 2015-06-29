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

  public function get_display_name ($include_desc = true, $bold_name = true) {
    return ($bold_name ? '*' : ''). $this->name .($bold_name ? '*' : '') .($include_desc && !empty($this->description) ? ' ('. $this->description .')' : '');
  }

  public function load_requires () {
    $this->_requires = $this->__decode_requires($this->requires);
  }

  /**
   * Format for the array-version of the list of requirements:
   *
   * $list -> an array of Requirement objects.
   *
   * Examples:
   *    an upgrade requires 3 iron ore:
   *    $list = array(
   *      new Requirement (array('name_id => 'ore_iron', 'qty' => 3))
   *    );
   *
   *    an upgrade requires 1 iron ore and 2 steel ingots:
   *    $list = array(
   *      new Requirement (array('name_id => 'ore_iron', 'qty' => 1)),
   *      new Requirement (array('name_id => 'ore_steel', 'qty' => 2)),
   *    );
   */
  public function __encode_requires ($list) {
    if (empty($list)) return '';
    $items = array();

    // Loop through the list and encode it.
    foreach ($list as $requirement) {
      // Skip any item without a name_id.
      if (empty($requirement->name_id)) continue;
      $items[] = $requirement->encode();
    }

    return implode('|', $items);
  }

  /**
   * Format for the string-version of the list of requirements:
   *
   * $value -> "TYPE,NAME_ID,QTY|TYPE,NAME_ID,QTY"   (item separator = "|", divider between type, name_id, and quantity = ",")
   *
   * Examples:
   *    an upgrade requires 3 iron ore returns -->  "item,ore_iron,3"
   *    an upgrade requires 1 iron ore and 2 steel ingots returns -->  "item,ore_iron|item,ore_steel,2"
   *    an upgrade requires 2 iron ore and a previous upgrade equip1 returns -->  "item,ore_iron,2|upgrade,equip1"
   */
  public function __decode_requires ($requirements) {
    $list = array();
    if ($requirements == '') return $list;
    
    // Bust up the requirements by the primary separator.
    $requirements = explode('|', $requirements);

    // Sub-divide each requirement to find the name_id and quantity.
    foreach ($requirements as $value) {
      $list[] = Requirement::from($value);
    }

    return $list;
  }

  public function get_requires () {
    if (empty($this->_requires)) $this->load_requires();
    return $this->_requires;
  }

  /**
   * $type -> 'item', 'upgrade'
   */
  public function get_required_type ($type) {
    $requires = $this->get_requires();
    $item_requirements = array();
    foreach ($requires as $requirement) {
      if ($requirement->type != $type) continue;
      $item_requirements[] = $requirement;
    }
    return $item_requirements;
  }

  // public function get_required_items () {
  //   if (empty($this->_requires)) $this->load_requires();
  //   $item_requirements = array();
  //   foreach ($item_requirements as $requirement) {
  //     if ($requirement->type != 'item') continue;
  //     $item_requirements[] = $requirement;
  //   }
  //   return $item_requirements;
  // }

  public function queue_process ($queue = null) {
    // Can't process an upgrade without the $queue item.
    if (empty($queue)) return FALSE;

    // Load the Guild from the queue data.
    $guild = Guild::load(array('gid' => $queue->gid));
    if (empty($guild)) return FALSE;

    // Create message.
    $attachment = new SlackAttachment ();
    $message = new SlackMessage (array('player' => $guild));
    $message->text = 'Your upgrade has been processed.';
    $message->add_attachment($attachment);
    $attachment->color = SlackAttachment::COLOR_GREEN;
    $result = array('messages' => array($message));

    // Add the upgrade to the Guild.
    $guild->add_upgrade($this->name_id);
    $success = $guild->save();
    if ($success === false) {
      $attachment->text = 'There was an error saving your '.$this->get_display_name(false).' upgrade. Please talk to Paul.';
      $attachment->color = SlackAttachment::COLOR_RED;
      return $result;
    }

    $attachment->text = $this->get_display_name() .' upgrade is complete.';

    // If we were given a Queue, destroy it.
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