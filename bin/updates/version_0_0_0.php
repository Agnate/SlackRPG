<?php

/**
 * Name the function with the version number, as this is how we'll verify the update.
 */
function update_version_0_0_0 ($forced = false) {
  $time = time();
  $hours = 60 * 60;

  // Create the events Queue table.
  $queue_table = array();
  $queue_table[] = "queue_id INT(11) UNSIGNED AUTO_INCREMENT";
  $queue_table[] = "gid INT(11) UNSIGNED NOT NULL";
  $queue_table[] = "type VARCHAR(255) NOT NULL";
  $queue_table[] = "type_id INT(11) UNSIGNED NOT NULL";
  $queue_table[] = "created INT(10) UNSIGNED NOT NULL";
  $queue_table[] = "execute INT(10) UNSIGNED NOT NULL";
  $queue_table[] = "PRIMARY KEY ( queue_id )";
  add_update_query( "CREATE TABLE IF NOT EXISTS queue (". implode(',', $queue_table) .")" );

  // Create Guilds table.
  $guilds_table = array();
  $guilds_table[] = "gid INT(11) UNSIGNED AUTO_INCREMENT";
  $guilds_table[] = "admin TINYINT(1) NOT NULL";
  $guilds_table[] = "username VARCHAR(255) NOT NULL";
  $guilds_table[] = "slack_user_id VARCHAR(255) NOT NULL";
  $guilds_table[] = "season INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "name VARCHAR(255) NOT NULL";
  $guilds_table[] = "icon VARCHAR(100) NOT NULL";
  $guilds_table[] = "created INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "updated INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "gold INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "fame INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "adventurer_limit INT(10) UNSIGNED NOT NULL";
  $guilds_table[] = "upgrades VARCHAR(255) NOT NULL";
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
  $adventurers_table[] = "dead TINYINT(1) NOT NULL";
  $adventurers_table[] = "gender VARCHAR(10) NOT NULL";
  $adventurers_table[] = "enhancements VARCHAR(255) NOT NULL";
  $adventurers_table[] = "undying TINYINT(1) NOT NULL";
  $adventurers_table[] = "revivable TINYINT(1) NOT NULL";
  $adventurers_table[] = "death_date INT(10) UNSIGNED NOT NULL";
  $adventurers_table[] = "bossed TINYINT(1) NOT NULL";
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
  $quests_table[] = "stars INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "created INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "active TINYINT(1) NOT NULL";
  $quests_table[] = "completed TINYINT(1) NOT NULL";
  $quests_table[] = "reward_gold INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "reward_exp INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "reward_fame INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "duration INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "cooldown INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "requirements VARCHAR(255) NOT NULL";
  $quests_table[] = "party_size_min INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "party_size_max INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "level INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "success_rate INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "death_rate INT(10) UNSIGNED NOT NULL";
  $quests_table[] = "kit_id INT(11) UNSIGNED NOT NULL";
  $quests_table[] = "multiplayer TINYINT(1) NOT NULL";
  $quests_table[] = "boss_aid INT(11) UNSIGNED NOT NULL";
  $quests_table[] = "keywords LONGTEXT NOT NULL";
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

  // Create Season table.
  $season_table = array();
  $season_table[] = "sid INT(11) UNSIGNED AUTO_INCREMENT";
  $season_table[] = "created INT(10) UNSIGNED NOT NULL";
  $season_table[] = "duration INT(10) UNSIGNED NOT NULL";
  $season_table[] = "active TINYINT(1) NOT NULL";
  $season_table[] = "PRIMARY KEY ( sid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS seasons (". implode(',', $season_table) .")" );

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
  $location_table[] = "keywords VARCHAR(255) NOT NULL";
  $location_table[] = "map_icon VARCHAR(255) NOT NULL";
  $location_table[] = "open TINYINT(1) NOT NULL";
  $location_table[] = "PRIMARY KEY ( locid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS locations (". implode(',', $location_table) .")" );

  // Create Upgrade table.
  $upgrade_table = array();
  $upgrade_table[] = "upid INT(11) UNSIGNED AUTO_INCREMENT";
  $upgrade_table[] = "name_id VARCHAR(100) NOT NULL";
  $upgrade_table[] = "name VARCHAR(255) NOT NULL";
  $upgrade_table[] = "description VARCHAR(255) NOT NULL";
  $upgrade_table[] = "cost INT(10) UNSIGNED NOT NULL";
  $upgrade_table[] = "duration INT(10) UNSIGNED NOT NULL";
  $upgrade_table[] = "requires VARCHAR(255) NOT NULL";
  $upgrade_table[] = "PRIMARY KEY ( upid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS upgrades (". implode(',', $upgrade_table) .")" );

  // Create Adventurer Class table.
  $adventurer_class_table = array();
  $adventurer_class_table[] = "acid INT(11) UNSIGNED AUTO_INCREMENT";
  $adventurer_class_table[] = "name_id VARCHAR(100) NOT NULL";
  $adventurer_class_table[] = "name VARCHAR(255) NOT NULL";
  $adventurer_class_table[] = "icon VARCHAR(100) NOT NULL";
  $adventurer_class_table[] = "class_name VARCHAR(100) NOT NULL";
  $adventurer_class_table[] = "PRIMARY KEY ( acid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS adventurer_classes (". implode(',', $adventurer_class_table) .")" );

  // Create ItemTemplate table.
  $item_template_table = array();
  $item_template_table[] = "itid INT(11) UNSIGNED AUTO_INCREMENT";
  $item_template_table[] = "name_id VARCHAR(100) NOT NULL";
  $item_template_table[] = "name VARCHAR(255) NOT NULL";
  $item_template_table[] = "icon VARCHAR(100) NOT NULL";
  $item_template_table[] = "type VARCHAR(100) NOT NULL";
  $item_template_table[] = "rarity_lo INT(10) UNSIGNED NOT NULL";
  $item_template_table[] = "rarity_hi INT(10) UNSIGNED NOT NULL";
  $item_template_table[] = "cost INT(10) UNSIGNED NOT NULL";
  $item_template_table[] = "for_sale TINYINT(1) NOT NULL";
  $item_template_table[] = "PRIMARY KEY ( itid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS item_templates (". implode(',', $item_template_table) .")" );

  // Create Item table.
  $item_table = array();
  $item_table[] = "iid INT(11) UNSIGNED AUTO_INCREMENT";
  $item_table[] = "itid INT(11) UNSIGNED NOT NULL";
  $item_table[] = "gid INT(11) UNSIGNED NOT NULL";
  $item_table[] = "name_id VARCHAR(100) NOT NULL";
  $item_table[] = "name VARCHAR(255) NOT NULL";
  $item_table[] = "icon VARCHAR(100) NOT NULL";
  $item_table[] = "type VARCHAR(100) NOT NULL";
  $item_table[] = "rarity_lo INT(10) UNSIGNED NOT NULL";
  $item_table[] = "rarity_hi INT(10) UNSIGNED NOT NULL";
  $item_table[] = "cost INT(10) UNSIGNED NOT NULL";
  $item_table[] = "for_sale TINYINT(1) NOT NULL";
  $item_table[] = "on_hold TINYINT(1) NOT NULL";
  $item_table[] = "extra_data VARCHAR(255) NOT NULL";
  $item_table[] = "PRIMARY KEY ( iid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS items (". implode(',', $item_table) .")" );

  // Create Challenge table.
  $challenge_table = array();
  $challenge_table[] = "chid INT(11) UNSIGNED AUTO_INCREMENT";
  $challenge_table[] = "challenger_id INT(11) UNSIGNED NOT NULL";
  $challenge_table[] = "challenger_champ INT(11) UNSIGNED NOT NULL";
  $challenge_table[] = "challenger_moves VARCHAR(255) NOT NULL";
  $challenge_table[] = "opponent_id INT(11) UNSIGNED NOT NULL";
  $challenge_table[] = "opponent_champ INT(11) UNSIGNED NOT NULL";
  $challenge_table[] = "opponent_moves VARCHAR(255) NOT NULL";
  $challenge_table[] = "created INT(10) UNSIGNED NOT NULL";
  $challenge_table[] = "wager INT(10) UNSIGNED NOT NULL";
  $challenge_table[] = "confirmed TINYINT(1) NOT NULL";
  $challenge_table[] = "winner INT(11) UNSIGNED NOT NULL";
  $challenge_table[] = "reward INT(10) UNSIGNED NOT NULL";
  $challenge_table[] = "PRIMARY KEY ( chid )";
  add_update_query( "CREATE TABLE IF NOT EXISTS challenges (". implode(',', $challenge_table) .")" );

  // Add some Adventurers.
  // $adventurers = array();
  // $adventurers[] = array(':gid' => '', ':name' => 'Antoine Delorisci', ':icon' => ':antoine:', ':gender' => 'male', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => 'leywalker', ':champion' => false);
  // $adventurers[] = array(':gid' => '', ':name' => 'Catherine Hemsley', ':icon' => ':catherine:', ':gender' => 'female', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  // $adventurers[] = array(':gid' => '', ':name' => 'Gareth Lockheart', ':icon' => ':gareth:', ':gender' => 'male', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  // $adventurers[] = array(':gid' => '', ':name' => 'Reginald Tigerlily', ':icon' => ':reginald:', ':gender' => 'male', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => 'judge', ':champion' => false);
  // $adventurers[] = array(':gid' => '', ':name' => 'Morgan LeClaire', ':icon' => ':morgan:', ':gender' => 'female', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  // $adventurers[] = array(':gid' => '', ':name' => 'Freya von Alfheimr', ':icon' => ':freya:', ':gender' => 'female', ':created' => $time, ':available' => true, ':level' => '1', ':exp' => 0, ':exp_tnl' => 1, ':class' => 'magus', ':champion' => false);
  // //$adventurers[] = array(':gid' => '', ':name' => '', ':icon' => '', ':gender' => '', ':created' => '', ':available' => '', ':level' => '', ':exp' => 0, ':exp_tnl' => 1, ':class' => '', ':champion' => false);
  // foreach ($adventurers as $adventurer) {
  //   add_update_query("INSERT INTO adventurers (gid, name, icon, gender, created, available, level, exp, exp_tnl, class, champion) VALUES (:gid, :name, :icon, :gender, :created, :available, :level, :exp, :exp_tnl, :class, :champion)", $adventurer);
  // }

  // Add some Quests.
  // $quests = array();
  // $quests[] = array(':name' => 'Permanent Quest', ':icon' => ':pushpin:', ':type' => 'standard', ':locid' => 3, ':stars' => 1, ':created' => $time, ':active' => true, ':permanent' => true, ':reward_gold' => 100, ':reward_exp' => 150, ':reward_fame' => 0, ':duration' => 20, ':cooldown' => 10, ':party_size_min' => 1, ':party_size_max' => 1, ':level' => 5, ':success_rate' => 100, ':death_rate' => 0);
  // $quests[] = array(':name' => 'Fancy Quest', ':icon' => ':pushpin:', ':type' => 'standard', ':locid' => 2, ':stars' => 4, ':created' => $time, ':active' => true, ':permanent' => false, ':reward_gold' => 500, ':reward_exp' => 450, ':reward_fame' => 10, ':duration' => 50, ':cooldown' => 60, ':party_size_min' => 1, ':party_size_max' => 3, ':level' => 30, ':success_rate' => 80, ':death_rate' => 5);
  // //$quests[] = array(':name' => '', ':icon' => '', ':type' => '', ':locid' => 0, ':stars' => 0, ':created' => $time, ':active' => false, ':permanent' => false, ':reward_gold' => 0, ':reward_exp' => 0, ':duration' => 0, ':cooldown' => 0, ':party_size_min' => 1, ':party_size_max' => 0, ':level' => 1, ':success_rate' => 100, ':death_rate' => 0);
  // foreach ($quests as $quest) {
  //   add_update_query("INSERT INTO quests (name, icon, type, locid, stars, created, active, permanent, reward_gold, reward_exp, reward_fame, duration, cooldown, party_size_min, party_size_max, level, success_rate, death_rate) VALUES (:name, :icon, :type, :locid, :stars, :created, :active, :permanent, :reward_gold, :reward_exp, :reward_fame, :duration, :cooldown, :party_size_min, :party_size_max, :level, :success_rate, :death_rate)", $quest);
  // }

  // Add a Season.
  /*$seasons = array();
  $seasons[] = array(':created' => $time, ':duration' => $hours * 24 * 30, ':active' => true);
  //$seasons[] = array(':created' => $time, ':duration' => 0, ':active' => true);
  foreach ($seasons as $season) {
    add_update_query("INSERT INTO seasons (created, duration, active) VALUES (:created, :duration, :active)", $season);
  }*/

  // Add a Map.
  /*$maps = array();
  $maps[] = array(':season' => 1, ':created' => $time);
  //$maps[] = array(':season' => 0, ':created' => $time);
  foreach ($maps as $map) {
    add_update_query("INSERT INTO maps (season, created) VALUES (:season, :created)", $map);
  }*/

  // Add some Locations.
  /*$locations = array();
  $locations[] = array(':mapid' => 1, ':gid' => 0, ':name' => "The Capital", ':row' => 13, ':col' => 13, ':type' => 'capital', ':created' => $time, ':revealed' => true);
  $locations[] = array(':mapid' => 1, ':gid' => 0, ':name' => "The Dragon's Cave", ':row' => 1, ':col' => 4, ':type' => 'empty', ':created' => $time, ':revealed' => false);
  $locations[] = array(':mapid' => 1, ':gid' => 0, ':name' => "", ':row' => 14, ':col' => 13, ':type' => 'empty', ':created' => $time, ':revealed' => false);
  //$locations[] = array(':mapid' => 0, ':gid' => 0, ':name' => "", ':row' => 0, ':col' => 0, ':type' => '', ':created' => $time, ':revealed' => false);
  foreach ($locations as $location) {
    add_update_query("INSERT INTO locations (mapid, gid, name, row, col, type, created, revealed) VALUES (:mapid, :gid, :name, :row, :col, :type, :created, :revealed)", $location);
  }*/

  // TEMP: change hours to seconds so it can be easily tested. (Comment the line below to set back to proper value)
  // $hours = 0.5;

  // Add some Upgrades.
  $upgrades = array();
  $upgrades[] = array(':name_id' => "dorm1", ':name' => "Dormitory 1", ':description' => "increase max adventurer limit by 1", ':cost' => 750, ':duration' => (3 * $hours), ':requires' => '');
  $upgrades[] = array(':name_id' => "dorm2", ':name' => "Dormitory 2", ':description' => "increase max adventurer limit by 1", ':cost' => 1250, ':duration' => (6 * $hours), ':requires' => 'upgrade,dorm1');
  $upgrades[] = array(':name_id' => "dorm3", ':name' => "Dormitory 3", ':description' => "increase max adventurer limit by 1", ':cost' => 2250, ':duration' => (12 * $hours), ':requires' => 'upgrade,dorm2');
  $upgrades[] = array(':name_id' => "dorm4", ':name' => "Dormitory 4", ':description' => "increase max adventurer limit by 1", ':cost' => 3500, ':duration' => (18 * $hours), ':requires' => 'upgrade,dorm3');
  $upgrades[] = array(':name_id' => "dorm5", ':name' => "Dormitory 5", ':description' => "increase max adventurer limit by 1", ':cost' => 5000, ':duration' => (24 * $hours), ':requires' => 'upgrade,dorm4');
  $upgrades[] = array(':name_id' => "dorm6", ':name' => "Dormitory 6", ':description' => "increase max adventurer limit by 1", ':cost' => 10000, ':duration' => (30 * $hours), ':requires' => 'upgrade,dorm5');
  $upgrades[] = array(':name_id' => "speed1", ':name' => "Transportation: Horse", ':description' => "increase speed by 5%", ':cost' => 3000, ':duration' => (12 * $hours), ':requires' => 'item,animal_horse,4');
  $upgrades[] = array(':name_id' => "speed2", ':name' => "Transportation: Pegasus", ':description' => "increase speed by 5%", ':cost' => 10000, ':duration' => (18 * $hours), ':requires' => 'upgrade,speed1|item,animal_pegasus,4');
  $upgrades[] = array(':name_id' => "speed3", ':name' => "Transportation: Airship", ':description' => "increase speed by 10%", ':cost' => 20000, ':duration' => (24 * $hours), ':requires' => 'upgrade,speed2|item,ore_steel,10|item,ore_iron,10|item,ore_adamantine,3|item,ore_crystal');
  $upgrades[] = array(':name_id' => "equip1", ':name' => "Equipment: Iron", ':description' => "increase quest success rate by 2%", ':cost' => 750, ':duration' => (12 * $hours), ':requires' => 'item,ore_iron,3');
  $upgrades[] = array(':name_id' => "equip2", ':name' => "Equipment: Steel", ':description' => "increase quest success rate by 2%", ':cost' => 2500, ':duration' => (12 * $hours), ':requires' => 'upgrade,equip1|item,ore_steel,3');
  $upgrades[] = array(':name_id' => "equip3", ':name' => "Equipment: Mithril", ':description' => "increase quest success rate by 2%", ':cost' => 6000, ':duration' => (18 * $hours), ':requires' => 'upgrade,equip2|item,ore_mithril,3');
  $upgrades[] = array(':name_id' => "equip4", ':name' => "Equipment: Inlaid Crystal", ':description' => "increase quest success rate by 2%", ':cost' => 7000, ':duration' => (18 * $hours), ':requires' => 'upgrade,equip3|item,ore_crystal,3');
  $upgrades[] = array(':name_id' => "equip5", ':name' => "Equipment: Diamond Edged", ':description' => "increase quest success rate by 2%", ':cost' => 10000, ':duration' => (24 * $hours), ':requires' => 'upgrade,equip4|item,ore_diamond,3');
  $upgrades[] = array(':name_id' => "equip6", ':name' => "Equipment: Adamantine", ':description' => "increase quest success rate by 2%", ':cost' => 12000, ':duration' => (24 * $hours), ':requires' => 'upgrade,equip5|item,ore_adamantine,3');
  $upgrades[] = array(':name_id' => "equip7", ':name' => "Equipment: Demonsteel", ':description' => "increase quest success rate by 2%", ':cost' => 18000, ':duration' => (30 * $hours), ':requires' => 'upgrade,equip6|item,ore_demonite,3');
  $upgrades[] = array(':name_id' => "equip8", ':name' => "Equipment: Godsteel", ':description' => "increase quest success rate by 2%", ':cost' => 20000, ':duration' => (30 * $hours), ':requires' => 'upgrade,equip7|item,ore_godstone,3');
  $upgrades[] = array(':name_id' => "heal1", ':name' => "Healer's Garden", ':description' => "reduce death rate by 2%", ':cost' => 1100, ':duration' => (12 * $hours), ':requires' => 'item,herb_green,5');
  $upgrades[] = array(':name_id' => "heal2", ':name' => "Apothecary", ':description' => "reduce death rate by 2%", ':cost' => 4500, ':duration' => (18 * $hours), ':requires' => 'upgrade,heal1|item,herb_red,5');
  //$upgrades[] = array(':name_id' => "", ':name' => "", ':description' => "", ':cost' => 0, ':duration' => 0, ':requires' => '');
  foreach ($upgrades as $upgrade) {
    add_update_query("INSERT INTO upgrades (name_id, name, description, cost, duration, requires) VALUES (:name_id, :name, :description, :cost, :duration, :requires)", $upgrade);
  }

  // Add some Adventurer Classes.
  $adventurer_classes = array();
  //$adventurer_classes[] = array(':name_id' => "vagabond", ':name' => "Vagabond", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "shaman", ':name' => "Shaman", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "brigand", ':name' => "Brigand", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "judge", ':name' => "Judge", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "magus", ':name' => "Magus", ':icon' => "", ':class_name' => "");
  //$adventurer_classes[] = array(':name_id' => "leywalker", ':name' => "Leywalker", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "dragoon", ':name' => "Dragoon", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "strider", ':name' => "Strider", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "oracle", ':name' => "Oracle", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "juggernaut", ':name' => "Juggernaut", ':icon' => "", ':class_name' => "");
  //$adventurer_classes[] = array(':name_id' => "artificer", ':name' => "Artificer", ':icon' => "", ':class_name' => "");
  $adventurer_classes[] = array(':name_id' => "undead", ':name' => "Undead", ':icon' => "", ':class_name' => "");
  //$adventurer_classes[] = array(':name_id' => "", ':name' => "", ':icon' => "", ':class_name' => "");
  foreach ($adventurer_classes as $adventurer_class) {
    add_update_query("INSERT INTO adventurer_classes (name_id, name, icon, class_name) VALUES (:name_id, :name, :icon, :class_name)", $adventurer_class);
  }

  // Add ItemTemplates.
  $item_templates = array();
  $item_templates[] = array(':name_id' => 'powerstone_shaman', ':name' => 'Shaman Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_brigand', ':name' => 'Brigand Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_judge', ':name' => 'Judge Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_magus', ':name' => 'Magus Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_dragoon', ':name' => 'Dragoon Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_strider', ':name' => 'Strider Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_oracle', ':name' => 'Oracle Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'powerstone_juggernaut', ':name' => 'Juggernaut Powerstone', ':icon' => '', ':type' => 'powerstone', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  
  $item_templates[] = array(':name_id' => 'ore_iron', ':name' => 'Iron Ore', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 0, ':rarity_hi' => 2, ':cost' => 200, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_steel', ':name' => 'Steel', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 2, ':rarity_hi' => 3, ':cost' => 300, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_mithril', ':name' => 'Mithril Ore', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 3, ':rarity_hi' => 4, ':cost' => 400, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_crystal', ':name' => 'Crystal Shard', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 3, ':rarity_hi' => 4, ':cost' => 500, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_diamond', ':name' => 'Diamond', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 4, ':rarity_hi' => 5, ':cost' => 600, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_adamantine', ':name' => 'Adamantine', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 4, ':rarity_hi' => 5, ':cost' => 700, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_demonite', ':name' => 'Demonite', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 5, ':rarity_hi' => 5, ':cost' => 800, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'ore_godstone', ':name' => 'Godstone', ':icon' => '', ':type' => 'ore', ':rarity_lo' => 5, ':rarity_hi' => 5, ':cost' => 900, ':for_sale' => false);
  
  $item_templates[] = array(':name_id' => 'animal_horse', ':name' => 'Horse', ':icon' => '', ':type' => 'animal', ':rarity_lo' => 1, ':rarity_hi' => 3, ':cost' => 250, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'animal_pegasus', ':name' => 'Pegasus', ':icon' => '', ':type' => 'animal', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 500, ':for_sale' => false);
  
  $item_templates[] = array(':name_id' => 'herb_green', ':name' => 'Green Herb', ':icon' => '', ':type' => 'herb', ':rarity_lo' => 1, ':rarity_hi' => 2, ':cost' => 200, ':for_sale' => false);
  $item_templates[] = array(':name_id' => 'herb_red', ':name' => 'Red Herb', ':icon' => '', ':type' => 'herb', ':rarity_lo' => 3, ':rarity_hi' => 4, ':cost' => 400, ':for_sale' => false);
  
  $item_templates[] = array(':name_id' => 'revival_phoenixfeather', ':name' => 'Phoenix Feather', ':icon' => ':rpg-item-feather:', ':type' => 'revival', ':rarity_lo' => 1, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  
  $item_templates[] = array(':name_id' => 'kit_firstaid', ':name' => 'First Aid Kit', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 500, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_advsupplies', ':name' => 'Adventuring Supplies', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 350, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_guildbanner', ':name' => 'Guild Banner', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 150, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_guide', ':name' => 'Guide', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 300, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_seisreport', ':name' => 'Seismology Report', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 150, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_apprentice', ':name' => 'Magus Apprentice', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 1000, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_herbalist', ':name' => 'Herbalist Assistant', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 150, ':for_sale' => true);
  $item_templates[] = array(':name_id' => 'kit_shepherd', ':name' => 'Shepherd', ':icon' => '', ':type' => 'kit', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 150, ':for_sale' => true);

  $item_templates[] = array(':name_id' => 'relic_soulstone', ':name' => 'Soul Stone', ':icon' => '', ':type' => 'relic', ':rarity_lo' => 3, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);

  //$item_templates[] = array(':name_id' => '', ':name' => '', ':icon' => '', ':type' => '', ':rarity_lo' => 0, ':rarity_hi' => 5, ':cost' => 0, ':for_sale' => false);
  foreach ($item_templates as $item_template) {
    add_update_query("INSERT INTO item_templates (name_id, name, icon, type, rarity_lo, rarity_hi, cost, for_sale) VALUES (:name_id, :name, :icon, :type, :rarity_lo, :rarity_hi, :cost, :for_sale)", $item_template);
  }
}