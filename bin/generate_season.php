<?php
/**
 * This script generates a new season.
 * Example of creating a season from command line:
 * 
 * (from  /rpg_slack/temp  directory)
 *   php bin/generate_season.php -f
 *
 * Options:
 *    -f    Force the script to generate a season with the default settings.
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

// If we need to force the generation of a season, do so.
if (isset($opts['f'])) ServerUtils::start_new_season(true);