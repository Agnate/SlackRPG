<?php

/**
 * Name the function with the version number, as this is how we'll verify the update.
 */
function update_version_0_0_1 ($forced = false) {
  // Update Adventurers table.
  add_update_query("ALTER TABLE adventurers ADD COLUMN enhancements VARCHAR(255) NOT NULL");
  add_update_query("ALTER TABLE locations ADD COLUMN map_icon VARCHAR(255) NOT NULL");
  add_update_query("ALTER TABLE locations ADD COLUMN open TINYINT(1) NOT NULL");
  add_update_query("ALTER TABLE quests CHANGE permanent completed TINYINT(1) NOT NULL");
  add_update_query("ALTER TABLE quests ADD COLUMN guild_limit INT(10) UNSIGNED NOT NULL");
}