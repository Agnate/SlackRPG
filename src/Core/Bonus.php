<?php

class Bonus {
  /**
   * To add new Modifiers, add the following:
   *   1. Add a "protected" variable.
   *   2. Add a "const" with that variable's name as the string.
   *   3. Add the new "const" to the list called ALL_MODIFIERS.
   *   4. Add a default value in the __construct function.
   */

  // Fields
  protected $_travel_speed_modifiers;
  protected $_quest_speed_modifiers;
  protected $_quest_success_modifiers;
  protected $_death_rate_modifiers;
  protected $_quest_reward_gold_modifiers;
  protected $_quest_reward_fame_modifiers;
  protected $_quest_reward_exp_modifiers;
  protected $_quest_reward_item_modifiers;

  // Modifiers available for retrieval.
  const TRAVEL_SPEED = '_travel_speed_modifiers';
  const QUEST_SPEED = '_quest_speed_modifiers';
  const QUEST_SUCCESS = '_quest_success_modifiers';
  const DEATH_RATE = '_death_rate_modifiers';
  const QUEST_REWARD_GOLD = '_quest_reward_gold_modifiers';
  const QUEST_REWARD_FAME = '_quest_reward_fame_modifiers';
  const QUEST_REWARD_EXP = '_quest_reward_exp_modifiers';
  const QUEST_REWARD_ITEM = '_quest_reward_item_modifiers';
  // List of all available modifiers.
  static $all_modifiers = array(Bonus::TRAVEL_SPEED, Bonus::QUEST_SPEED, Bonus::QUEST_SUCCESS, Bonus::DEATH_RATE, Bonus::QUEST_REWARD_GOLD, Bonus::QUEST_REWARD_FAME, Bonus::QUEST_REWARD_EXP, Bonus::QUEST_REWARD_ITEM);

  // Change how the modifier is returned.
  const MOD_ORIGINAL = 'original';
  const MOD_HUNDREDS = 'hundreds';
  const MOD_DIFF = 'diff';

  function __construct($data = array()) {
    // Save values to object.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Set any defaults.
    if (empty($this->_travel_speed_modifiers)) $this->_travel_speed_modifiers = $this->__create_modifier_list();
    if (empty($this->_quest_speed_modifiers)) $this->_quest_speed_modifiers = $this->__create_modifier_list();
    if (empty($this->_quest_success_modifiers)) $this->_quest_success_modifiers = $this->__create_modifier_list();
    if (empty($this->_death_rate_modifiers)) $this->_death_rate_modifiers = $this->__create_modifier_list();
    if (empty($this->_quest_reward_gold_modifiers)) $this->_quest_reward_gold_modifiers = $this->__create_modifier_list();
    if (empty($this->_quest_reward_fame_modifiers)) $this->_quest_reward_fame_modifiers = $this->__create_modifier_list();
    if (empty($this->_quest_reward_exp_modifiers)) $this->_quest_reward_exp_modifiers = $this->__create_modifier_list();
    if (empty($this->_quest_reward_item_modifiers)) $this->_quest_reward_item_modifiers = $this->__create_modifier_list();
  }

  protected function __create_modifier_list () {
    $quest_types = Quest::types();
    $location_types = Location::types();

    $mods = array(
      'default' => 1,
      'Quest' => array(),
      'Location' => array(),
    );

    return $mods;
  }

  protected function &__get_modifier ($mod_name) {
    if (!property_exists($this, $mod_name)) return FALSE;
    // Get the modifier list.
    return $this->$mod_name;
  }

  protected function __evaluate_for ($for) {
    if (is_object($for)) return $for;
    $data = explode('->', $for);
    $info = array();
    if (isset($data[0])) $info['for'] = $data[0];
    if (isset($data[1])) $info['type'] = $data[1];
    return $info;
  }

  protected function __adjust_modifier ($mod, $value_as) {
    if ($value_as == Bonus::MOD_DIFF) return ($mod - 1);
    if ($value_as == Bonus::MOD_HUNDREDS) return floor(($mod - 1) * 100);
    return $mod;
  }


  /**
   * $mod_name - Use the constants defined here in Bonus to retrieve the desired modifier (ex. Bonus::TRAVEL_SPEED).
   * $for - This should be either an Object (Quest or Location) or a String (ex. "Quest->".Quest::TYPE_BOSS).
   */
  public function get_mod ($mod_name, $for = 'default', $value_as = Bonus::MOD_ORIGINAL) {
    // Get the modifier list.
    $mods = &$this->__get_modifier($mod_name);
    if ($mods === FALSE) return FALSE;
    // Convert $for object/string into meaningful data to categorize the modifier.
    $info = $this->__evaluate_for($for);
    $for = is_object($info) ? get_class($info) : $info['for'];
    // Look for the value.
    if (!isset($mods[$for])) return FALSE;
    // Grab the value (if we're looking for a specific $for value, look for one and use default if it doesn't exist).
    switch ($for) {
      case 'Quest':
      case 'Location':
        $type = is_array($info) ? $info['type'] : $info->type;
        $mod = isset($mods[$for][$type]) ? $mods[$for][$type] : $mods['default'];
        break;

      default:
        $mod = $mods[$for];
        break;
    }
    // Return the modifier value for whatever specific scenario we need.
    return $this->__adjust_modifier($mod, $value_as);
  }

  public function set_mod ($mod_name, $value, $for = 'default') {
    // Get the modifier list.
    $mods = &$this->__get_modifier($mod_name);
    if ($mods === FALSE) return FALSE;
    // Convert $for object/string into meaningful data to categorize the modifier.
    $info = $this->__evaluate_for($for);
    $for = is_object($info) ? get_class($info) : $info['for'];
    // Set the value.
    switch ($for) {
      case 'Quest':
      case 'Location':
        $type = is_array($info) ? $info['type'] : $info->type;
        $mods[$for][$type] = $value;
        break;

      default:
        $mods[$for] = $value;
        break;
    }
    return TRUE;
  }

  public function add_mod ($mod_name, $value, $for = 'default') {
    // Get the modifier list.
    $mods = &$this->__get_modifier($mod_name);
    if ($mods === FALSE) return FALSE;
    // Convert $for object/string into meaningful data to categorize the modifier.
    $info = $this->__evaluate_for($for);
    $for = is_object($info) ? get_class($info) : $info['for'];
    // Set the value.
    switch ($for) {
      case 'Quest':
      case 'Location':
        $type = is_array($info) ? $info['type'] : $info->type;
        if (!isset($mods[$for][$type])) $mods[$for][$type] = $mods['default'];
        $mods[$for][$type] += $value;
        break;

      default:
        if (!isset($mods[$for])) $mods[$for] = $mods['default'];
        $mods[$for] += $value;
        break;
    }
    return TRUE;
  }
}