<?php
require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');


/**
 * Remove all available adventurers.
 */
function clean_out_tavern ($output_information = false) {
  // Load up the list of adventurer names.
  $json = Adventurer::load_adventurer_names_list();

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
  Adventurer::save_adventurer_names_list($json);
}


/**
 * Generate new adventurers and make them available to players.
 */
function generate_new_adventurers ($output_information = false, $num_adventurers = 3, $special_class_rate = 5, $special_limit = 1) {
  // Load up the list of adventurer names.
  $json = Adventurer::load_adventurer_names_list();

  // Get list of the 3 current adventurers with the highest level.
  $highest_levels = Adventurer::load_multiple(array(), "ORDER BY level DESC, gid DESC LIMIT 3");
  // Average out the levels to get the highest.
  $levels = 0;
  $highest_level = 0;
  foreach ($highest_levels as $high) {
    $levels += $high->level;
    if ($high->level > $highest_level) $highest_level = $high->level;
  }
  $avg_level = ($levels > 0 && count($highest_levels) > 0) ? max(1, floor(($levels / count($highest_levels)) * 0.90)) : 1;

  // Generate some names.
  $time = time();
  $adventurers = array();
  for ($i = 0; $i < $num_adventurers; $i++) {
    // Determine if they have a special class.
    $has_special_class = $special_limit > 0 && (rand(1, 100) <= $special_class_rate);
    if ($has_special_class) $special_limit--;

    // Generate the adventurer.
    $adventurer = Adventurer::generate_new_adventurer($has_special_class, false, $json);

    // Change the level.
    $adventurer->set_level(rand(1, $avg_level));
    $adventurer->save();
    $adventurers[] = $adventurer;
  }

  // Write out the JSON file to prevent names from being reused.
  Adventurer::save_adventurer_names_list($json);

  // Output the adventurers that were created.
  if ($output_information) foreach ($adventurers as $adventurer) print 'Created new adventurer: '.$adventurer->name."\n";
}