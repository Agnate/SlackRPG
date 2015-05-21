<?php

class Map extends RPGEntitySaveable {
  // Fields
  public $mapid;
  public $season;
  public $created;

  // Protected
  protected $locations;

  // Private vars
  static $fields_int = array('season', 'created');
  static $db_table = 'map';
  static $default_class = 'Map';
  static $primary_key = 'mapid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function load_locations () {
    $this->locations = Location::load_multiple(array('mapid' => $this->mapid));
  }

  public function get_locations () {
    if (empty($this->locations)) {
      $this->load_locations();
    }

    return $this->locations;
  }
}