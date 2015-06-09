<?php

class Guild extends RPGEntitySaveable {
  // Fields
  public $gid;
  public $username;
  public $slack_user_id;
  public $name;
  public $icon;
  public $created;
  public $updated;
  public $gold;
  public $fame;
  public $adventurer_limit;
  public $upgrades;

  // Protected
  protected $_adventurers;
  protected $_renown;
  protected $_upgrades;

  // Private vars
  static $fields_int = array('created', 'updated', 'gold', 'fame', 'adventurer_limit');
  static $db_table = 'guilds';
  static $default_class = 'Guild';
  static $primary_key = 'gid';

  const DEFAULT_ADVENTURER_LIMIT = 3;
  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    // Add default adventurer limit.
    if (empty($this->adventurer_limit)) $this->adventurer_limit = Guild::DEFAULT_ADVENTURER_LIMIT;
    if (empty($this->_renown)) $this->_renown = -9999;

    // Convert upgrades.
    $this->load_upgrades();
  }

  public function get_display_name ($bold = true) {
    return $this->icon.' '.($bold ? '*' : '').$this->name.($bold ? '*' : '');
  }

  public function load_adventurers () {
    // Get all adventurers in this Guild.
    $this->_adventurers = Adventurer::load_multiple( array('gid' => $this->gid) );
  }

  public function get_adventurers () {
    // Load the adventurers if they haven't been loaded.
    if ( empty($this->_adventurers) ) {
      $this->load_adventurers();
    }

    return $this->_adventurers;
  }

  public function get_adventurers_count () {
    return count($this->get_adventurers());
  }

  public function get_total_points ($force_calculation = false) {
    if (!$force_calculation && $this->_renown !== -9999) return $this->_renown;

    // Add up all the renown.
    $this->_renown = $this->fame;
    $adventurers = $this->get_adventurers();
    foreach ($adventurers as $adventurer) {
      $this->_renown += $adventurer->popularity;
    }

    return $this->_renown;
  }

  public function load_upgrades () {
    $list = explode(',', $this->upgrades);
    $this->_upgrades = array();

    // Load up all the Upgrade items.
    foreach ($list as $upgrade_name) {
      $this->__add_upgrade($upgrade_name, false);
    }

    // Re-string the upgrade list to weed out errors.
    $this->_update_upgrades_to_string();
  }

  protected function _update_upgrades_to_string () {
    if ($this->upgrades == '') $this->_upgrades = array();
    else $this->upgrades = implode(',', array_keys($this->_upgrades));
  }

  public function get_upgrades () {
    if (empty($this->_upgrades)) $this->load_upgrades();
    return $this->_upgrades;
  }

  public function add_upgrade ($upgrade_name) {
    if (empty($upgrade_name)) return FALSE;
    if (empty($this->_upgrades)) $this->load_upgrades();
    // Add the upgrade to the list.
    $this->__add_upgrade($upgrade_name);
    // Refresh the string to save to the db.
    $this->_update_upgrades_to_string();
    return FALSE;
  }

  protected function __add_upgrade ($upgrade_name, $check_existing = true) {
    // Check if this upgrade even exists.
    $upgrade = Upgrade::load(array('name_id' => $upgrade_name));
    if (empty($upgrade)) return FALSE;
    // Check if the upgrade is already in the list.
    if ($check_existing && in_array($upgrade_name, array_keys($this->_upgrades))) return FALSE;
    // Add the upgrade.
    $this->_upgrades[$upgrade->name_id] = $upgrade;
    return $upgrade;
  }

  public function meets_requirement ($upgrade) {
    // Check that ALL of the requirements are met.
    $requires = $upgrade->get_requires();
    if (empty($requires)) return TRUE;

    $keys = array_keys($this->_upgrades);
    foreach ($requires as $upgrade_name) {
      if (!in_array($upgrade_name, $keys)) return FALSE;
    }

    return TRUE;
  }

  public function get_available_upgrades () {
    // Load all Upgrades.
    $all = Upgrade::load_multiple(array());

    // Weed out the upgrades that aren't available
    foreach ($all as $key => $upgrade) {
      // If they can upgrade to this, keep it.
      if ($this->meets_requirement($upgrade)) continue;
      // Remove anything that cannot be upgraded now.
      unset($all[$key]);
    }

    return $all;
  }


  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  static function load ( $data, $find_partials = false, $load_adventurers = false ) {
    // Load the Guild.
    $guild = parent::load( $data, $find_partials );

    // Load the inventory.
    if ( $load_adventurers && !empty($guild) ) {
      // Get the inventory.
      $guild->load_adventurers();
    }

    return $guild;
  }

  static function sort ($a, $b) {
    if ($a->get_total_points() == $b->get_total_points()) {
      if ($a->gold == $b->gold) {
        if ($a->created == $b->created) return 0;
        return ($a->created < $b->created) ? -1 : 1;
      }
      return ($a->gold > $b->gold) ? -1 : 1;
    }
    return ($a->get_total_points() > $b->get_total_points()) ? -1 : 1;
  }
}