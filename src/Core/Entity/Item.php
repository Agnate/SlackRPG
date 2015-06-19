<?php

class Item extends RPGEntitySaveable {
  // Fields
  public $iid;
  public $itid; // ItemTemplate ID
  public $name_id;
  public $name;
  public $icon;
  public $type;
  
  // Private vars
  static $fields_int = array();
  static $db_table = 'items';
  static $default_class = 'Item';
  static $primary_key = 'iid';

  
  function __construct($data = array(), ItemTemplate $template = null) {
    // Preload template values.
    if (!empty($template)) {
      $tempdata = get_object_vars($template);

      foreach ($tempdata as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Perform regular constructor.
    parent::__construct($data);
  }

  public function get_display_name () {
    return $this->name;
  }
}