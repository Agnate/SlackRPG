<?php

class Location extends RPGEntitySaveable {
  // Fields
  public $locid;
  public $mapid;
  public $gid; // Guild who revealed it.
  public $name;
  public $row;
  public $col;
  public $type;
  public $created;
  public $revealed;
  public $star_min;
  public $star_max;

  // Protected
  protected $_map;

  // Private vars
  static $fields_int = array('created', 'row', 'col', 'star_min', 'star_max');
  static $db_table = 'locations';
  static $default_class = 'Location';
  static $primary_key = 'locid';

  const TYPE_EMPTY = 'empty';
  const TYPE_CAPITAL = 'capital';
  const TYPE_CREATURE = 'creature';
  const TYPE_STRUCTURE = 'structure';
  const TYPE_LANDMARK = 'landmark';

  static $_types = array(Location::TYPE_CREATURE, Location::TYPE_STRUCTURE, Location::TYPE_LANDMARK);

  const TRAVEL_BASE = 10800; // 10800 = 3 hours/tile (60 * 60 * 3)

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function get_display_name () {
    return '['.$this->get_coord_name().']'.(!empty($this->name) ? ' '.$this->name : '');
  }

  public function get_coord_name () {
    return Location::get_letter($this->col) .$this->row;
  }

  public function get_duration ($travel_modifier = null) {
    // Get the map so we can find the town location.
    $map = $this->get_map();
    // Get the capital in the map.
    $capital = $map->get_capital();
    // Calculate the raw distance and multiply by a time constant.
    $travel_per_tile = Location::TRAVEL_BASE;
    if (!empty($travel_modifier)) $travel_per_tile = floor($travel_per_tile * $travel_modifier);
    return ceil(sqrt(pow(($capital->row - $this->row), 2) + pow(($capital->col - $this->col), 2)) * $travel_per_tile);
  }

  public function load_map () {
    $this->_map = Map::load(array('mapid' => $this->mapid));
  }

  public function get_map () {
    // Load the Map if it hasn't been loaded.
    if ( empty($this->_map) ) {
      $this->load_map();
    }

    return $this->_map;
  }

  public static function get_letter ($num) {
    return chr(64 + $num);
  }

  public static function get_number ($letter) {
    return ord(strtoupper($letter)) - 64;
  }


  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  static function types () {
    return Location::$_types;
  }
}