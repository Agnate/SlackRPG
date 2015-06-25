<?php

class Item extends RPGEntitySaveable {
  // Fields
  public $iid;
  public $itid; // ItemTemplate ID
  public $gid;
  public $name_id;
  public $name;
  public $icon;
  public $type;
  public $rarity_lo;
  public $rarity_hi;

  protected $_description;
  
  // Private vars
  static $fields_int = array('rarity_lo', 'rarity_hi');
  static $db_table = 'items';
  static $default_class = 'Item';
  static $primary_key = 'iid';
  static $partials = array('name', 'name_id');

  
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

    // Load up the item description.
    $this->_description = ItemDesc::get($this);
  }

  public function get_display_name ($bold = true) {
    return (!empty($this->icon) ? $this->icon.' ' : '').($bold ? '*' : '').$this->name.($bold ? '*' : '');
  }

  public function get_description () {
    return $this->_description;
  }
}