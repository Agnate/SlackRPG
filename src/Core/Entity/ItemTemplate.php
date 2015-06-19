<?php

class ItemTemplate extends RPGEntitySaveable {
  // Fields
  public $itid;
  public $name_id;
  public $name;
  public $icon;
  public $type;
  
  // Private vars
  static $fields_int = array();
  static $db_table = 'item_templates';
  static $default_class = 'ItemTemplate';
  static $primary_key = 'itid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );
  }

  public function get_display_name ($bold = true) {
    return (!empty($this->icon) ? $this->icon.' ' : '').($bold ? '*' : '').$this->name.($bold ? '*' : '');
  }
}