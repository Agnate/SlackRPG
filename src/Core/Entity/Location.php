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
  public $star_min;
  public $star_max;

  // Protected
  protected $_map;

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
    // Get the map so we can find the town location.
    $map = $this->get_map();
    // Get the capital in the map.
    $capital = $map->get_capital();
    // Calculate the raw distance and multiply by a time constant.
    $travel_speed_modifier = $this->calculate_travel_speed_modifier($guild, $adventurers, $kit);
    $travel_per_tile = Location::TRAVEL_BASE * $travel_speed_modifier;
    return ceil(sqrt(pow(($capital->row - $this->row), 2) + pow(($capital->col - $this->col), 2)) * $travel_per_tile);
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
    if ( empty($this->_map) ) {
      $this->load_map();
    }

    return $this->_map;
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
    return chr(64 + $num);
  }

  public static function get_number ($letter) {
    return ord(strtoupper($letter)) - 64;
  }

  public static function random_location ($map, $row, $col, $type = NULL, &$json = NULL, $original_json = NULL, $save = TRUE) {
    // Randomize type.
    if (empty($type)) {
      $types = Location::types();
      $type = $types[array_rand($types)];
    }

    // Create location.
    $location_data = array(
      'mapid' => $map->mapid,
      'gid' => 0,
      'name' => Location::generate_name($type, $json, $original_json),
      'row' => $row,
      'col' => $col,
      'type' => $type,
      'created' => time(),
      'revealed' => false,
    );

    $location = new Location ($location_data);

    if ($save) $location->save();

    return $location;
  }

  public static function generate_name ($type, &$json = NULL, $original_json = NULL) {
    // Empty locations are just blank.
    $name = '';
    if ($type == Location::TYPE_EMPTY) return $name;

    // Load up the list of location names.
    $save_json = empty($json);
    if (empty($json)) $json = Location::load_location_names_list();
    if (empty($original_json)) $original_json = Location::load_location_names_list(true);

    // Get the JSON for this location type.
    $type_json =& $json[$type];

    // If it is format-based, pick a format and generate the pieces.
    if (isset($type_json['format'])) {
      $format_index = array_rand($type_json['format']);
      $format = $type_json['format'][$format_index];
      // Create the list of substitution tokens.
      $tokens = array();
      foreach ($type_json as $token => &$data) {
        if ($token == 'format') continue;
        if (!isset($data['parts'])) continue;
        // Generate the token value.
        $join = isset($data['join']) ? $data['join'] : ' ';
        $tokens[$token] = Location::generate_from_parts($data['parts'], $original_json[$type][$token]['parts'], $join);
      }
      $name = str_replace(array_keys($tokens), array_values($tokens), $format);
    }
    // If it's just a series of parts, connect them.
    else if (isset($type_json['parts'])) {
      $join = isset($type_json['join']) ? $type_json['join'] : ' ';
      $name = Location::generate_from_parts($type_json['parts'], $original_json[$type]['parts'], $join);
    }

    // If we're supposed to save the JSON, do so now.
    if ($save_json) Location::save_location_names_list($json);

    return $name;
  }

  protected static function generate_from_parts (&$parts, $original_parts, $join = ' ') {
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

    return implode($join, $name);
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
}