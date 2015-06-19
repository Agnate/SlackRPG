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