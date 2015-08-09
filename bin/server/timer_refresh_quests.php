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
  // $quests = Quest::load_multiple(array('active' => true, 'permanent' => false, 'gid' => 0, 'agid' => 0));

  // // Get the names from the quests and re-insert them into the JSON file of names.
  // foreach ($quests as $quest) {
  //   // Recycle the Quest's name and icon by adding it back to the JSON file.
  //   Quest::recycle_quest($quest, $json);

  //   // Delete the quest.
  //   $quest->delete();

  //   if ($output_information) print 'Deleted quest: '.$quest->name."\n";
  // }

  // Add the names back to the JSON file.
  //Quest::save_quest_names_list($json);
}


/**
 * Generate new quests and make them available to players.
 */
function generate_new_quests ($output_information = false, $num_quests = 0) {
  // Get the current season.
  $season = Season::current();
  if (empty($season)) {
    if ($output_information) print "Could not find an active season.\n";
    return FALSE;
  }

  // Get the map.
  $map = $season->get_map();
  
  // Load up the list of quest names.
  $json = Quest::load_quest_names_list();
  $original_json = Quest::load_quest_names_list(true);

  // Get list of all revealed locations.
  $types = Location::types();
  $locations = Location::load_multiple(array('mapid' => $map->mapid, 'type' => $types, 'revealed' => true));
  $original_locations = $locations;

  // If there are no revealed locations, we can't generate quests yet.
  if (empty($locations)) {
    if ($output_information) print "No new quests were created because no locations have been revealed.\n";
    return;
  }

  // Randomly choose a number of personal quests for all Guilds.
  if ($num_quests <= 0) $num_quests = rand(2, 4);

  // Get all the active Guilds this season.
  $guilds = Guild::load_multiple(array('season' => $season->sid));

  // Generate quests for each Guild.
  $guild_count = 0;
  foreach ($guilds as $guild) {
    // Check the number of current quests, as we do not want to exceed the max quest allowance.
    $cur_quests = $guild->get_quests();
    $quest_count = count($cur_quests);
    $num_new_quests = min($num_quests, Quest::MAX_COUNT - $quest_count);
    // If we've hit or exceeded the limit, do not create any quests.
    if ($num_new_quests <= 0) {
      if ($output_information) print $guild->name.' has hit or exceeded the quest limit ('.Quest::MAX_COUNT.").\n";
      continue;
    }

    $quests = array();
    for ($i = 0; $i < $num_new_quests; $i++) {
      // Choose a location to theme the quest with.
      if (empty($locations)) $locations = $original_locations;
      $location = array_splice($locations, array_rand($locations), 1);
      $location = array_pop($location);
      // If we still don't have a location, there's a problem so break out.
      if (empty($location)) break;
      // Generate the quest.
      $gquest = Quest::generate_personal_quest($guild, $location, $json, $original_json, true);
      if (!empty($gquest)) $quests[] = $gquest;
    }

    $guild_count++;

    if ($output_information) print 'Generated '.count($quests).' quest(s) for '.$guild->name.".\n";
  }

  // Write out the JSON file to prevent names from being reused.
  Quest::save_quest_names_list($json);

  // Output the quests that were created.
  if ($output_information) print 'Created new quests '.$guild_count." Guild(s).\n";
}