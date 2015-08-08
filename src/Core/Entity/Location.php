<?php

class Location extends RPGEntitySaveable {
  // Fields
  public $locid;
  public $mapid;
  public $gid; // Guild who revealed it.
  public $name;
  public $row;
  public $col;
  public $type;
  public $created;
  public $revealed;
  public $open;
  public $star_min;
  public $star_max;
  public $keywords;
  public $map_icon;

  // Protected
  protected $_map;
  protected $_keywords;

  // Private vars
  static $fields_int = array('created', 'row', 'col', 'star_min', 'star_max');
  static $db_table = 'locations';
  static $default_class = 'Location';
  static $primary_key = 'locid';

  const TYPE_EMPTY = 'empty';
  const TYPE_CAPITAL = 'capital';
  const TYPE_DOMICILE = 'domicile';
  const TYPE_CREATURE = 'creature';
  const TYPE_STRUCTURE = 'structure';
  const TYPE_LANDMARK = 'landmark';

  static $_types = array(Location::TYPE_DOMICILE, Location::TYPE_CREATURE, Location::TYPE_STRUCTURE, Location::TYPE_LANDMARK);

  const FILENAME_LIST_ORIGINAL = '/bin/json/original/location_names.json';
  const FILENAME_LIST = '/bin/json/location_names.json';

  const TRAVEL_BASE = 5; // 10800 = 3 hours/tile (60 * 60 * 3)
  

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function get_display_name () {
    return '`'.$this->get_coord_name().'` '.(!empty($this->name) ? ' '.$this->name : '');
  }

  public function get_coord_name () {
    return Location::get_letter($this->col) .$this->row;
  }

  public function get_duration ($guild, $adventurers, $kit) {
    // Calculate the raw distance and multiply by a time constant.
    $travel_speed_modifier = $this->calculate_travel_speed_modifier($guild, $adventurers, $kit);
    $travel_per_tile = Location::TRAVEL_BASE * $travel_speed_modifier;
    return ceil($this->get_distance() * $travel_per_tile);
  }

  public function get_distance ($capital = null) {
    if (empty($capital))  {
      // Get the map so we can find the town location.
      $map = $this->get_map();
      // Get the capital in the map.
      $capital = $map->get_capital();
    }

    // If we still don't have a capital, return 0.
    if (empty($capital)) return 0;

    return sqrt(pow(($capital->row - $this->row), 2) + pow(($capital->col - $this->col), 2));
  }

  public function calculate_travel_speed_modifier ($guild, $adventurers, $kit) {
    $mod = $guild->get_bonus()->get_mod(Bonus::TRAVEL_SPEED, $this);
    if (!empty($kit)) $mod += $kit->get_bonus()->get_mod(Bonus::TRAVEL_SPEED, $this, Bonus::MOD_DIFF);
    foreach ($adventurers as $adventurer) $mod += $adventurer->get_bonus()->get_mod(Bonus::TRAVEL_SPEED, $this, Bonus::MOD_DIFF);
    return $mod;
  }

  public function load_map () {
    $this->_map = Map::load(array('mapid' => $this->mapid));
  }

  public function get_map () {
    // Load the Map if it hasn't been loaded.
    if (empty($this->_map)) {
      $this->load_map();
    }

    return $this->_map;
  }

  public function load_keywords () {
    $this->_keywords = $this->__decode_keywords($this->keywords);
  }

  public function get_keywords () {
    if ($this->_keywords === NULL) $this->load_keywords();
    return $this->_keywords;
  }

  public function set_keywords ($list) {
    // Encode the keywords and store them.
    $this->keywords = $this->__encode_keywords($list);
    // Reload the keywords.
    $this->load_keywords();
  }

