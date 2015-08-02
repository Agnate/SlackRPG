<?php

class Adventurer extends RPGEntitySaveable {
  // Fields
  public $aid;
  public $gid; // Guild they are in.
  public $agid; // Adventuring Group they are in.
  public $name;
  public $icon;
  public $created;
  public $available;
  public $level;
  public $popularity;
  public $exp;
  public $exp_tnl;
  public $class;
  public $champion;
  public $dead;
  public $gender;
  public $enhancements;

  // Protected
  protected $_bonus;
  protected $_adventurer_class;
  protected $_enhancements;

  // Private vars
  static $fields_int = array('created', 'level', 'popularity', 'exp', 'exp_tnl');
  static $db_table = 'adventurers';
  static $default_class = 'Adventurer';
  static $primary_key = 'aid';

  const FILENAME_ADVENTURER_NAMES_ORIGINAL = '/bin/json/original/adventurer_names.json';
  const FILENAME_ADVENTURER_NAMES = '/bin/json/adventurer_names.json';

  const LEVEL_CAP = 20;
  const ENHANCE_AT_LEVELS = 5;
  const GENDER_MALE = 'male';
  const GENDER_FEMALE = 'female';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    if (empty($this->available)) $this->available = false;
    if (empty($this->champion)) $this->champion = false;
    if (empty($this->exp_tnl)) $this->exp_tnl = $this->calculate_exp_tnl();

