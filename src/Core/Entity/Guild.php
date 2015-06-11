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
  protected $_travel_speed_modifier;
  protected $_quest_success_modifier;

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
    if (empty($this->_travel_speed_modifier)) $this->_travel_speed_modifier = 1;
    if (empty($this->_quest_success_modifier)) $this->_quest_success_modifier = 1;

    // Convert upgrades.
    $this->load_upgrades();

    // Apply upgrade bonuses.
    $this->__apply_upgrade_bonuses();
  }

  public function get_display_name ($bold = true) {
    return $this->icon.' '.($bold ? '*' : '').$this->name.($bold ? '*' : '');
  }

  protected function __apply_upgrade_bonuses () {
    if (empty($this->_upgrades)) $this->load_upgrades();
    // Apply bonuses from Upgrades to this Guild.
    foreach ($this->_upgrades as $upgrade) {
      $upgrade->apply_bonus($this);
    }
  }

  public function load_adventurers () {
    // Get all adventurers in this Guild.
    $this->_adventurers = Adventurer::load_multiple( array('gid' => $this->gid) );
  }

  public function get_adventurers () {
    // Load the adventurers if they haven't been loaded.
    if (empty($this->_adventurers)) $this->load_adventurers();
    return $this->_adventurers;
  }

  public function get_adventurers_count () {
    return count($this->get_adventurers());
  }

  public function get_best_adventurers ($count, $only_available = true) {
    if (empty($this->_adventurers)) $this->load_adventurers();
    $by_level = array();
    foreach ($this->_adventurers as $adventurer) {
      if (!isset($by_level[$adventurer->level])) $by_level[$adventurer->level] = array();
      $by_level[$adventurer->level][] = $adventurer;
    }
    // Sort highest levels to the top.
    krsort($by_level, SORT_NATURAL);
    $adventurers = array();
    foreach ($by_level as $key => $list) {
      foreach ($list as $adventurer) {
        $adventurers[] = $adventurer;
        $count--;
        if (!$count) break 2;
      }
    }
    return $adventurers;
  }

  public function get_best_adventurers_level ($count, $only_available = true) {
    if (empty($this->_adventurers)) $this->load_adventurers();
    // Get list of adventurers.
    $adventurers = $this->get_best_adventurers($count, $only_available);
    $total = 0;
    foreach ($adventurers as $adventurer) $total += $adventurer->level;
    return $total;
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
    $this->__update_upgrades_to_string();
  }

  protected function __update_upgrades_to_string () {
    $this->upgrades = implode(',', array_keys($this->_upgrades));
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
    $this->__update_upgrades_to_string();
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

  public function has_upgrade ($upgrade) {
    $upgrade_name = is_string($upgrade) ? $upgrade : $upgrade->name_id;
    if (empty($upgrade_name)) return FALSE;
    if (empty($this->_upgrades)) $this->load_upgrades();
    return in_array($upgrade_name, array_keys($this->_upgrades));
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
      if (!$this->has_upgrade($upgrade) && $this->meets_requirement($upgrade)) continue;
      // Remove anything that cannot be upgraded now.
      unset($all[$key]);
    }

    return $all;
  }

  public function get_travel_speed_modifier () {
    return $this->_travel_speed_modifier;
  }

  /**
   * $mod -> Should be a decimal representation of a percentage (example: 0.2 for 20%).
   */
  public function set_travel_speed_modifier ($mod) {
    $this->_travel_speed_modifier = $mod;
  }

  /**
   * $mod -> Should be a decimal representation of a percentage (example: 0.2 for 20%).
   */
  public function add_travel_speed_modifier ($mod) {
    $this->_travel_speed_modifier += $mod;
  }


  public function get_quest_success_modifier ($as_hundreds = false) {
    if ($as_hundreds) return floor(($this->_quest_success_modifier - 1) * 100);
    return $this->_quest_success_modifier;
  }

  /**
   * $mod -> Should be a decimal representation of a percentage (example: 0.2 for 20%).
   */
  public function set_quest_success_modifier ($mod) {
    $this->_quest_success_modifier = $mod;
  }

  /**
   * $mod -> Should be a decimal representation of a percentage (example: 0.2 for 20%).
   */
  public function add_quest_success_modifier ($mod) {
    $this->_quest_success_modifier += $mod;
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