<?php

class Map extends RPGEntitySaveable {
  // Fields
  public $mapid;
  public $season;
  public $created;
  
  // Protected
  protected $_locations;
  protected $_capital;

  // Private vars
  static $fields_int = array('season', 'created');
  static $db_table = 'maps';
  static $default_class = 'Map';
  static $primary_key = 'mapid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function load_locations () {
    $this->_locations = Location::load_multiple(array('mapid' => $this->mapid));
  }

  public function get_locations () {
    if (empty($this->_locations)) {
      $this->load_locations();
    }

    return $this->_locations;
  }

  public function load_capital () {
    $this->_capital = Location::load(array('mapid' => $this->mapid, 'type' => Location::TYPE_CAPITAL));
  }

  public function get_capital () {
    if (empty($this->_capital)) {
      $this->load_capital();
    }

    return $this->_capital;
  }
}