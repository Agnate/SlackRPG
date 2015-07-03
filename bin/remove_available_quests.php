<?php
/**
 * This script removes all available quests.
 * Example of removing quests from command line:
 * 
 * (from  /rpg_slack/temp  directory)
 *   php bin/remove_available_quests.php -f
 *
 * Options:
 *    -f    Force the script to remove quests.
 *
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');

// Load up functions for removing quests.
require_once(RPG_SERVER_ROOT.'/bin/server/timer_refresh_quests.php');

// Get the parameters passed in from the PHP command line.
$shortopts = 'f::'; // Optional
$longopts = array(
  'force::', // Optional
);
$opts = getopt($shortopts, $longopts);

// If we need to force the removal of quests, do so.
if (isset($opts['f'])) remove_available_quests(true);