<?php

class Guild extends RPGEntitySaveable {
  // Fields
  public $gid;
  public $admin;
  public $season;
  public $username;
  public $slack_user_id;
  public $name;
  public $icon;
  public $created;
  public $updated;
  public $gold;
  public $fame;
  public $adventurer_limit;
  public $upgrades;

  // Protected
  protected $_adventurers;
  protected $_renown;
  protected $_upgrades;
  protected $_queued_upgrades;
  protected $_bonus;
  protected $_items;
  protected $_quests;

  // Private vars
  static $fields_int = array('season', 'created', 'updated', 'gold', 'fame', 'adventurer_limit');
  static $db_table = 'guilds';
  static $default_class = 'Guild';
  static $primary_key = 'gid';

  const DEFAULT_ADVENTURER_LIMIT = 4;
  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    // Add default adventurer limit.
    if (empty($this->admin)) $this->admin = false;
    if (empty($this->adventurer_limit)) $this->adventurer_limit = Guild::DEFAULT_ADVENTURER_LIMIT;
    if (empty($this->_renown)) $this->_renown = -9999;

    // Load up the bonus object.
    $this->load_bonus();

    // Convert upgrades.
    $this->load_upgrades();

    // Apply upgrade bonuses.
    $this->__apply_upgrade_bonuses();
  }

  public function get_display_name ($bold = true, $display_icon = true) {
    return ($display_icon ? $this->icon.' ' : '').($bold ? '*' : '').$this->name.($bold ? '*' : '');
  }

  protected function __apply_upgrade_bonuses () {
    if (empty($this->_upgrades)) $this->load_upgrades();
    // Apply bonuses from Upgrades to this Guild.
    foreach ($this->_upgrades as $upgrade) {
      $upgrade->apply_bonus($this);
    }
  }

  public function load_adventurers () {
    // Get all adventurers in this Guild.
    $this->_adventurers = Adventurer::load_multiple( array('gid' => $this->gid, 'dead' => false) );
  }

  public function get_adventurers () {
    // Load the adventurers if they haven't been loaded.
    if (empty($this->_adventurers)) $this->load_adventurers();
    return $this->_adventurers;
  }

  public function get_adventurers_count ($include_undead = true) {
    $adventurers = $this->get_adventurers();
    $count = 0;
    foreach ($adventurers as $adventurer) {
      if (!$include_undead && $adventurer->class == AdventurerClass::UNDEAD) continue;
      $count++;
    }
    return $count;
  }

  public function get_best_adventurers ($count, $only_available = true) {
    if (empty($this->_adventurers)) $this->load_adventurers();
    $by_level = array();
    foreach ($this->_adventurers as $adventurer) {
      if (!isset($by_level[$adventurer->level])) $by_level[$adventurer->level] = array();
      $by_level[$adventurer->level][] = $adventurer;
    }
    // Sort highest levels to the top.
    krsort($by_level, SORT_NATURAL);
    $adventurers = array();
    foreach ($by_level as $key => $list) {
      foreach ($list as $adventurer) {
        $adventurers[] = $adventurer;
        $count--;
        if (!$count) break 2;
      }
    }
    return $adventurers;
  }

  public function get_best_adventurers_level ($count, $only_available = true) {
    if (empty($this->_adventurers)) $this->load_adventurers();
    // Get list of adventurers.
    $adventurers = $this->get_best_adventurers($count, $only_available);
    $total = 0;
    foreach ($adventurers as $adventurer) $total += $adventurer->level;
    return $total;
  }

  public function get_champion () {
    if (empty($this->_adventurers)) $this->load_adventurers();
    $adventurers = $this->get_adventurers();
    foreach ($adventurers as $adventurer) {
      if ($adventurer->champion) return $adventurer;
    }
    return FALSE;
  }

  public function get_total_points ($force_calculation = false) {
    if (!$force_calculation && $this->_renown !== -9999) return $this->_renown;

    // Add up all the renown.
    $this->_renown = $this->fame;
    $adventurers = $this->get_adventurers();
    foreach ($adventurers as $adventurer) {
      $this->_renown += $adventurer->popularity;
    }

    return $this->_renown;
  }

  public function load_upgrades () {
    $list = explode(',', $this->upgrades);
    $this->_upgrades = array();

    // Load up all the Upgrade items.
    foreach ($list as $upgrade_name) {
      $this->__add_upgrade($upgrade_name, false);
    }

    // Re-string the upgrade list to weed out errors.
    $this->__update_upgrades_to_string();
  }

  protected function __update_upgrades_to_string () {
    $this->upgrades = implode(',', array_keys($this->_upgrades));
  }

  public function get_upgrades () {
    if (empty($this->_upgrades)) $this->load_upgrades();
    return $this->_upgrades;
  }

  public function add_upgrade ($upgrade_name) {
    if (empty($upgrade_name)) return FALSE;
    if (empty($this->_upgrades)) $this->load_upgrades();
    // Add the upgrade to the list.
    $this->__add_upgrade($upgrade_name);
    // Refresh the string to save to the db.
    $this->__update_upgrades_to_string();
    return FALSE;
  }

  protected function __add_upgrade ($upgrade_name, $check_existing = true) {
    // Check if this upgrade even exists.
    $upgrade = Upgrade::load(array('name_id' => $upgrade_name));
    if (empty($upgrade)) return FALSE;
    // Check if the upgrade is already in the list.
    if ($check_existing && in_array($upgrade_name, array_keys($this->_upgrades))) return FALSE;
    // Add the upgrade.
    $this->_upgrades[$upgrade->name_id] = $upgrade;
    return $upgrade;
  }

  public function has_upgrade ($upgrade) {
    $upgrade_name = is_string($upgrade) ? $upgrade : $upgrade->name_id;
    if (empty($upgrade_name)) return FALSE;
    if (empty($this->_upgrades)) $this->load_upgrades();
    return in_array($upgrade_name, array_keys($this->_upgrades));
  }

  public function meets_requirement ($upgrade) {
    // Check that ALL of the requirements are met.
    $requires = $upgrade->get_requires();
    if (empty($requires)) return TRUE;

    $keys = array_keys($this->_upgrades);
    foreach ($requires as $requirement) {
      if ($requirement->type != 'upgrade') continue;
      if (!in_array($requirement->name_id, $keys)) return FALSE;
    }

    return TRUE;
  }

  public function has_required_items ($upgrade_or_requirements) {
    // Determine if the parameter is an Upgrade, a Requirement, or a list of Requirements.
    $class = get_class($upgrade_or_requirements);
    if ($class == 'Upgrade')
      $requires = $upgrade_or_requirements->get_required_type(Requirement::TYPE_ITEM);
    else if ($class == 'Requirement')
      $requires = array($upgrade_or_requirements);
    else if (is_array($upgrade_or_requirements))
      $requires = $upgrade_or_requirements;

    $items = array();
    if (empty($requires)) return $items;

    $inventory = &$this->get_items();
    $compact = $this->compact_items($inventory);

    foreach ($requires as $requirement) {
      if (get_class($requirement) != 'Requirement') continue;
      // Find the item in the inventory.
      if (!isset($compact[$requirement->name_id])) return FALSE;
      if (count($compact[$requirement->name_id]) < $requirement->qty) return FALSE;

      // Remove the number of items we need.
      for ($i = 0; $i < $requirement->qty; $i++) {
        $items[] = array_pop($compact[$requirement->name_id]);
      }
    }

    return $items;
  }

  protected function compact_items ($items, $exclude_on_hold = true) {
    $compact_items = array();

    // Compact same-name items.
    foreach ($items as &$item) {
      if ($exclude_on_hold && $item->on_hold) continue;
      if (!isset($compact_items[$item->name_id])) $compact_items[$item->name_id] = array();
      $compact_items[$item->name_id][] = $item;
    }

    return $compact_items;
  }

  public function get_queued_upgrades () {
    if (!is_array($this->_queued_upgrades)) {
      // Get any upgrades that are in the queue.
      $queues = Queue::load_multiple(array('gid' => $this->gid, 'type' => 'Upgrade'));
      $this->_queued_upgrades = array();
      foreach ($queues as $queue) {
        $upgrade = $queue->process();
        $this->_queued_upgrades[$upgrade->name_id] = $upgrade;
      }
    }
    return $this->_queued_upgrades;
  }

  public function upgrade_is_queued ($upgrade) {
    $upgrade_name = is_string($upgrade) ? $upgrade : $upgrade->name_id;
    // Get all queued upgrades.
    $queued_upgrades = $this->get_queued_upgrades();
    return isset($queued_upgrades[$upgrade_name]);
  }

  public function get_available_upgrades () {
    // Load all Upgrades.
    $all = Upgrade::load_multiple(array());

    // Weed out the upgrades that aren't available
    foreach ($all as $key => $upgrade) {
      // If they can upgrade to this, keep it.
      if (!$this->has_upgrade($upgrade) && !$this->upgrade_is_queued($upgrade) && $this->meets_requirement($upgrade)) continue;
      // Remove anything that cannot be upgraded now.
      unset($all[$key]);
    }

    return $all;
  }

  public function load_bonus () {
    if (empty($this->_bonus)) $this->_bonus = new Bonus ();
    return $this->_bonus;
  }

  public function get_bonus () {
    if (empty($this->_bonus)) $this->load_bonus();
    return $this->_bonus;
  }

  public function load_items () {
    // Load all items this Guild has.
    $this->_items = Item::load_multiple(array('gid' => $this->gid));
  }

  public function &get_items () {
    if (!is_array($this->_items)) $this->load_items();
    return $this->_items;
  }

  public function add_item ($item_template, $extra_data = NULL) {
    // Always "add" any ItemTemplate objects.
    if ($item_template instanceof ItemTemplate == false) return FALSE;

    // Get the current items.
    $items = &$this->get_items();

    // Create the inventory item.
    $item_data = array('gid' => $this->gid);
    if ($extra_data !== NULL) $item_data['extra_data'] = $extra_data;
    $item = new Item ($item_data, $item_template);
    $success = $item->save();

    // Before adding it to the inventory, run any Item-specific changes.
    $item->on_add_to_inventory($this);

    // Add the item into the inventory.
    if ($success) $items[] = &$item;

    return $success ? $item : $success;
  }

  public function remove_item ($item, $put_on_hold = false) {
    // Always "remove" any Item objects from an Inventory.
    if ($item instanceof Item == false) return FALSE;

    // Lock the item away from the user.
    if ($put_on_hold) {
      $item->on_hold = true;
    }
    // Remove item ownership.
    else {
      $item->gid = 0;
    }

    // Remove the item if it's not just on hold.
    if (!$put_on_hold) {
      // Before removing it from the inventory, run any Item-specific changes.
      $item->on_remove_from_inventory($this);

      // Get the current items.
      $items = &$this->get_items();
      // Remove from this player's inventory.
      foreach ($items as $key => &$invitem) {
        if ($invitem == $item) {
          array_splice($items, $key, 1);
          break;
        }
      }
    }

    // Save the item.
    return $item->save();
  }

  /**
   * Return an item from being on hold.
   */
  public function return_item ($item) {
    if ($item instanceof Item == false) return FALSE;

    $item->on_hold = false;
    return $item->save();
  }

  public function load_quests () {
    $this->_quests = Quest::load_multiple(array('gid' => $this->gid, 'completed' => false, 'multiplayer' => false));
  }

  public function get_quests () {
    if (!is_array($this->_quests)) $this->load_quests();
    return $this->_quests;
  }

  public function calculate_adventurer_level_info () {
    $adventurers = $this->get_adventurers();
    $lo = 99999;
    $hi = 0;

    foreach ($adventurers as $adventurer) {
      $level = $adventurer->get_level();
      if ($level < $lo) $lo = $level;
      if ($level > $hi) $hi = $level;
    }

    return compact('lo', 'hi');
  }

  

  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function load ($data, $find_partials = false, $load_adventurers = false, $load_items = false) {
    // Load the Guild.
    $guild = parent::load($data, $find_partials);

    // Load the Adventurers.
    if ($load_adventurers && !empty($guild)) {
      $guild->load_adventurers();
    }

    // Load the Items.
    if ($load_items && !empty($guild)) {
      $guild->load_items();
    }

    return $guild;
  }

  /**
   * Get all of the Guilds for this season.
   */
  public static function current ($season = null) {
    if (empty($season)) $season = Season::current();

    $guilds = Guild::load_multiple(array('season' => $season->sid));

    if (empty($guilds)) return array();
    return $guilds;
  }

  public static function sort ($a, $b) {
    if ($a->get_total_points() == $b->get_total_points()) {
      if ($a->gold == $b->gold) {
        if ($a->created == $b->created) return 0;
        return ($a->created < $b->created) ? -1 : 1;
      }
      return ($a->gold > $b->gold) ? -1 : 1;
    }
    return ($a->get_total_points() > $b->get_total_points()) ? -1 : 1;
  }
}