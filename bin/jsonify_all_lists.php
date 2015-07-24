<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('config.php');


echo "Generating adventurer names...";
require_once(RPG_SERVER_ROOT.'/bin/raw/adventurer_names.php');

// Assemble the data.
$data = array(
  'first_names' => $first_names,
  'last_names' => $last_names,
  'icons' => $adventurer_icons,
);

// Write out the JSON file.
$fp = fopen(RPG_SERVER_ROOT.'/bin/json/original/adventurer_names.json', 'w');
fwrite($fp, json_encode($data));
fclose($fp);

echo " Done!";



echo "\nGenerating challenge texts...";
require_once(RPG_SERVER_ROOT.'/bin/raw/challenge_texts.php');

// Write out the JSON file.
$fp = fopen(RPG_SERVER_ROOT.'/bin/json/original/challenge_texts.json', 'w');
fwrite($fp, json_encode($challenge_texts));
fclose($fp);

echo " Done!";



echo "\nGenerating location names...";
require_once(RPG_SERVER_ROOT.'/bin/raw/location_and_quest_names.php');

// Write out the JSON file.
$fp = fopen(RPG_SERVER_ROOT.'/bin/json/original/location_names.json', 'w');
fwrite($fp, json_encode($location_names));
fclose($fp);

echo " Done!";



echo "\nGenerating quest names...";
// List is included with location names php file.

// Write out the JSON file.
$fp = fopen(RPG_SERVER_ROOT.'/bin/json/original/quest_names.json', 'w');
fwrite($fp, json_encode($quest_names));
fclose($fp);

echo " Done!";