    // Load up the class and bonus objects.
    $this->calculate_bonus();
  }

  public function get_display_name ($bold = true, $include_champion = true, $include_class = true, $include_gender = true, $include_icon = true) {
    $adventurer_class = $this->get_adventurer_class();
    return ($this->champion ? ':crown:' : '').($include_icon ? $this->icon.' ' : '').($include_class && !empty($adventurer_class) ? $adventurer_class->get_display_name().' ' : '').($bold ? '*' : '').$this->name.($include_gender ? ($this->gender == Adventurer::GENDER_MALE ? ' ♂' : ' ♀') : '').($bold ? '*' : '');
  }

  public function get_pronoun ($capitalize = false) {
    $pronoun = $this->gender == Adventurer::GENDER_MALE ? 'he' : 'she';
    return $capitalize ? ucwords($pronoun) : $pronoun;
  }

  public function get_other_pronoun ($capitalize = false) {
    $pronoun = $this->gender == Adventurer::GENDER_MALE ? 'him' : 'her';
    return $capitalize ? ucwords($pronoun) : $pronoun;
  }

  public function get_possessive_pronoun ($capitalize = false) {
    $pronoun = $this->gender == Adventurer::GENDER_MALE ? 'his' : 'her';
    return $capitalize ? ucwords($pronoun) : $pronoun;
  }

  public function get_possessive () {
    return substr($this->name, -1) == 's' ? "'" : "'s";
  }

  public function get_gender ($capitalize = false) {
    return $capitalize ? ucwords($this->gender) : $this->gender;
  }

  public function give_exp ($exp) {
    $this->exp += $exp;
    // Check if they level up.
    $leveled_up = FALSE;
    while ($this->exp >= $this->exp_tnl) {
      $leveled_up = $this->level_up() || $leveled_up;
    }
    return $leveled_up;
  }

  public function get_level ($include_mods = true) {
    return (!$include_mods && $this->class == 'juggernaut') ? ($this->level - 2) : $this->level;
  }

  /**
   * Very crude enhancement calculation (aka basically none).
   */
  public function set_level ($level, $include_enhancement = false) {
    $orig_level = $this->level;
    $this->level = $level - 1;
    $this->level_up(false);

    // Add enhancements depending on level differences (ignore if level was reduced).
    if ($include_enhancement && $orig_level < $level) {
      $cur_enhs = floor($orig_level / Adventurer::ENHANCE_AT_LEVELS);
      $total_enhs = floor($level / Adventurer::ENHANCE_AT_LEVELS);
      if ($cur_enhs < $total_enhs) {
        // Add extra enhancements.
        for ($i = $cur_enhs; $i < $total_enhs; $i++) {
          $this->add_enhancement($this->get_random_enhancement());
        }
      }
    }
  }

  protected function level_up ($include_enhancement = true) {
    if ($this->level >= Adventurer::LEVEL_CAP) return FALSE;
    // Level up!
    $this->level++;
    $this->exp_tnl = $this->calculate_exp_tnl();

    // Check for level-up enhancement (every 5th levels).
    if ($include_enhancement && $this->get_level(false) % Adventurer::ENHANCE_AT_LEVELS == 0) {
      $this->add_enhancement($this->get_random_enhancement());
    }

    return TRUE;
  }

  public function calculate_exp_tnl ($level = null) {
    if (empty($level)) $level = $this->get_level(false) + 1;
    $level--;
    // Crude level numbers for now.
    if ($level <= 5) return ($level * 100);
    if ($level <= 10) return ($level * 200);
    if ($level <= 15) return ($level * 500);
    return ($level * 1000);
  }

  public function has_adventurer_class () {
    return !empty($this->class);
  }

  public function load_adventurer_class () {
    if (empty($this->class)) return;
    $this->_adventurer_class = AdventurerClass::load(array('name_id' => $this->class));
  }

  public function get_adventurer_class () {
    if (empty($this->_adventurer_class)) $this->load_adventurer_class();
    return !empty($this->_adventurer_class) ? $this->_adventurer_class : FALSE;
  }

  public function set_adventurer_class ($class) {
    $class_name = is_string($class) ? $class : $class->name_id;
    $this->class = $class_name;
    // Sync up the adventurer class.
    $this->calculate_bonus();
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

    // Apply enhancement bonuses.
    $this->apply_enhancements();

    // If they have a class, load up the appropriate class.
    $this->_adventurer_class = null;
    $this->load_adventurer_class();

    // Apply class modifiers.
    $adventurer_class = $this->get_adventurer_class();
    if (!empty($adventurer_class)) $adventurer_class->apply_bonus($this);
  }

  protected function apply_enhancements () {
    $enhancements = $this->get_enhancements();

    foreach ($enhancements as $enhancement) {
      $this->_bonus->add_mod($enhancement->bonus, $enhancement->value, $enhancement->for);
    }
  }

  public function load_enhancements () {
    $this->_enhancements = $this->__decode_enhancements($this->enhancements);
    return $this->_enhancements;
  }

  public function get_enhancements () {
    if ($this->_enhancements === NULL) $this->load_enhancements();
    return $this->_enhancements;
  }

  public function set_enhancements ($list) {
    if (is_array($list)) $this->enhancements = $this->__encode_enhancements($list);
    else if (is_string($list)) $this->enhancements = $list;
    else return FALSE;

    // Re-load the enhancements to properly encode them.
    $this->load_enhancements();

    // Recalculate the bonus.
    $this->calculate_bonus();
  }

  public function add_enhancement ($enhancement) {
    if (is_string($enhancement)) $enhancement = Enhancement::from($enhancement);

    $enhancements = $this->get_enhancements();
    $enhancements[] = $enhancement;

    return $this->set_enhancements($enhancements);
  }

  public function get_random_enhancement () {
    $options = array(
      array('bonus' => Bonus::QUEST_SUCCESS, 'value' => 0.01, 'random_quest' => TRUE),
      array('bonus' => Bonus::QUEST_SPEED, 'value' => -0.01, 'random_quest' => TRUE),
      array('bonus' => Bonus::DEATH_RATE, 'value' => 0.01, 'random_quest' => TRUE),
      array('bonus' => Bonus::QUEST_REWARD_GOLD, 'value' => 0.01, 'random_quest' => TRUE),
      array('bonus' => Bonus::QUEST_REWARD_FAME, 'value' => 0.01, 'random_quest' => TRUE),
      array('bonus' => Bonus::QUEST_REWARD_EXP, 'value' => 0.01, 'random_quest' => TRUE),
      array('bonus' => Bonus::QUEST_REWARD_ITEM, 'value' => 0.01, 'random_quest' => TRUE),

      array('bonus' => Bonus::ITEM_TYPE_FIND_RATE, 'value' => 0.01, 'random_item' => TRUE),

      array('bonus' => Bonus::TRAVEL_SPEED, 'value' => -0.01),
      array('bonus' => Bonus::MISS_RATE, 'value' => -0.01),
      array('bonus' => Bonus::CRIT_RATE, 'value' => 0.01),
    );

    // Choose an enhancement.
    $enhancement = $options[array_rand($options)];

    if (isset($enhancement['random_quest']) && $enhancement['random_quest']) {
      $types = Quest::types();
      $type = $types[array_rand($types)];
      $enhancement['for'] = 'Quest->'.$type;
    }

    if (isset($enhancement['random_item']) && $enhancement['random_item']) {
      $types = ItemType::ALL(false);
      $type = $types[array_rand($types)];
      $enhancement['for'] = 'ItemType->'.$type;
    }

    return new Enhancement ($enhancement);
  }

  /**
   * Format for the array-version of the list of enhancements:
   *
   * $list -> an array of Enhancement objects.
   *
   * Examples:
   *    increase travel speed by 5%:
   *    $list = array(
   *      new Enhancement (array('bonus => Bonus::TRAVEL_SPEED, 'value' => -0.05))
   *    );
   *
   *    increase Boss quest success by 5%:
   *    $list = array(
   *      new Requirement (array('bonus => Bonus::QUEST_SUCCESS, 'value' => 0.05, 'for' => 'Quest->'.Quest::TYPE_BOSS)),
   *    );
   */
  public function __encode_enhancements ($list) {
    if (empty($list)) return '';
    $items = array();

    // Loop through the list and encode it.
    foreach ($list as $enhancement) {
      // Skip any item without a bonus name.
      if (empty($enhancement->bonus)) continue;
      $items[] = $enhancement->encode();
    }

    return implode('|', $items);
  }

  /**
   * Format for the string-version of the list of requirements:
   *
   * $value -> "MOD,VALUE|MOD,VALUE,FOR"   (item separator = "|", divider between mod name, value, and for = ",")
   *    MOD -> Bonus types (example: Bonus::TRAVEL_SPEED)
   *    VALUE -> Bonus value (example: 5, 0.15, -1.05)
   *    FOR -> Specificity of what the bonus applies to (example: Bonus::FOR_DEFAULT or "Quest->".Quest::TYPE_BOSS)
   *
   * Examples:
   *    increase travel speed by 5% -->  "_travel_speed,-0.05"
   *    increase Boss quest success by 5% -->  "_quest_success,0.05,Quest->boss"
   */
  public function __decode_enhancements ($enhancements) {
    $list = array();
    if ($enhancements == '') return $list;
    
    // Bust up the enhancements by the primary separator.
    $enhancements = explode('|', $enhancements);

    // Sub-divide each requirement to find the bonus name and value.
    foreach ($enhancements as $value) {
      $list[] = Enhancement::from($value);
    }

    return $list;
  }



  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function generate_new_adventurer ($allow_class = false, $save_adventurer = true, &$json = null) {
    // Load up the list of adventurer names.
    $save_json = empty($json);
    if (empty($json)) $json = Adventurer::load_adventurer_names_list();

    // Get list of adventurer classes.
    $class_ids = AdventurerClass::all_classes();

    // Determine the gender (needed for first name and icon).
    $gender = rand(1, 100) <= 50 ? Adventurer::GENDER_MALE : Adventurer::GENDER_FEMALE;

    // Check if there are enough first names and icons.
    $first_name_count = count($json['first_names'][$gender]);
    $icon_count = count($json['icons']);
    // If we don't have enough first names or icons, refresh the list to start over.
    if ($first_name_count <= 0) { //|| $icon_count <= 0) {
      $json = refresh_original_adventurer_names_list();
      $first_name_count = count($json['first_names'][$gender]);
      $icon_count = count($json['icons']);
    }
    
    // Get the first name.
    $first_i = rand(0, $first_name_count - 1);
    $first = $json['first_names'][$gender][$first_i];
    unset($json['first_names'][$gender][$first_i]);
    $json['first_names'][$gender] = array_values($json['first_names'][$gender]);
    
    // Get the last name.
    $last_i = rand(0, count($json['last_names']) - 1);
    $last = $json['last_names'][$last_i];
    // Remove last name from list.
    // unset($json['last_names'][$last_i]);
    // $json['last_names'] = array_values($json['last_names']);

    // Determine if they have a special class.
    $adventurer_class_id = $allow_class ? $class_ids[rand(0, count($class_ids) - 1)] : '';

    // Get the icon.
    $icon = ':rpg-adv-'.$gender.':';
    if (!empty($adventurer_class_id)) $icon = ':rpg-adv-'.$gender.'-'.$adventurer_class_id.':';
    // $icon_i = rand(0, count($json['icons']) - 1);
    // $icon = $json['icons'][$icon_i];
    // unset($json['icons'][$icon_i]);
    // $json['icons'] = array_values($json['icons']);

    $adventurer_data = array(
      'gender' => $gender,
      'name' => $first.' '.$last,
      'icon' => $icon,
      'class' => $adventurer_class_id,
      'created' => time(),
      'available' => true,
      'level' => 1,
      'popularity' => 0,
      'exp' => 0,
    );
    $adventurer = new Adventurer ($adventurer_data);
    if ($save_adventurer) $adventurer->save();

    // Add the names back to the JSON file.
    if ($save_json) Adventurer::save_adventurer_names_list($json);

    return $adventurer;
  }

  /**
   * $json -> The JSON-decoded list of adventurer names. Pass this in if you're doing bulk operations and only want to load and save once.
   */
  public static function recycle_adventurer ($adventurer, &$json = null) {
    // If there's no gender, we can't categorize the name, so we're done.
    if (empty($adventurer->gender)) return false;

    // Load up the list of adventurer names.
    $save_json = empty($json);
    if (empty($json)) $json = Adventurer::load_adventurer_names_list();

    // Determine the first and last name.
    $name = explode(' ', $adventurer->name);
    $first_name = array_shift($name);
    $last_name = implode(' ', $name);
    if (!empty($first_name)) $json['first_names'][$adventurer->gender][] = $first_name;

    // Add last name to list. (Last names aren't currently deleted from the list, so no need to add the name back yet.)
    //if (!empty($last_name)) $json['last_names'][] = $last_name;

    // Add icon back to the icons list.
    if (!empty($adventurer->icon)) $json['icons'][$adventurer->gender][] = $adventurer->icon;

    // Add the names back to the JSON file.
    if ($save_json) Adventurer::save_adventurer_names_list($json);

    return true;
  }

  /**
   * Load up the list of Adventurer names that are still available.
   */
  public static function load_adventurer_names_list ($original = false) {
    $file_name = RPG_SERVER_ROOT .($original ? Adventurer::FILENAME_ADVENTURER_NAMES_ORIGINAL : Adventurer::FILENAME_ADVENTURER_NAMES);
    $adventurer_names_json_string = file_get_contents($file_name);
    return json_decode($adventurer_names_json_string, true);
  }

  /**
   * $data -> An array that can be properly encoded using PHP's json_encode function.
   */
  public static function save_adventurer_names_list ($data) {
    // Write out the JSON file to prevent names from being reused.
    $fp = fopen(RPG_SERVER_ROOT . Adventurer::FILENAME_ADVENTURER_NAMES, 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
  }

  /**
   * Replace the working adventurer names list with a copy of the original.
   */
  public static function refresh_original_adventurer_names_list () {
    // Load the original JSON list.
    $json = Adventurer::load_adventurer_names_list(true);

    // Overwrite the working copy with the new list.
    Adventurer::save_adventurer_names_list($json);

    return $json;
  }
}