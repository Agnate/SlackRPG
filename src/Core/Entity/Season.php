<?php

class Season extends RPGEntitySaveable {
  // Fields
  public $sid;
  public $created;
  public $duration;
  public $active;

  // Protected
  protected $_map;
  
  // Private vars
  static $fields_int = array('created', 'duration');
  static $db_table = 'seasons';
  static $default_class = 'Season';
  static $primary_key = 'sid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function load_map () {
    $this->_map = Map::load(array('season' => $this->sid));
    return $this->_map;
  }

  public function get_map () {
    if (empty($this->_map) && $this->_map !== FALSE) $this->load_map();
    return $this->_map;
  }



  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  /**
   * Get the current season.
   */
  public static function current () {
    $season = Season::load(array('active' => true));
    return $season;
  }
}