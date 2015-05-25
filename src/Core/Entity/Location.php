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

  // Protected
  protected $_map;

  // Private vars
  static $fields_int = array('created', 'row', 'col');
  static $db_table = 'locations';
  static $default_class = 'Location';
  static $primary_key = 'locid';

  const TYPE_EMPTY = 'empty';
  const TYPE_CAPITAL = 'capital';

  const TRAVEL_MODIFIER = 5; // 10800 = 3 hours/tile (60 * 60 * 3)

  
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
    if (empty($travel_modifier) && $travel_modifier !== 0) $travel_modifier = Location::TRAVEL_MODIFIER;
    return sqrt(pow(($capital->row - $this->row), 2) + pow(($capital->col - $this->col), 2)) * $travel_modifier;
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
}