  /**
   * Token options:
   *
   *    !fullname -> The full name of the location.
   *    !creature -> The creature name (only for Creature locations).
   *    !creatureadj -> The adjective describing the creature name (only for Creature locations).
   *    !creaturefull -> The creature name including the adjective (only for Creature locations).
   *    !name -> The name of the domicile (only for Domicile locations).
   *    !dwelling -> The name of the dwelling/domicile (only for Domicile or Creature locations).
   */
  public function get_tokens_from_keywords () {
    $tokens = array(
      '!fullname' => $this->name,
    );

    // Sift through the keywords if it's important.
    $keywords = $this->get_keywords();
    switch ($this->type) {
      case Location::TYPE_CREATURE:
        $tokens['!creature'] = $keywords[1];
        $tokens['!creatureadj'] = $keywords[0];
        $tokens['!creaturefull'] = $keywords[0].' '.$keywords[1];
        $tokens['!dwelling'] = $keywords[2];
        break;

      case Location::TYPE_DOMICILE:
        $tokens['!name'] = $keywords[0];
        $tokens['!dwelling'] = $keywords[1];
        break;
    }

    return $tokens;
  }

  /**
   * Extract what category of icon we want to use on the map based on the keywords.
   */
  public function get_map_icon () {
    if ($this->type == Location::TYPE_CAPITAL) return 'capital';

    $keywords = $this->get_keywords();

    switch ($this->type) {
      default:
        // Take the last keyword, split by space and take the first word of the group.
        $pieces = explode(' ', array_pop($keywords));
        return strtolower(array_shift($pieces));
    }
  }

  public function get_adjacent_locations ($create_new = FALSE, &$json = NULL, $original_json = NULL, $save_new = TRUE) {
    $row = $this->row;
    $col = $this->col;
    $locations = array();

    if ($create_new) $map = $this->get_map();

    // Get all locations to the NSEW of this one.
    $coords = array(
      array('row' => $row - 1, 'col' => $col),
      array('row' => $row + 1, 'col' => $col),
      array('row' => $row, 'col' => $col - 1),
      array('row' => $row, 'col' => $col + 1),
    );

    foreach ($coords as $coord) {
      $data = array(
        'row' => $coord['row'],
        'col' => $coord['col'],
        'mapid' => $this->mapid,
      );
      $location = Location::load($data);

      // If there's no location, create a new one or continue.
      if (empty($location)) {
        if (!$create_new) continue;
        // Use Map density to decide if it's empty or not.
        $type = Location::TYPE_EMPTY;
        // Generate a random non-empty type if we randomize the density and get a non-empty location.
        if (rand(0, 100) <= (Map::DENSITY * 100)) $type = NULL;
        $location = Location::random_location($map, $data['row'], $data['col'], $type, $json, $original_json, $save_new);
      }

      $locations[] = $location;
    }

    return $locations;
  }

  public function is_adjacent ($row, $col) {
    if (abs($this->row - $row) <= 1 && $this->col == $col) return true;
    if (abs($this->col - $col) <= 1 && $this->row == $row) return true;
    return false;
  }

  public function assign_star_rating ($capital = null) {
    // Assign star rating based on proximity to the Capital.
    if ($this->type != Location::TYPE_EMPTY) {
      $dist = $this->get_distance($capital);
      // If we can't calculate the distance (or it's the capital), star-rating should be zero.
      if ($dist === 0) return;

      // 0-2.5 = 1-star
      // 2.6-5 = 2-star
      // 5.1-7.5 = 3-star
      // 7.6-10 = 4-star
      // 10+ = 5-star
      if ($dist <= 2.5) $this->star_max = 1;
      else if ($dist <= 5) $this->star_max = 2;
      else if ($dist <= 7.5) $this->star_max = 3;
      else if ($dist <= 10) $this->star_max = 4;
      else $this->star_max = 5;

      if ($this->star_max > 1) $this->star_min = $this->star_max - rand(0, 1);
      else $this->star_min = $this->star_max;
    }
  }

  

  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function types ($include_empty = false) {
    return $include_empty ? array_merge(Location::$_types, array(Location::TYPE_EMPTY)) : Location::$_types;
  }

  public static function get_letter ($num) {
    if ($num > 26) {
      $first = floor($num / 26);
      $second = $num % 26;
      if ($second == 0) {
        $first--;
        $second = 26;
      }
      return chr(64 + $first) . chr(64 + $second);;
    }

    return chr(64 + $num);
  }

