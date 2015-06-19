<?php
/**
 * This script removes all available adventurers.
 * Example of removing adventurers from command line:
 * 
 * (from  /rpg_slack/temp  directory)
 *   php bin/clean_out_tavern.php -f
 *
 * Options:
 *    -f    Force the script to remove adventurers.
 *
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');

// Load up functions for removing adventurers.
require_once(RPG_SERVER_ROOT.'/bin/server/timer_refresh_tavern.php');

// Get the parameters passed in from the PHP command line.
$shortopts = 'f::'; // Optional
$longopts = array(
  'force::', // Optional
);
$opts = getopt($shortopts, $longopts);

// If we need to force the removal of adventurers, do so.
if (isset($opts['f'])) clean_out_tavern(true);