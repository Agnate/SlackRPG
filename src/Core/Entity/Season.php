<?php

class Season extends RPGEntitySaveable {
  // Fields
  public $sid;
  public $created;
  public $duration;
  public $active;
  
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

  /**
   * Get the current season.
   */
  public static function current () {
    $season = Season::load(array('active' => true));
    return $season;
  }
}