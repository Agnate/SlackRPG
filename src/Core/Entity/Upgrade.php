<?php

class Upgrade extends RPGEntitySaveable {
  // Fields
  public $upid;
  public $name_id;
  public $name;
  public $cost;
  public $duration;
  public $requires;

  // Protected
  protected $_requires;

  // Private vars
  static $fields_int = array('cost', 'duration');
  static $db_table = 'upgrades';
  static $default_class = 'Upgrade';
  static $primary_key = 'upid';

  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Convert requirements.
    $this->load_requires();
  }

  public function get_display_name () {
    return $this->name;
  }

  public function load_requires () {
    if ($this->requires == '') $this->_requires = array();
    else $this->_requires = explode(',', $this->requires);
  }

  protected function _update_upgrades_to_string () {
    $this->requires = implode(',', $this->_requires);
  }

  public function get_requires () {
    if (empty($this->_requires)) $this->load_requires();
    return $this->_requires;
  }

}