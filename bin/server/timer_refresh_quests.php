<?php
require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');


/**
 * Remove all available quests.
 */
function remove_available_quests ($output_information = false) {
  // Load up the list of quest names.
  //$json = Quest::load_quest_names_list();

  // Get all of the available quests.
  $quests = Quest::load_multiple(array('active' => true, 'permanent' => false, 'gid' => 0, 'agid' => 0));

  // Get the names from the quests and re-insert them into the JSON file of names.
  foreach ($quests as $quest) {
    // Recycle the Quest's name and icon by adding it back to the JSON file.
    Quest::recycle_quest($quest, $json);

    // Delete the quest.
    $quest->delete();

    if ($output_information) print 'Deleted quest: '.$quest->name."\n";
  }

  // Add the names back to the JSON file.
  //Quest::save_quest_names_list($json);
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
  $types = Location::types();
  $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
  $original_locations = $locations;

  // If no number of quests are set, do it based on the number of active Guilds.
  if ($num_quests <= 0) {
    $guilds = Guild::load_multiple(array('season' => $season->sid));
    $num_guilds = count($guilds);
    $num_guilds += rand(0, ceil($num_guilds / 3));
    $num_locations = count($locations) + rand(0, 2);
    $num_quests = !empty($guilds) ? min($num_guilds, $num_locations) : $num_locations;
  }

  print 'Generating '.$num_quests." quest".($num_quests == 1 ? '' : 's')."...\n";
  
  // Generate some quests.
  $time = time();
  $quests = array();
  for ($i = 0; $i < $num_quests; $i++) {
    // Generate the quest.
    if (empty($locations)) $locations = $original_locations;
    $location = array_splice($locations, array_rand($locations), 1);
    $location = array_pop($location);
    $gen_quests = Quest::generate_quests($location, 1, true);
    if (!empty($gen_quests)) $quests = array_merge($quests, $gen_quests);
  }

  // Write out the JSON file to prevent names from being reused.
  //Quest::save_quest_names_list($json);

  // Output the adventurers that were created.
  if ($output_information) {
    if (count($quests) <= 0) print "No new quests were created.\n";
    else foreach ($quests as $quest) print 'Created new quest: '.$quest->name."\n";
  }
}