<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('config.php');
require_once(RPG_SERVER_ROOT.'/bin/raw/adventurer_names.php');
require_once(RPG_SERVER_ROOT.'/bin/raw/challenge_texts.php');

// Assemble the data.
$data = array(
  'first_names' => $first_names,
  'last_names' => $last_names,
  'icons' => $adventurer_icons,
);

echo "Generating adventurer names...";

// Write out the JSON file.
$fp = fopen(RPG_SERVER_ROOT.'/bin/json/original/adventurer_names.json', 'w');
fwrite($fp, json_encode($data));
fclose($fp);

echo " Done!";



echo "\nGenerating challenge texts...";

// Write out the JSON file.
$fp = fopen(RPG_SERVER_ROOT.'/bin/json/original/challenge_texts.json', 'w');
fwrite($fp, json_encode($challenge_texts));
fclose($fp);

echo " Done!";