<?php
/**
 * This script resets all JSON name lists.
 * Example of reseting lists from command line:
 * 
 * (from  /rpg_slack/temp  directory)
 *   php bin/reset_json_lists.php -f
 *
 * Options:
 *    -f    Force the script to reset all lists.
 *
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');

// Get the parameters passed in from the PHP command line.
$shortopts = 'f::'; // Optional
$longopts = array(
  'force::', // Optional
);
$opts = getopt($shortopts, $longopts);

// If we need to force the resetting of lists, do so.
if (isset($opts['f'])) ServerUtils::reset_json_lists(true);