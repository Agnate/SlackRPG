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
  const NUM_ROWS = 26;
  const NUM_COLS = 26;

  
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

    $num_rows = Map::NUM_ROWS;
    $num_cols = Map::NUM_COLS; // Letter
    $total = $num_rows * $num_cols;

    // Initialize the grid.
    $grid = array();
    $open = array();
    for ($r = 1; $r <= $num_rows; $r++) {
      $grid[$r] = array();
      for ($c = 1; $c <= $num_cols; $c++) {
        $grid[$r][$c] = NULL;
        $open[$r.'-'.$c] = array('row' => $r, 'col' => $c);
      }
    }

    // Create Capital somewhere in the middle.
    $middle_row = floor($num_rows / 2);
    $capital_row = rand($middle_row - 3, $middle_row + 3);
    $middle_col = floor($num_cols / 2);
    $capital_col = rand($middle_col - 3, $middle_col + 3);

    // The capital is actually 4 locations.
    for ($crow = 0; $crow < 2; $crow++) {
      for ($ccol = 0; $ccol < 2; $ccol++) {
        $temprow = $capital_row + $crow;
        $tempcol = $capital_col + $ccol;
        $capital_data = array(
          'mapid' => $this->mapid,
          'gid' => 0,
          'name' => 'The Capital',
          'row' => $temprow,
          'col' => $tempcol,
          'type' => Location::TYPE_CAPITAL,
          'created' => time(),
          'revealed' => true,
        );
        $capital = new Location ($capital_data);
        if ($save_locations) $capital->save();
        $grid[$temprow][$tempcol] = $capital;
        $locations[] = $capital;
        unset($open[$temprow.'-'.$tempcol]);
      }
    }
    

    // Loop through and create the rest of the Locations.
    $num_locs = ceil($total * Map::DENSITY);
    for ($i = 0; $i < $num_locs; $i++) {
      // Find an empty location.
      $open_index = array_rand($open);
      $coord = $open[$open_index];
      unset($open[$open_index]);
      // Generate the location.
      $location = Location::random_location($this, $coord['row'], $coord['col'], NULL, $json, $original_json, $save_locations);
      // if ($save_locations) $location->save();
      $grid[$coord['row']][$coord['col']] = $location;
      $locations[] = $location;
    }

    // Fill the rest of the map with empty locations.
    foreach ($open as $coord) {
      // Generate the location.
      $location = Location::random_location($this, $coord['row'], $coord['col'], Location::TYPE_EMPTY, $json, $original_json, $save_locations);
      // if ($save_locations) $location->save();
      $grid[$coord['row']][$coord['col']] = $location;
      $locations[] = $location;
    }

    return $locations;
  }
}