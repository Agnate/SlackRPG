<?php

class Bonus {
  /**
   * To add new Modifiers, add the following:
   *   1. Add a "protected" variable.
   *   2. Add a "const" with that variable's name as the string.
   *   3. Add the new "const" to the list called ALL_MODIFIERS.
   *   4. Add a default value in the __construct function.
   *   5. Add bonus name to static function list.
   */

  // 1. Fields - Keep the names as short as possible, as these are stored in the database.
  protected $_travel_speed;
  protected $_quest_speed;
  protected $_quest_success;
  protected $_death_rate;
  protected $_q_reward_gold;
  protected $_q_reward_fame;
  protected $_q_reward_exp;
  protected $_q_reward_item;
  protected $_q_reward_sp_item;
  protected $_miss_rate;
  protected $_crit_rate;
  protected $_opp_miss_rate;
  protected $_opp_crit_rate;
  protected $_attack_as_success;
  protected $_defend_as_success;
  protected $_break_as_success;
  protected $_loss_by_one_as_tie;
  protected $_loss_on_success;
  protected $_tie_breaker_on_fail;
  protected $_item_type_find_rate;

  // 2. Modifiers available for retrieval.
  const TRAVEL_SPEED = '_travel_speed';
  const QUEST_SPEED = '_quest_speed';
  const QUEST_SUCCESS = '_quest_success';
  const DEATH_RATE = '_death_rate';
  const QUEST_REWARD_GOLD = '_q_reward_gold';
  const QUEST_REWARD_FAME = '_q_reward_fame';
  const QUEST_REWARD_EXP = '_q_reward_exp';
  const QUEST_REWARD_ITEM = '_q_reward_item';
  const QUEST_REWARD_SPECIAL_ITEM = '_q_reward_sp_item';
  const MISS_RATE = '_miss_rate';
  const CRIT_RATE = '_crit_rate';
  const OPPONENT_MISS_RATE = '_opp_miss_rate';
  const OPPONENT_CRIT_RATE = '_opp_crit_rate';
  const ATTACK_AS_SUCCESS = '_attack_as_success';
  const DEFEND_AS_SUCCESS = '_defend_as_success';
  const BREAK_AS_SUCCESS = '_break_as_success';
  const LOSS_BY_ONE_AS_TIE = '_loss_by_one_as_tie';
  const LOSS_ON_SUCCESS = '_loss_on_success';
  const TIE_BREAKER_ON_FAIL = '_tie_breaker_on_fail';
  const ITEM_TYPE_FIND_RATE = '_item_type_find_rate';

  // 3. List of all available modifiers.
  static $all_modifiers = array(Bonus::TRAVEL_SPEED, Bonus::QUEST_SPEED, Bonus::QUEST_SUCCESS, Bonus::DEATH_RATE,
    Bonus::QUEST_REWARD_GOLD, Bonus::QUEST_REWARD_FAME, Bonus::QUEST_REWARD_EXP, Bonus::QUEST_REWARD_ITEM,
    Bonus::MISS_RATE, Bonus::CRIT_RATE, Bonus::OPPONENT_MISS_RATE, Bonus::OPPONENT_CRIT_RATE, Bonus::ATTACK_AS_SUCCESS,
    Bonus::DEFEND_AS_SUCCESS, Bonus::BREAK_AS_SUCCESS, Bonus::LOSS_BY_ONE_AS_TIE, Bonus::LOSS_ON_SUCCESS,
    Bonus::TIE_BREAKER_ON_FAIL, Bonus::ITEM_TYPE_FIND_RATE, Bonus::QUEST_REWARD_SPECIAL_ITEM);

  // Change how the modifier is returned.
  const MOD_ORIGINAL = 'original'; // Get as decimal version (1.05) for +5%
  const MOD_HUNDREDS = 'hundreds'; // Get in hundreds (5) for +5%.
  const MOD_DIFF = 'diff'; // Get diff as decimal version (0.05) for +5%.

  const FOR_DEFAULT = 'default';

