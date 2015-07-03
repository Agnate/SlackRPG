<?php
require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');


/**
 * Remove all available quests.
 */
function remove_available_quests ($output_information = false) {
  // Load up the list of adventurer names.
  /*$json = Adventurer::load_adventurer_names_list();

  // Get all of the available adventurers.
  $adventurers = Adventurer::load_multiple(array('available' => true, 'gid' => 0, 'agid' => 0));

  // Get the names from the adventurers and re-insert them into the JSON file of names.
  foreach ($adventurers as $adventurer) {
    // Recycle the Adventurer's name and icon by adding it back to the JSON file.
    Adventurer::recycle_adventurer($adventurer, $json);

    // Delete the adventurer.
    $adventurer->delete();

    if ($output_information) print 'Deleted adventurer: '.$adventurer->name."\n";
  }

  // Add the names back to the JSON file.
  Adventurer::save_adventurer_names_list($json);*/
}


/**
 * Generate new quests and make them available to players.
 */
function generate_new_quests ($output_information = false, $num_quests = 0) {
  // Load up the list of quest names.
  //$json = Quest::load_quest_names_list();

  // Get the current season.
  $season = Season::current();
  if (empty($season)) {
    if ($output_information) print "Could not find an active season.\n";
    return FALSE;
  }

  // Get current map.
  $map = Map::load(array('season' => $season->sid));

  // Get list of all revealed locations.
  $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));

  // If no number of quests are set, do it based on the number of active Guilds.
  if ($num_quests <= 0) {
    $guilds = Guild::load_multiple(array('season' => $season->sid));
    $num_quests = !empty($guilds) ? min(count($guilds), count($locations)) : count($locations);
  }
  
  // Generate some quests.
  $time = time();
  $quests = array();
  for ($i = 0; $i < $num_quests; $i++) {
    // Generate the quest.
    $location = array_splice($locations, array_rand($locations), 1);
    $location = array_pop($location);
    $quest = Quest::generate_quests($location, 1, true);
    $quests[] = $quest;
  }

  // Write out the JSON file to prevent names from being reused.
  //Adventurer::save_adventurer_names_list($json);

  // Output the adventurers that were created.
  if ($output_information) {
    if (count($quests) <= 0) print "No new quests were created.\n";
    else foreach ($quests as $quest) print 'Created new quest: '.$quest->name."\n";
  }
}