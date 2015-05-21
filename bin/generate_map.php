<?php
require('../vendor/autoload.php');

$locations = array(
  'town' => 'T',
  'dragon_cave' => 'D',
  'standing_stones' => 'S',
  'mausoleum' => 'M',
  'lava_lake' => 'L',
  'plague_hollows' => 'P',
  'bumbling_brook' => 'B',
  'meekrat_meadows' => 'Mm',
  'sweltering_swamp' => 'Ss',
);

$map = generate_map($locations);

d($map);


/**
 * Rows are numbered, Cols are lettered.
 */
function generate_map ($locations, $rows = 26, $cols = 26) {
  // Build default grid.
  $map_keys = array();
  for ($r = 1; $r <= $rows; $r++) {
    for ($c = 1; $c <= $cols; $c++) {
      $map_keys[$r][get_letter($c)] = ' ';
    }
  }

  // Clone the map_keys array so we can use that to pluck random locations.
  $map_locations = $map_keys;

  // Add unique locations to grid.
  foreach ($locations as $location_id => $symbol) {
    // Find a random spot that's not occupied.
    $row = array_rand($map_locations);
    $col = array_rand($map_locations[$row]);
    unset($map_locations[$row][$col]);
    
    $map_keys[$row][$col] = $symbol;
  }

  return $map_keys;
}

function get_letter ($num) {
  return chr(64 + $num);
}

function get_number ($letter) {
  return ord($letter) - 64;
}