  const DEFAULT_VALUE = 1;

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
      'ItemType' => array(),
    );

    return $mods;
  }

  protected function &__get_modifier ($mod_name) {
    if (!property_exists($this, $mod_name)) return FALSE;
    // Get the modifier list.
    return $this->$mod_name;
  }

  /**
   * Merge another bonus into this one (additive).
   *
   * $bonus -> Another Bonus object.
   *
   */
  public function merge ($bonus, $exclude_mods = array()) {
    if (get_class($bonus) != 'Bonus') return FALSE;

    // Loop through the modifiers and add them together.
    foreach (static::$all_modifiers as $mod_name) {
      // Skip any excluded mods.
      if (in_array($mod_name, $exclude_mods)) continue;

      $mod = $bonus->$mod_name;
      foreach ($mod as $for => $list_or_value) {
        // If this is the default value, there's no list.
        if ($for == Bonus::FOR_DEFAULT) {
          $val = Bonus::adjust_modifier($list_or_value, Bonus::MOD_DIFF);
          $this->add_mod($mod_name, $val, $for);
          continue;
        }

        // Loop through the list of values to make sure they're copied over.
        foreach ($list_or_value as $type => $value) {
          $val = Bonus::adjust_modifier($value, Bonus::MOD_DIFF);
          $this->add_mod($mod_name, $val, Bonus::compile_for($for, $type));
        }
      }
    }

    return TRUE;
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
    $info = Bonus::evaluate_for($for);
    $for = is_object($info) ? get_class($info) : $info['for'];
    // Look for the value.
    if (!isset($mods[$for])) return FALSE;
    // Grab the value (if we're looking for a specific $for value, look for one and use default if it doesn't exist).
    switch ($for) {
      case 'Quest':
      case 'Location':
      case 'Challenge':
      case 'ItemType':
        $type = is_array($info) ? $info['type'] : $info->type;
        $mod = isset($mods[$for][$type]) ? $mods[$for][$type] : $mods[Bonus::FOR_DEFAULT];
        break;

      default:
        $mod = $mods[$for];
        break;
    }
    // Return the modifier value for whatever specific scenario we need.
    return Bonus::adjust_modifier($mod, $value_as);
  }

  public function set_mod ($mod_name, $value, $for = Bonus::FOR_DEFAULT) {
    // Get the modifier list.
    $mods = &$this->__get_modifier($mod_name);
    if ($mods === FALSE) return FALSE;
    // Convert $for object/string into meaningful data to categorize the modifier.
    $info = Bonus::evaluate_for($for);
    $for = is_object($info) ? get_class($info) : $info['for'];
    // Set the value.
    switch ($for) {
      case 'Quest':
      case 'Location':
      case 'Challenge':
      case 'ItemType':
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
    $info = Bonus::evaluate_for($for);
    $for = is_object($info) ? get_class($info) : $info['for'];
    // Set the value.
    switch ($for) {
      case 'Quest':
      case 'Location':
      case 'Challenge':
      case 'ItemType':
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



  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function get_name ($bonus_name, $value, $for = Bonus::FOR_DEFAULT) {
    $for = $for != Bonus::FOR_DEFAULT ? Bonus::evaluate_for($for) : '';
    $name = '';

    // 5. Add bonus name to the list.
    switch ($bonus_name) {
      case Bonus::TRAVEL_SPEED: $name = 'travel time'; break;
      case Bonus::QUEST_SPEED: $name = 'time to complete !Quest'; break;
      case Bonus::QUEST_SUCCESS: $name = 'chance of completing !Quest'; break;
      case Bonus::DEATH_RATE: $name = 'chance of adventurers dying during !Quest'; break;
      case Bonus::QUEST_REWARD_GOLD: $name = 'amount of gold received for completing !Quest'; break;
      case Bonus::QUEST_REWARD_FAME: $name = 'amount of fame received for completing !Quest'; break;
      case Bonus::QUEST_REWARD_EXP: $name = 'amount of experience points received for completing !Quest'; break;
      case Bonus::QUEST_REWARD_ITEM: $name = 'chance of finding an item when completing !Quest'; break;
      case Bonus::QUEST_REWARD_SPECIAL_ITEM: $name = 'chance of finding a special item when completing !Quest'; break;
      case Bonus::ITEM_TYPE_FIND_RATE: $name = 'chance of finding !ItemType when completing !Quest'; break;

      case Bonus::MISS_RATE: $name = 'miss rate in a Colosseum fight'; break;
      case Bonus::CRIT_RATE: $name = 'crit rate in a Colosseum fight'; break;
      case Bonus::OPPONENT_MISS_RATE: $name = 'OPPONENT_MISS_RATE'; break;
      case Bonus::OPPONENT_CRIT_RATE: $name = 'OPPONENT_CRIT_RATE'; break;
      case Bonus::ATTACK_AS_SUCCESS: $name = 'ATTACK_AS_SUCCESS'; break;
      case Bonus::DEFEND_AS_SUCCESS: $name = 'DEFEND_AS_SUCCESS'; break;
      case Bonus::BREAK_AS_SUCCESS: $name = 'BREAK_AS_SUCCESS'; break;
      case Bonus::LOSS_BY_ONE_AS_TIE: $name = 'LOSS_BY_ONE_AS_TIE'; break;
      case Bonus::LOSS_ON_SUCCESS: $name = 'LOSS_ON_SUCCESS'; break;
      case Bonus::TIE_BREAKER_ON_FAIL: $name = 'TIE_BREAKER_ON_FAIL'; break;
    }

    if (empty($name)) return $bonus_name;

    // Create any replacement tokens.
    $tokens = array();

    // Default values for !Quest tokens.
    switch ($bonus_name) {
      case Bonus::QUEST_SPEED:
      case Bonus::QUEST_SUCCESS:
      case Bonus::DEATH_RATE:
        $tokens['!Quest'] = 'a quest';
        break;

      case Bonus::QUEST_REWARD_GOLD:
      case Bonus::QUEST_REWARD_FAME:
      case Bonus::QUEST_REWARD_EXP:
      case Bonus::QUEST_REWARD_ITEM:
      case Bonus::QUEST_REWARD_SPECIAL_ITEM:
      case Bonus::ITEM_TYPE_FIND_RATE:
        $tokens['!Quest'] = 'a quest or exploring';
        break;
    }

    // Specific tokens based on the "for" value.
    if (!empty($for)) {
      switch ($for['for']) {
        case 'Quest':
        case 'ItemType':
          $tokens['!'.$for['for']] = $for['for']::get_type_name($for['type']);
      }
    }

    // Replace tokens.
    $name = str_replace(array_keys($tokens), array_values($tokens), $name);

    return $name .' by '.floor(abs($value) * 100).'%';
  }

  public static function evaluate_for ($for) {
    if (is_object($for)) return $for;
    $data = explode('->', $for);
    $info = array();
    if (isset($data[0])) $info['for'] = $data[0];
    if (isset($data[1])) $info['type'] = $data[1];
    return $info;
  }

  public static function compile_for ($for, $type = NULL) {
    $list = array($for);
    if (!empty($type)) $list[] = $type;
    return implode('->', $list);
  }

  public static function adjust_modifier ($mod, $value_as) {
    if ($value_as == Bonus::MOD_DIFF) return ($mod - 1);
    if ($value_as == Bonus::MOD_HUNDREDS) return floor(($mod - 1) * 100);
    return $mod;
  }
}