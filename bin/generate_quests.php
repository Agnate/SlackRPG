<?php
/**
 * This script generates new quests.
 * Example of creating quests from command line:
 * 
 * (from  /rpg_slack/temp  directory)
 *   php bin/generate_quests.php -f
 *
 * Options:
 *    -f    Force the script to generate quests with the default settings.
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

// If we need to force the generation of quests, do so.
if (isset($opts['f'])) ServerUtils::generate_new_quests(true);