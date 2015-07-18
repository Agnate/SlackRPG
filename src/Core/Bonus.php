<?php

class Bonus {
  /**
   * To add new Modifiers, add the following:
   *   1. Add a "protected" variable.
   *   2. Add a "const" with that variable's name as the string.
   *   3. Add the new "const" to the list called ALL_MODIFIERS.
   *   4. Add a default value in the __construct function.
   */

  // 1. Fields
  protected $_travel_speed_modifiers;
  protected $_quest_speed_modifiers;
  protected $_quest_success_modifiers;
  protected $_death_rate_modifiers;
  protected $_quest_reward_gold_modifiers;
  protected $_quest_reward_fame_modifiers;
  protected $_quest_reward_exp_modifiers;
  protected $_quest_reward_item_modifiers;
  protected $_miss_rate_modifiers;
  protected $_crit_rate_modifiers;
  protected $_opponent_miss_rate_modifiers;
  protected $_opponent_crit_rate_modifiers;
  protected $_attack_as_success_modifiers;
  protected $_defend_as_success_modifiers;
  protected $_break_as_success_modifiers;
  protected $_loss_by_one_as_tie_modifiers;
  protected $_loss_on_success_modifiers;
  protected $_tie_breaker_on_fail_modifiers;

  // 2. Modifiers available for retrieval.
  const TRAVEL_SPEED = '_travel_speed_modifiers';
  const QUEST_SPEED = '_quest_speed_modifiers';
  const QUEST_SUCCESS = '_quest_success_modifiers';
  const DEATH_RATE = '_death_rate_modifiers';
  const QUEST_REWARD_GOLD = '_quest_reward_gold_modifiers';
  const QUEST_REWARD_FAME = '_quest_reward_fame_modifiers';
  const QUEST_REWARD_EXP = '_quest_reward_exp_modifiers';
  const QUEST_REWARD_ITEM = '_quest_reward_item_modifiers';
  const MISS_RATE = '_miss_rate_modifiers';
  const CRIT_RATE = '_crit_rate_modifiers';
  const OPPONENT_MISS_RATE = '_opponent_miss_rate_modifiers';
  const OPPONENT_CRIT_RATE = '_opponent_crit_rate_modifiers';
  const ATTACK_AS_SUCCESS = '_attack_as_success_modifiers';
  const DEFEND_AS_SUCCESS = '_defend_as_success_modifiers';
  const BREAK_AS_SUCCESS = '_break_as_success_modifiers';
  const LOSS_BY_ONE_AS_TIE = '_loss_by_one_as_tie_modifiers';
  const LOSS_ON_SUCCESS = '_loss_on_success_modifiers';
  const TIE_BREAKER_ON_FAIL = '_tie_breaker_on_fail_modifiers';

  // 3. List of all available modifiers.
  static $all_modifiers = array(Bonus::TRAVEL_SPEED, Bonus::QUEST_SPEED, Bonus::QUEST_SUCCESS, Bonus::DEATH_RATE,
    Bonus::QUEST_REWARD_GOLD, Bonus::QUEST_REWARD_FAME, Bonus::QUEST_REWARD_EXP, Bonus::QUEST_REWARD_ITEM,
    Bonus::MISS_RATE, Bonus::CRIT_RATE, Bonus::OPPONENT_MISS_RATE, Bonus::OPPONENT_CRIT_RATE, Bonus::ATTACK_AS_SUCCESS,
    Bonus::DEFEND_AS_SUCCESS, Bonus::BREAK_AS_SUCCESS, Bonus::LOSS_BY_ONE_AS_TIE, Bonus::LOSS_ON_SUCCESS,
    Bonus::TIE_BREAKER_ON_FAIL);

  // Change how the modifier is returned.
  const MOD_ORIGINAL = 'original'; // Get as decimal version (1.05) for +5%
  const MOD_HUNDREDS = 'hundreds'; // Get in hundreds (5) for +5%.
  const MOD_DIFF = 'diff'; // Get diff as decimal version (0.05) for +5%.

  const FOR_DEFAULT = 'default';

  function __construct($data = array()) {
    // Save values to object.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // 4. Set any defaults.
    $all_mods = static::$all_modifiers;
    foreach ($all_mods as $mod) {
      switch ($mod) {
        default:
          if (empty($this->{$mod}))
            $this->{$mod} = $this->__create_modifier_list();
          break;
      }
    }
  }

  protected function __create_modifier_list ($default = 1) {
    // $quest_types = Quest::types();
    // $location_types = Location::types();

    $mods = array(
      Bonus::FOR_DEFAULT => $default,
      'Quest' => array(),
      'Location' => array(),
      'Challenge' => array(),
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
  public function get_mod ($mod_name, $for = Bonus::FOR_DEFAULT, $value_as = Bonus::MOD_ORIGINAL) {
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
      case 'Challenge':
        $type = is_array($info) ? $info['type'] : $info->type;
        $mod = isset($mods[$for][$type]) ? $mods[$for][$type] : $mods[Bonus::FOR_DEFAULT];
        break;

      default:
        $mod = $mods[$for];
        break;
    }
    // Return the modifier value for whatever specific scenario we need.
    return $this->__adjust_modifier($mod, $value_as);
  }

  public function set_mod ($mod_name, $value, $for = Bonus::FOR_DEFAULT) {
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
      case 'Challenge':
        $type = is_array($info) ? $info['type'] : $info->type;
        $mods[$for][$type] = $value;
        break;

      default:
        $mods[$for] = $value;
        break;
    }
    return TRUE;
  }

  public function add_mod ($mod_name, $value, $for = Bonus::FOR_DEFAULT) {
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
      case 'Challenge':
        $type = is_array($info) ? $info['type'] : $info->type;
        if (!isset($mods[$for][$type])) $mods[$for][$type] = $mods[Bonus::FOR_DEFAULT];
        $mods[$for][$type] += $value;
        break;

      default:
        if (!isset($mods[$for])) $mods[$for] = $mods[Bonus::FOR_DEFAULT];
        $mods[$for] += $value;
        break;
    }
    return TRUE;
  }
}