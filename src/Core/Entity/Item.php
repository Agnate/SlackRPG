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
  public $cost;
  public $for_sale;
  public $on_hold;
  public $extra_data;

  // Protected
  protected $_description;
  protected $_bonus;
  
  // Private vars
  static $fields_int = array('rarity_lo', 'rarity_hi', 'cost');
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
    $this->_description = ItemDesc::get($this, $this->extra_data);
    if (empty($this->for_sale)) $this->for_sale = false;

    // Load up the bonus objects.
    $this->calculate_bonus();
  }

  public function get_display_name ($bold = true) {
    if ($this->name_id == 'relic_soulstone') $suffix = ' ('.$this->extra_data.')';
    return (!empty($this->icon) ? $this->icon.' ' : '').($bold ? '*' : '').$this->name.(isset($suffix) ? $suffix : '').($bold ? '*' : '');
  }

  public function get_description () {
    return $this->_description;
  }

  public function load_bonus () {
    if (empty($this->_bonus)) $this->_bonus = new Bonus ();
    return $this->_bonus;
  }

  public function get_bonus () {
    if (empty($this->_bonus)) $this->load_bonus();
    return $this->_bonus;
  }

  public function calculate_bonus () {
    // Load up the bonus object.
    $this->_bonus = null;
    $this->load_bonus();

    // Apply item modifiers.
    ItemBonus::apply_bonus($this);
  }

  /**
   * This function runs when the Item is added to a Guild's inventory.
   */
  public function on_add_to_inventory ($guild) {
    // For Soul Stones, add the Undead Adventurer to the Guild.
    if ($this->name_id == 'relic_soulstone') {
      // Check if this Undead Adventurer exists yet.
      $adventurer_data = array('gid' => 0, 'name' => $this->extra_data, 'class' => AdventurerClass::UNDEAD);
      $adventurer = Adventurer::load($adventurer_data);
      // If we cannot find an adventurer by this name, make a new one.
      if (empty($adventurer)) $adventurer = Adventurer::generate_undead_adventurer($this->extra_data, false);
      // Assign the adventurer to this Guild.
      $adventurer->gid = $guild->gid;
      $adventurer->available = false;
      $adventurer->dead = false;
      $adventurer->undying = true;
      $adventurer->set_adventurer_class(AdventurerClass::UNDEAD);
      $adventurer->save();
    }
  }

  /**
   * This function runs when the Item is removed from a Guild's inventory.
   */
  public function on_remove_from_inventory ($guild) {
    // For Soul Stones, remove the Undead Adventurer from the Guild.
    if ($this->name_id == 'relic_soulstone') {
      $adventurer_data = array('gid' => $guild->gid, 'name' => $this->extra_data, 'class' => AdventurerClass::UNDEAD);
      $adventurer = Adventurer::load($adventurer_data);
      // If we found the adventurer, remove them from the Guild.
      if (!empty($adventurer)) {
        $adventurer->gid = 0;
        $adventurer->save();
      }
    }
  }
}