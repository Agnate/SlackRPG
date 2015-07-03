<?php
require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');


/**
 * Reset all JSON lists.
 */
function reset_json_lists ($output_information = false) {
  // Reset adventurer names and icons.
  if ($output_information) print "Resetting adventurer names list...";
  Adventurer::refresh_original_adventurer_names_list();
  if ($output_information) print " Done.\n";

  if ($output_information) print "All JSON lists have be reset.\n";
}

function start_new_season ($output_information = false) {
  $time = time();
  $hours = 60 * 60;
  $days = $hours * 24;

  // Disable any currently-active seasons.
  if ($output_information) print "Disabling old seasons (if there are any)...";
  $old_seasons = Season::load_multiple(array('active' => true));
  if (!empty($old_seasons)) {
    foreach ($old_seasons as $old_season) {
      $old_season->active = false;
      $old_season->save();
    }
  }
  if ($output_information) print " Done.\n";

  // Maybe show some kind of "game over" message with some stats and winners?

  // Reset name lists.
  reset_json_lists($output_information);

  // Create a new season.
  if ($output_information) print "Creating new season...";
  $season_data = array('created' => $time, 'duration' => 30*$days, 'active' => false);
  $season = new Season ($season_data);
  $season->save();
  if ($output_information) print " Done.\n";

  // Create new map.
  if ($output_information) print "Creating new map...";
  $map_data = array('season' => $season->sid, 'created' => $time);
  $map = new Map ($map_data);
  $map->save();
  if ($output_information) print " Done.\n";

  // Generate the locations.
  if ($output_information) print "Creating new locations...";
  $row_offset = 10;
  $col_offset = 10;
  $loc_types = Location::types(true);
  $locations = array();
  for ($i = 0; $i <= 5; $i++) {
    // Generate the fake capital.
    if ($i == 0) {
      $location_data = array('mapid' => $map->mapid, 'gid' => 0, 'name' => 'The Capital', 'row' => $row_offset+$i, 'col' => $col_offset+$i, 'type' => Location::TYPE_CAPITAL, 'created' => $time, 'revealed' => true);
      $location = new Location ($location_data);
      $location->save();
      $locations[] = $location;
    }
    // Generate some random locations.
    else {
      $col_diff = rand(-5, 5);
      $stars = rand(1, 5);
      $star_diff = rand(0, 1);
      $location_data = array('mapid' => $map->mapid, 'gid' => 0, 'name' => 'Random Name '.($row_offset+$i), 'row' => $row_offset+$i, 'col' => $col_offset+$col_diff, 'type' => $loc_types[array_rand($loc_types)], 'created' => $time, 'revealed' => false, 'star_min' => ($stars > 1 ? ($stars - $star_diff) : $stars), 'star_max' => $stars);
      $location = new Location ($location_data);
      $location->save();
      $locations[] = $location;

      $col_diff = rand(-5, 5);
      $stars = rand(1, 5);
      $star_diff = rand(0, 1);
      $location_data = array('mapid' => $map->mapid, 'gid' => 0, 'name' => 'Random Name '.($row_offset-$i), 'row' => $row_offset-$i, 'col' => $col_offset+$col_diff, 'type' => $loc_types[array_rand($loc_types)], 'created' => $time, 'revealed' => false, 'star_min' => ($stars > 1 ? ($stars - $star_diff) : $stars), 'star_max' => $stars);
      $location = new Location ($location_data);
      $location->save();
      $locations[] = $location;
    }
  }
  if ($output_information) print " ".count($locations)." created.\n";

  // Set the season to active.
  if ($output_information) print "Setting season to active...";
  $season->active = true;
  $season->save();
  if ($output_information) print " Done.\n";

  // Done!
  if ($output_information) print "\nThe new season has now started!\n";
}