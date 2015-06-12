<?php

class Adventurer extends RPGEntitySaveable {
  // Fields
  public $aid;
  public $gid; // Guild they are in.
  public $agid; // Adventuring Group they are in.
  public $name;
  public $icon;
  public $created;
  public $available;
  public $level;
  public $popularity;
  public $exp;
  public $exp_tnl;
  public $class;
  public $champion;
  public $dead;

  // Protected
  protected $_death_rate_modifier;

  // Private vars
  static $fields_int = array('created', 'level', 'popularity', 'exp', 'exp_tnl');
  static $db_table = 'adventurers';
  static $default_class = 'Adventurer';
  static $primary_key = 'aid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    if (empty($this->available)) $this->available = false;
    if (empty($this->champion)) $this->champion = false;
    if (empty($this->_death_rate_modifier)) $this->_death_rate_modifier = 1;
  }

  public function get_display_name ($bold = true, $include_champion = true) {
    return ($this->champion ? ':crown:' : '').$this->icon.' '.($bold ? '*' : '').$this->name.($bold ? '*' : '');
  }

  public function give_exp ($exp) {
    $this->exp += $exp;
  }

  public function get_death_rate_modifier () {
    return $this->_death_rate_modifier;
  }

  /**
   * $mod -> Should be a decimal representation of a percentage (example: 0.2 for 20%).
   */
  // public function set_death_rate_modifier ($mod) {
  //   $this->_death_rate_modifier = $mod;
  // }

  /**
   * $mod -> Should be a decimal representation of a percentage (example: 0.2 for 20%).
   */
  // public function add_death_rate_modifier ($mod) {
  //   $this->_death_rate_modifier += $mod;
  // }
}