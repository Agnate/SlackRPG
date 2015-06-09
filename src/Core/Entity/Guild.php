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

  // Protected
  protected $_adventurers;
  protected $_renown;

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