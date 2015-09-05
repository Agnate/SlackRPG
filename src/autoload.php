<?php

// Define any constants.
define('RPG_GAME_DIR', RPG_SERVER_ROOT.'/src');

// Add in any class requirements. Not using namespaces, so this has to be done manually.
require_once(RPG_GAME_DIR.'/SlackAttachment.php');
require_once(RPG_GAME_DIR.'/SlackMessage.php');
require_once(RPG_GAME_DIR.'/ServerUtils.php');
require_once(RPG_GAME_DIR.'/Core/RPGEntity.php');
require_once(RPG_GAME_DIR.'/Core/RPGEntitySaveable.php');
add_requires(RPG_GAME_DIR.'/Core');
add_requires(RPG_GAME_DIR.'/Core/Entity');

// Loop through the specified directory and require any files inside that do not start with "__" (two underscores).
function add_requires ($dir, $ignore_autoload = true) {
  chdir($dir);

  foreach (glob("[!__]*.php") as $filename) {
    if ($ignore_autoload && $filename == 'autoload.php') continue;
    require_once($filename);
  }

  $dir_count = explode('/', $dir);
  $dir_count = count($dir_count);
  $up_level = '';

  for( $i = 0; $i < $dir_count; $i++) {
    $up_level .= '../';
  }

  chdir($up_level);
}