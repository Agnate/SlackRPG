<?php
require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');
require_once(RPG_SERVER_ROOT.'/bin/server/timer_refresh_tavern.php');


/**
 * Reset all JSON lists.
 */
function reset_json_lists ($output_information = false) {
  // Reset adventurer names and icons.
  if ($output_information) print "Resetting adventurer names list...";
  Adventurer::refresh_original_adventurer_names_list();
  if ($output_information) print " Done.\n";

  // Reset challenge texts.
  if ($output_information) print "Resetting challenge texts list...";
  Challenge::refresh_original_texts_list();
  if ($output_information) print " Done.\n";

  // Reset location names.
  if ($output_information) print "Resetting location names list...";
  Location::refresh_original_location_names_list();
  if ($output_information) print " Done.\n";

  // Reset quest names.
  if ($output_information) print "Resetting quest names list...";
  Quest::refresh_original_quest_names_list();
  if ($output_information) print " Done.\n";

  if ($output_information) print "All JSON lists have be reset.\n";
}

function start_new_season ($output_information = false) {
  $time = time();
  $hours = 60 * 60;
  $days = $hours * 24;

  // Clear out the queue.
  if ($output_information) print "Deleting all active queues (if there are any)...";
  $queues = Queue::load_multiple(array());
  if (!empty($queues)) {
    foreach ($queues as $queue) {
      $queue->delete();
    }
  }
  if ($output_information) print " Done.\n";

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

  // Set any adventurers that are in the tavern to unavailable.
  if ($output_information) print "Disabling adventurers in the tavern (if there are any)...";
  $adventurers = Adventurer::load_multiple(array('available' => true));
  if (!empty($adventurers)) {
    foreach ($adventurers as $adventurer) {
      $adventurer->available = false;
      $adventurer->save();
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
  $locations = $map->generate_locations();
  if ($output_information) print " ".count($locations)." created.\n";

  // Set the season to active.
  if ($output_information) print "Setting season to active...";
  $season->active = true;
  $season->save();
  if ($output_information) print " Done.\n";

  // Generate the map image.
  if ($output_information) print "Generating map image...";
  $mapimage = MapImage::generate_image($map);
  if ($output_information) print " Done.\n";

  // Refresh the Tavern to add new adventurers for hire.
  if ($output_information) print "Refreshing the Tavern...\n";
  generate_new_adventurers($output_information);
  if ($output_information) print " Done.\n";

  // Done!
  if ($output_information) print "\nThe new season has now started!\n";
}