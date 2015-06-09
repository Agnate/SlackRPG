<?php

/**
 * Name the function with the version number, as this is how we'll verify the update.
 */
function update_version_0_0_0 ($forced = false) {
  $time = time();

  // Create the events Queue table.
  $queue_table = array();
  $queue_table[] = "queue_id INT(11) UNSIGNED AUTO_INCREMENT";
  $queue_table[] = "type VARCHAR(255) NOT NULL";
  $queue_table[] = "type_id INT(11) UNSIGNED NOT NULL";
  $queue_table[] = "created INT(10) UNSIGNED NOT NULL";
  $queue_table[] = "execute INT(10) UNSIGNED NOT NULL";
  $queue_table[] = "PRIMARY KEY ( queue_id )";
  add_update_query( "CREATE TABLE IF NOT EXISTS queue (". implode(',', $queue_table) .")" );

  // Create Guilds table.
  $guilds_table = array();
  $guilds_table[] = "gid INT(11) UNSIGNED AUTO_INCREMENT";
  $guilds_table[] = "username VARCHAR(255) NOT NULL";
  $guilds_table[] = "slack_user_id VARCHAR(255) NOT NULL";
  $guilds_table[] = "name VARCHAR(255) NOT NULL";
  $guilds_table[] = "icon VARCHAR(100) NOT NULL";
  $guilds_table[] = "created INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "updated INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "gold INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "fame INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "adventurer_limit INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "PRIMARY KEY ( gid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS guilds (". implode(',', $guilds_table) .")" );

  // Create Adventurers table.
  $adventurers_table = array();
  $adventurers_table[] = "aid INT(11) UNSIGNED AUTO_INCREMENT";
  $adventurers_table[] = "gid INT(11) UNSIGNED NOT NULL";
  $adventurers_table[] = "agid INT(11) UNSIGNED NOT NULL";
  $adventurers_table[] = "name VARCHAR(255) NOT NULL";
  $adventurers_table[] = "icon VARCHAR(100) NOT NULL";
  $adventurers_table[] = "created INT(10) UNSIGNED NOT NULL";
  $adventurers_table[] = "available TINYINT(1) NOT NULL";
  $adventurers_table[] = "level INT(10) UNSIGNED NOT NULL";
  $adventurers_table[] = "popularity INT(10) UNSIGNED NOT NULL";
  $adventurers_table[] = "exp INT(10) UNSIGNED NOT NULL";
  $adventurers_table[] = "exp_tnl INT(10) UNSIGNED NOT NULL";
  $adventurers_table[] = "class VARCHAR(100) NOT NULL";
  $adventurers_table[] = "champion TINYINT(1) NOT NULL";
  $adventurers_table[] = "PRIMARY KEY ( aid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS adventurers (". implode(',', $adventurers_table) .")" );

  // Create Quests table.
  $quests_table = array();
  $quests_table[] = "qid INT(11) UNSIGNED AUTO_INCREMENT";
  $quests_table[] = "gid INT(11) UNSIGNED NOT NULL";
  $quests_table[] = "agid INT(11) UNSIGNED NOT NULL";
  $quests_table[] = "locid INT(11) UNSIGNED NOT NULL";
  $quests_table[] = "name VARCHAR(255) NOT NULL";
  $quests_table[] = "icon VARCHAR(100) NOT NULL";
  $quests_table[] = "type VARCHAR(100) NOT NULL";
  $quests_table[] = "created INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "active TINYINT(1) NOT NULL";
  $quests_table[] = "permanent TINYINT(1) NOT NULL";
  $quests_table[] = "reward_gold INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "reward_exp INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "reward_fame INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "duration INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "cooldown INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "requirements VARCHAR(255) NOT NULL";
  $quests_table[] = "party_size_min INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "party_size_max INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "PRIMARY KEY ( qid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS quests (". implode(',', $quests_table) .")" );

  // Create Adventuring Group table.
  $ad_group_table = array();
  $ad_group_table[] = "agid INT(11) UNSIGNED AUTO_INCREMENT";
  $ad_group_table[] = "gid INT(11) UNSIGNED NOT NULL";
  $ad_group_table[] = "created INT(10) UNSIGNED NOT NULL";
  $ad_group_table[] = "task_id INT(11) UNSIGNED NOT NULL";
  $ad_group_table[] = "task_type VARCHAR(100) NOT NULL";
  $ad_group_table[] = "task_eta INT(10) UNSIGNED NOT NULL";
  $ad_group_table[] = "completed TINYINT(1) NOT NULL";
  $ad_group_table[] = "PRIMARY KEY ( agid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS adventuring_groups (". implode(',', $ad_group_table) .")" );

  // Create Map table.
  $map_table = array();
  $map_table[] = "mapid INT(11) UNSIGNED AUTO_INCREMENT";
  $map_table[] = "season INT(10) UNSIGNED NOT NULL";
  $map_table[] = "created INT(10) UNSIGNED NOT NULL";
  $map_table[] = "PRIMARY KEY ( mapid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS maps (". implode(',', $map_table) .")" );

  // Create Location table (for maps).
  $location_table = array();
  $location_table[] = "locid INT(11) UNSIGNED AUTO_INCREMENT";
  $location_table[] = "mapid INT(11) UNSIGNED NOT NULL";
  $location_table[] = "gid INT(11) UNSIGNED NOT NULL";
  $location_table[] = "name VARCHAR(255) NOT NULL";
  $location_table[] = "row INT(10) UNSIGNED NOT NULL";
  $location_table[] = "col INT(10) UNSIGNED NOT NULL";
  $location_table[] = "type VARCHAR(255) NOT NULL";
  $location_table[] = "created INT(10) UNSIGNED NOT NULL";
  $location_table[] = "revealed TINYINT(1) NOT NULL";
  $location_table[] = "star_min INT(10) UNSIGNED NOT NULL";
  $location_table[] = "star_max INT(10) UNSIGNED NOT NULL";
  $location_table[] = "PRIMARY KEY ( locid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS locations (". implode(',', $location_table) .")" );

  // Add some Adventurers.
  $adventurers = array();
  $adventurers[] = array(':gid' => '', ':name' => 'Antoine Delorisci', ':icon' => ':antoine:', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  $adventurers[] = array(':gid' => '', ':name' => 'Catherine Hemsley', ':icon' => ':catherine:', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  $adventurers[] = array(':gid' => '', ':name' => 'Gareth Lockheart', ':icon' => ':gareth:', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  $adventurers[] = array(':gid' => '', ':name' => 'Reginald Tigerlily', ':icon' => ':reginald:', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  $adventurers[] = array(':gid' => '', ':name' => 'Morgan LeClaire', ':icon' => ':morgan:', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  $adventurers[] = array(':gid' => '', ':name' => 'Freya von Alfheimr', ':icon' => ':freya:', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  //$adventurers[] = array(':gid' => '', ':name' => '', ':icon' => '', ':created' => '', ':available' => '', ':level' => '', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  foreach ($adventurers as $adventurer) {
    add_update_query("INSERT INTO adventurers (gid, name, icon, created, available, level, exp, exp_tnl, class, champion) VALUES (:gid, :name, :icon, :created, :available, :level, :exp, :exp_tnl, :class, :champion)", $adventurer);
  }

  // Add some Quests.
  $quests = array();
  $quests[] = array(':name' => 'Permanent Quest', ':icon' => ':quest1:', ':type' => 'standard', ':locid' => 0, ':created' => $time, ':active' => true, ':permanent' => true, ':reward_gold' => 100, ':reward_exp' => 150, ':reward_fame' => 0, ':duration' => 20, ':cooldown' => 10, ':party_size_min' => 1, ':party_size_max' => 1);
  $quests[] = array(':name' => 'Fancy Quest', ':icon' => ':quest2:', ':type' => 'standard', ':locid' => 0, ':created' => $time, ':active' => true, ':permanent' => false, ':reward_gold' => 500, ':reward_exp' => 450, ':reward_fame' => 10, ':duration' => 50, ':cooldown' => 60, ':party_size_min' => 1, ':party_size_max' => 3);
  //$quests[] = array(':name' => '', ':icon' => '', ':type' => '', ':locid' => 0, ':created' => $time, ':active' => false, ':permanent' => false, ':reward_gold' => 0, ':reward_exp' => 0, ':duration' => 0, ':cooldown' => 0, ':party_size_min' => 1, ':party_size_max' => 0);
  foreach ($quests as $quest) {
    add_update_query("INSERT INTO quests (name, icon, type, locid, created, active, permanent, reward_gold, reward_exp, reward_fame, duration, cooldown, party_size_min, party_size_max) VALUES (:name, :icon, :type, :locid, :created, :active, :permanent, :reward_gold, :reward_exp, :reward_fame, :duration, :cooldown, :party_size_min, :party_size_max)", $quest);
  }

  // Add a Map.
  $maps = array();
  $maps[] = array(':season' => 1, ':created' => $time);
  //$maps[] = array(':season' => 0, ':created' => $time);
  foreach ($maps as $map) {
    add_update_query("INSERT INTO maps (season, created) VALUES (:season, :created)", $map);
  }

  // Add some Locations.
  $locations = array();
  $locations[] = array(':mapid' => 1, ':gid' => 0, ':name' => "The Capital", ':row' => 13, ':col' => 13, ':type' => 'capital', ':created' => $time, ':revealed' => true);
  $locations[] = array(':mapid' => 1, ':gid' => 0, ':name' => "The Dragon's Cave", ':row' => 1, ':col' => 4, ':type' => 'empty', ':created' => $time, ':revealed' => false);
  $locations[] = array(':mapid' => 1, ':gid' => 0, ':name' => "", ':row' => 14, ':col' => 13, ':type' => 'empty', ':created' => $time, ':revealed' => false);
  //$locations[] = array(':mapid' => 0, ':gid' => 0, ':name' => "", ':row' => 0, ':col' => 0, ':type' => '', ':created' => $time, ':revealed' => false);
  foreach ($locations as $location) {
    add_update_query("INSERT INTO locations (mapid, gid, name, row, col, type, created, revealed) VALUES (:mapid, :gid, :name, :row, :col, :type, :created, :revealed)", $location);
  }
}