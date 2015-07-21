<?php

class ItemTemplate extends RPGEntitySaveable {
  // Fields
  public $itid;
  public $name_id;
  public $name;
  public $icon;
  public $type;
  public $rarity_lo;
  public $rarity_hi;
  public $cost;
  public $for_sale;
  
  // Private vars
  static $fields_int = array('rarity_lo', 'rarity_hi', 'cost');
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

  public function get_description () {
    return ItemDesc::get($this);
  }


  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  /**
   * $exclude 
   */
  public static function random ($num_items = 1, $rarity_min = 0, $rarity_max = 5, $exclude_types = array(), $exclude_items = array(), $item_type_probabilities = NULL) {
    // Choose an item based on the probability that the type will appear.
    $all_probs = ($item_type_probabilities === NULL ? ItemType::PROBABILITIES() : $item_type_probabilities);
    $items = array();
    
    // Populate a list full of the types based on the probability given.
    $list = array();
    $all_items = array();
    foreach ($all_probs as $type => $prob) {
      // If we need to exclude a type, do so.
      if (in_array($type, $exclude_types)) continue;

      // Get the list of items and remove excluded items to prevent looping issues later.
      if (!isset($all_items[$type])) {
        // Load all items of this type.
        $all_items[$type] = ItemTemplate::load_multiple(array('type' => $type));
        // Remove any items that are excluded.
        foreach ($all_items[$type] as $index => $an_item) {
          $remove_me = false;
          if (in_array($an_item->type, $exclude_items)) $remove_me = true;
          // Check if the item's rarity range overlaps with the randomization's range.
          if ($an_item->rarity_hi < $rarity_min || $an_item->rarity_lo > $rarity_max) $remove_me = true;
          // If we need to remove the item, do so.
          if ($remove_me) unset($all_items[$type][$index]);
        }
        // Re-index the array.
        $all_items[$type] = array_values($all_items[$type]);
      }

      // If there are no items available for this type, skip it.
      if (count($all_items[$type]) <= 0) continue;

      // Fill the list with this type with $count number of $type.
      $count = $prob * 1000;
      $list = array_merge($list, array_fill(count($list), $count, $type));
    }

    // If there are no items to choose from, we're done.
    if (empty($list)) return $items;

    // Get some items.
    while (count($items) < $num_items) {
      // Choose an entry randomly from the list.
      $index = array_rand($list);
      $type = $list[$index];
      
      // Now that we have a type, randomly select an item of this type.
      if (!isset($all_items[$type])) $all_items[$type] = Item::load_multiple(array('type' => $type));

      // Randomly select an item.
      $item_index = array_rand($all_items[$type]);
      $item = $all_items[$type][$item_index];

      // If we didn't find an item, remove this index from the list because it's obviously bad.
      if (empty($item)) {
        array_splice($all_items[$type], $item_index, 1);
        continue;
      }

      // Add the item to the list.
      $items[] = $item;
    }

    return $items;
  }
}