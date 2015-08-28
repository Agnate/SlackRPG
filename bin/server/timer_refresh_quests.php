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
function generate_new_quests ($output_information = false, $num_quests = 0, $num_multiplayer_quests = 0) {
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
  
  // If there are no revealed locations, we can't generate quests yet.
  if (empty($locations)) {
    if ($output_information) print "No new quests were created because no locations have been revealed.\n";
    return;
  }

  // Sort out locations by star-rating.
  $all_locations = array('all' => $locations);
  foreach ($locations as &$location) {
    for ($star = $location->star_min; $star <= $location->star_max; $star++) {
      if ($star == 0) continue;
      if (!isset($all_locations[$star])) $all_locations[$star] = array();
      $all_locations[$star]['loc'.$location->locid] = $location;
    }
  }

  // Get all the active Guilds this season.
  $guilds = Guild::load_multiple(array('season' => $season->sid));
  $total_guild_count = count($guilds);

  // Randomly choose a number of personal quests for all Guilds.
  if ($num_quests <= 0) $num_quests = rand(2, 4);
  if ($num_multiplayer_quests <= 0) $num_multiplayer_quests = rand(1, ceil($total_guild_count / 4));

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

    // Determine the location list that the generator should pull from,
    // choosing lower-star locations for Guilds with weaker adventurers.
    $level = $guild->calculate_adventurer_level_info();
    $star = Quest::calculate_appropriate_star_range($level['lo'], $level['hi']);
    $original_locations = array();
    // Loop through all locations and merge together the viable locations.
    for ($s = $star['lo']; $s <= $star['hi']; $s++) {
      if (!isset($all_locations[$s])) continue;
      $original_locations = array_merge($original_locations, $all_locations[$s]);
    }

    // If there are no locations for this star range (for whatever reason), default to all locations.
    if (empty($original_locations)) $original_locations = $all_locations['all'];

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

  // Generate some multiplayer quests.
  $mquests = array();
  if ($num_multiplayer_quests > 0) {
    // Maximum number of multiplayer quests available is a third of the total registered guilds.
    $max_multiplayer = ceil($total_guild_count / 3);

    // Load up any current multiplayer quests so we don't generate too many.
    $cur_mquests = Quest::load_multiple(array('multiplayer' => true, 'active' => true));

    // Check how many quests we can make.
    $num_multiplayer_quests = min($num_multiplayer_quests, $max_multiplayer - count($cur_mquests));
    if ($num_multiplayer_quests > 0) {
      for ($i = 0; $i < $num_multiplayer_quests; $i++) {
        // Choose a location to theme the quest with.
        if (empty($locations)) $locations = $all_locations['all'];
        $location = array_splice($locations, array_rand($locations), 1);
        $location = array_pop($location);
        // If we still don't have a location, there's a problem so break out.
        if (empty($location)) break;
        // Generate the multiplayer quest.
        $mquest = Quest::generate_multiplayer_quest($location, $json, $original_json, true);
        if (!empty($mquest)) $mquests[] = $mquest;
      }

      if ($output_information) print 'Generated '.count($mquests)." Multi-Guild quest(s).\n";
    }
  }

  // Write out the JSON file to prevent names from being reused.
  Quest::save_quest_names_list($json);

  // Output the quests that were created.
  if ($output_information) print 'Created new quests for '.$guild_count." Guild(s).\n";
}