  public static function get_number ($letter) {
    $letter = strtoupper($letter);
    if (strlen($letter) > 1) {
      // Separate letters.
      $first = substr($letter, 0, 1);
      $second = substr($letter, 1, 1);
      // Convert to numbers.
      $first = ord($first) - 64;
      $second = ord($second) - 64;
      // Reverse engineer.
      return ($first * 26) + $second;
    }
    return ord($letter) - 64;
  }

  public static function random_location ($map, $row, $col, $type = NULL, &$json = NULL, $original_json = NULL, $save = TRUE) {
    // Randomize type.
    if (empty($type)) {
      $types = Location::types();
      $type = $types[array_rand($types)];
    }

    // Get name and keywords.
    $name = '';
    $keywords = '';
    if ($type != Location::TYPE_EMPTY) {
      $name_keywords = Location::generate_name($type, $json, $original_json);
      $name = $name_keywords['name'];
      $keywords = Location::__encode_keywords($name_keywords['keywords']);
    }

    // Create location.
    $location_data = array(
      'mapid' => $map->mapid,
      'gid' => 0,
      'name' => $name,
      'row' => $row,
      'col' => $col,
      'type' => $type,
      'created' => time(),
      'revealed' => false,
      'open' => false,
      'keywords' => $keywords,
    );

    $location = new Location ($location_data);

    // Assign star rating based on proximity to the Capital.
    $location->assign_star_rating();

    if ($save) $location->save();

    return $location;
  }

  public static function generate_name ($type, &$json = NULL, $original_json = NULL) {
    // Empty locations are just blank.
    $info = array(
      'name' => '',
      'keywords' => array(),
    );
    if ($type == Location::TYPE_EMPTY) return $info;

    // Load up the list of location names.
    $save_json = empty($json);
    if (empty($json)) $json = Location::load_location_names_list();
    if (empty($original_json)) $original_json = Location::load_location_names_list(true);

    // Get the JSON for this location type.
    $json_list =& $json[$type];
    $original_json_list = $original_json[$type];

    // Randomly generate the name.
    $info = JSONList::generate_name($json_list, $original_json_list);

    // If we're supposed to save the JSON, do so now.
    if ($save_json) Location::save_location_names_list($json);

    return $info;
  }

  protected static function generate_from_parts (&$parts, $original_parts) {
    if (is_string($parts)) return $parts;
    if (!is_array($parts)) return '';

    // If there are arrays for each part, randomly pick one.
    $name = array();
    foreach ($parts as $key => &$list) {
      if (is_array($list)) {
        $index = array_rand($list);
        // Re-index
        if ($index === NULL) {
          $list = $original_parts[$key];
          $index = array_rand($list);
        }
        $name[] = $list[$index];
        unset($list[$index]);
      }
      else if (is_string($list)) $name[] = $list;
    }

    return $name;
  }

  /**
   * Load up the list of location names that are still available.
   */
  public static function load_location_names_list ($original = false) {
    $file_name = RPG_SERVER_ROOT .($original ? Location::FILENAME_LIST_ORIGINAL : Location::FILENAME_LIST);
    $names_json_string = file_get_contents($file_name);
    return json_decode($names_json_string, true);
  }

  /**
   * $data -> An array that can be properly encoded using PHP's json_encode function.
   */
  public static function save_location_names_list ($data) {
    // Write out the JSON file to prevent names from being reused.
    $fp = fopen(RPG_SERVER_ROOT . Location::FILENAME_LIST, 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
  }

  /**
   * Replace the working location names list with a copy of the original.
   */
  public static function refresh_original_location_names_list () {
    // Load the original JSON list.
    $json = Location::load_location_names_list(true);

    // Overwrite the working copy with the new list.
    Location::save_location_names_list($json);

    return $json;
  }

  protected static function __encode_keywords ($list) {
    return is_array($list) ? implode('|', $list) : '';
  }

  protected static function __decode_keywords ($string) {
    return empty($string) ? array() : explode('|', $string);
  }
}