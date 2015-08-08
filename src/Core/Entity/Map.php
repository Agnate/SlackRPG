<?php

class Map extends RPGEntitySaveable {
  // Fields
  public $mapid;
  public $season;
  public $created;
  
  // Protected
  protected $_locations;
  protected $_capital;

  // Private vars
  static $fields_int = array('season', 'created');
  static $db_table = 'maps';
  static $default_class = 'Map';
  static $primary_key = 'mapid';

  const DENSITY = 0.15; // Percentage of Locations that are significant
  const CAPITAL_START_ROW = 100;
  const CAPITAL_START_COL = 81;
  const MIN_ROWS = 5; // 5
  const MIN_COLS = 5; // 5

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function load_locations () {
    $this->_locations = Location::load_multiple(array('mapid' => $this->mapid));
  }

  public function get_locations () {
    if (empty($this->_locations)) {
      $this->load_locations();
    }

    return $this->_locations;
  }

  public function load_capital () {
    $this->_capital = Location::load(array('mapid' => $this->mapid, 'type' => Location::TYPE_CAPITAL));
  }

  public function get_capital () {
    if (empty($this->_capital)) {
      $this->load_capital();
    }

    return $this->_capital;
  }

  public function generate_locations ($save_locations = true) {
    $json = Location::load_location_names_list();
    $original_json = Location::load_location_names_list(true);

    $locations = array();

    $info = array(
      'row_lo' => Map::CAPITAL_START_ROW - Map::MIN_ROWS,
      'row_hi' => Map::CAPITAL_START_ROW + Map::MIN_ROWS,
      'col_lo' => Map::CAPITAL_START_COL - Map::MIN_COLS,
      'col_hi' => Map::CAPITAL_START_COL + Map::MIN_COLS,
    );

    $num_rows = ($info['row_hi'] - $info['row_lo'] + 1) + 1;
    $num_cols = ($info['col_hi'] - $info['col_lo'] + 1) + 1; // Letter
    $total = $num_rows * $num_cols;

    // Initialize the grid.
    $grid = array();
    $open = array();
    for ($r = $info['row_lo']; $r <= $info['row_hi']; $r++) {
      $grid[$r] = array();
      for ($c = $info['col_lo']; $c <= $info['col_hi']; $c++) {
        $grid[$r][$c] = NULL;
        $open[$r.'-'.$c] = array('row' => $r, 'col' => $c);
      }
    }

    // Create Capital somewhere in the middle.
    $capital_row = Map::CAPITAL_START_ROW;
    $capital_col = Map::CAPITAL_START_COL;

    $capital_data = array(
      'mapid' => $this->mapid,
      'gid' => 0,
      'name' => 'The Capital',
      'row' => $capital_row,
      'col' => $capital_col,
      'type' => Location::TYPE_CAPITAL,
      'created' => time(),
      'revealed' => true,
      'open' => true,
    );
    $capital = new Location ($capital_data);
    if ($save_locations) $capital->save();
    $grid[$capital_row][$capital_col] = $capital;
    $locations[] = $capital;
    unset($open[$capital_row.'-'.$capital_col]);
    $adjacents = array();

    // Loop through and create the rest of the Locations.
    $num_locs = ceil($total * Map::DENSITY);
    for ($i = 0; $i < $num_locs; $i++) {
      // Find an empty location.
      $open_index = array_rand($open);
      $coord = $open[$open_index];
      unset($open[$open_index]);
      // Generate the location.
      $location = Location::random_location($this, $coord['row'], $coord['col'], NULL, $json, $original_json, false);
      // If we're not saving the location, it means we need to manually assign the star rating (so we can pass in the unsaved Capital).
      $location->assign_star_rating($capital);
      if ($save_locations) $location->save();
      $grid[$coord['row']][$coord['col']] = $location;
      $locations[] = $location;
      // Hold onto locations adjacent to the capital.
      if ($capital->is_adjacent($location->row, $location->col)) $adjacents[] = $location;
    }

    // Fill the rest of the map with empty locations.
    foreach ($open as $coord) {
      // Generate the location.
      $location = Location::random_location($this, $coord['row'], $coord['col'], Location::TYPE_EMPTY, $json, $original_json, $save_locations);
      // if ($save_locations) $location->save();
      $grid[$coord['row']][$coord['col']] = $location;
      $locations[] = $location;
      // Hold onto locations adjacent to the capital.
      if ($capital->is_adjacent($location->row, $location->col)) $adjacents[] = $location;
    }

    // Mark the locations adjacent to the capital as open.
    foreach ($adjacents as $adjacent) {
      $adjacent->open = true;
      if ($save_locations) $adjacent->save();
    }

    return $locations;
  }
}