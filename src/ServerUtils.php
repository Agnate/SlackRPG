<?php
use \Kint;

require_once(RPG_SERVER_ROOT.'/src/autoload.php');

class ServerUtils {

  public static function get_next_refresh_time ($intervals) {
    // Get the current time and find the closest one.
    $now = time();
    $tomorrow = strtotime('+1 day', strtotime(date('Y-m-d').' 00:00:00'));

    // Check today for the closest interval.
    foreach ($intervals as $interval) {
      $time = strtotime(date('Y-m-d').' '.$interval);
      // If this interval has not passed, it's our interval.
      if ($time > $now) return $time;
    }

    // Looks like we need an interval from tomorrow.
    foreach ($intervals as $interval) {
      $time = strtotime(date('Y-m-d', $tomorrow).' '.$interval);
      // If this interval has not passed, it's our interval.
      if ($time > $now) return $time;
    }

    // Something is messed up if we're still here, so just return now.
    return $now;
  }

  /**
       _____ _________   _____ ____  _   __
      / ___// ____/   | / ___// __ \/ | / /
      \__ \/ __/ / /| | \__ \/ / / /  |/ / 
     ___/ / /___/ ___ |___/ / /_/ / /|  /  
    /____/_____/_/  |_/____/\____/_/ |_/   
                                           
  */
  public static function start_new_season ($output_information = false) {
    $time = time();
    $hours = 60 * 60;
    $days = $hours * 24;

    // Clear out the queue.
    if ($output_information) print "Deleting all active queues (if there are any)...";
    $queues = Queue::load_multiple(array());
    if (!empty($queues)) {
      foreach ($queues as $queue) {
        $queue->delete();
      }
    }
    if ($output_information) print " Done.\n";

    // Disable any currently-active seasons.
    if ($output_information) print "Disabling old seasons (if there are any)...";
    $old_seasons = Season::load_multiple(array('active' => true));
    if (!empty($old_seasons)) {
      foreach ($old_seasons as $old_season) {
        $old_season->active = false;
        $old_season->save();
      }
    }
    if ($output_information) print " Done.\n";

    // Set any adventurers that are in the tavern to unavailable.
    if ($output_information) print "Disabling adventurers in the tavern (if there are any)...";
    $adventurers = Adventurer::load_multiple(array('available' => true));
    if (!empty($adventurers)) {
      foreach ($adventurers as $adventurer) {
        $adventurer->available = false;
        $adventurer->save();
      }
    }
    if ($output_information) print " Done.\n";

    // Maybe show some kind of "game over" message with some stats and winners?

    // Reset name lists.
    ServerUtils::reset_json_lists($output_information);

    // Create a new season.
    if ($output_information) print "Creating new season...";
    $season_data = array('created' => $time, 'duration' => 30*$days, 'active' => false);
    $season = new Season ($season_data);
    $season->save();
    if ($output_information) print " Done.\n";

    // Create new map.
    if ($output_information) print "Creating new map...";
    $map_data = array('season' => $season->sid, 'created' => $time);
    $map = new Map ($map_data);
    $map->save();
    if ($output_information) print " Done.\n";

    // Generate the locations.
    if ($output_information) print "Creating new locations...";
    $locations = $map->generate_locations();
    if ($output_information) print " ".count($locations)." created.\n";

    // Set the season to active.
    if ($output_information) print "Setting season to active...";
    $season->active = true;
    $season->save();
    if ($output_information) print " Done.\n";

    // Generate the map image.
    if ($output_information) print "Generating map image...";
    $mapimage = MapImage::generate_image($map);
    if ($output_information) print " Done.\n";

    // Refresh the Tavern to add new adventurers for hire.
    if ($output_information) print "Refreshing the Tavern...\n";
    ServerUtils::generate_new_adventurers($output_information);
    if ($output_information) print " Done.\n";

    // Done!
    if ($output_information) print "\nThe new season has now started!\n";
  }

  /**
   * Reset all JSON lists.
   */
  public static function reset_json_lists ($output_information = false) {
    // Reset adventurer names and icons.
    if ($output_information) print "Resetting adventurer names list...";
    Adventurer::refresh_original_adventurer_names_list();
    if ($output_information) print " Done.\n";

    // Reset challenge texts.
    if ($output_information) print "Resetting challenge texts list...";
    Challenge::refresh_original_texts_list();
    if ($output_information) print " Done.\n";

    // Reset location names.
    if ($output_information) print "Resetting location names list...";
    Location::refresh_original_location_names_list();
    if ($output_information) print " Done.\n";

    // Reset quest names.
    if ($output_information) print "Resetting quest names list...";
    Quest::refresh_original_quest_names_list();
    if ($output_information) print " Done.\n";

    if ($output_information) print "All JSON lists have be reset.\n";
  }



  /**
      _________ _    ____________  _   __
     /_  __/   | |  / / ____/ __ \/ | / /
      / / / /| | | / / __/ / /_/ /  |/ / 
     / / / ___ | |/ / /___/ _, _/ /|  /  
    /_/ /_/  |_|___/_____/_/ |_/_/ |_/   
                                         
  */


  public static function reset_tavern ($output_information = false) {
    // Clean out the tavern.
    ServerUtils::clean_out_tavern($output_information);

    // Determine the number of adventurers to create based on the number of Guilds.
    $guilds = Guild::current();
    $multiplier = 1 + floor(count($guilds) / 10);
    $num_new = $multiplier * 3;

    // Generate new adventurers for the tavern.
    if ($output_information) print "Creating ".$num_new." new adventurers for the Tavern...";
    ServerUtils::generate_new_adventurers($output_information, $num_new);
  }

  public static function trickle_tavern ($output_information = false) {
    // Get the current adventurers in the tavern.
    $adventurers = Adventurer::load_multiple(array('available' => true, 'gid' => 0, 'agid' => 0, 'dead' => false));

    // Determine the trickle limit of adventurers based on the number of Guilds.
    $guilds = Guild::current();
    $trickle_limit = 1 + floor(count($guilds) / 10);
    $num_new = $trickle_limit - count($adventurers);

    // If the tavern is low on adventurers, create some.
    if ($num_new > 0) {
      if ($output_information) print "Trickling in ".$num_new." new adventurer(s) for the Tavern...";
      ServerUtils::generate_new_adventurers($output_information, $num_new);
    }
    else {
      if ($output_information) print "Tavern has enough adventurers.";
    }
  }

  /**
   * Remove all available adventurers.
   */
  public static function clean_out_tavern ($output_information = false) {
    // Load up the list of adventurer names.
    $json = Adventurer::load_adventurer_names_list();

    // Get all of the available adventurers.
    $adventurers = Adventurer::load_multiple(array('available' => true, 'gid' => 0, 'agid' => 0, 'dead' => false));

    // Get the names from the adventurers and re-insert them into the JSON file of names.
    foreach ($adventurers as $adventurer) {
      // Recycle the Adventurer's name and icon by adding it back to the JSON file.
      Adventurer::recycle_adventurer($adventurer, $json);

      // Delete the adventurer.
      $adventurer->delete();

      if ($output_information) print 'Deleted adventurer: '.$adventurer->name."\n";
    }

    // Add the names back to the JSON file.
    Adventurer::save_adventurer_names_list($json);
  }


  /**
   * Generate new adventurers and make them available to players.
   */
  public static function generate_new_adventurers ($output_information = false, $num_adventurers = 3, $special_class_rate = 1, $special_limit = 1) {
    // Load up the list of adventurer names.
    $json = Adventurer::load_adventurer_names_list();

    // Get list of the 3 current adventurers with the highest level.
    $highest_levels = Adventurer::load_multiple(array(), "ORDER BY level DESC, gid DESC LIMIT 3");
    // Average out the levels to get the highest.
    $levels = 0;
    $highest_level = 0;
    foreach ($highest_levels as $high) {
      $levels += $high->level;
      if ($high->level > $highest_level) $highest_level = $high->level;
    }
    $avg_level = ($levels > 0 && count($highest_levels) > 0) ? max(1, floor(($levels / count($highest_levels)) * 0.90)) : 1;

    // Generate some adventurers.
    $time = time();
    $adventurers = array();
    for ($i = 0; $i < $num_adventurers; $i++) {
      // Determine if they have a special class.
      $has_special_class = $special_limit > 0 && (rand(1, 100) <= $special_class_rate);
      if ($has_special_class) $special_limit--;

      // Generate the adventurer.
      $adventurer = Adventurer::generate_new_adventurer($has_special_class, false, $json);

      // Change the level.
      $adventurer->set_level(rand(1, $avg_level));
      $adventurer->exp = $adventurer->calculate_exp_tnl( $adventurer->get_level(false) );
      $adventurer->exp += rand(1, floor(($adventurer->exp_tnl - $adventurer->exp) / 3));
      $adventurer->save();
      $adventurers[] = $adventurer;
    }

    // Write out the JSON file to prevent names from being reused.
    Adventurer::save_adventurer_names_list($json);

    // Output the adventurers that were created.
    if ($output_information)
      foreach ($adventurers as $adventurer) print 'Created new adventurer: '.$adventurer->name."\n";
  }



  /**
        __    _________    ____  __________  ____  ___    ____  ____ 
       / /   / ____/   |  / __ \/ ____/ __ )/ __ \/   |  / __ \/ __ \
      / /   / __/ / /| | / / / / __/ / __  / / / / /| | / /_/ / / / /
     / /___/ /___/ ___ |/ /_/ / /___/ /_/ / /_/ / ___ |/ _, _/ /_/ / 
    /_____/_____/_/  |_/_____/_____/_____/\____/_/  |_/_/ |_/_____/  
                                                                     
  */

  /**
   * Show leaderboard standings in public channel.
   */
  public static function show_leaderboard_standings ($output_information = false) {
    // Get the currently-active season so that we pick the right Guild.
    $season = Season::current();
    if (empty($season)) return FALSE;

    // Load all Guilds.
    $guilds = Guild::load_multiple(array('season' => $season->sid));
    if (empty($guilds)) return FALSE;

    // Sort Guilds by fame.
    usort($guilds, array('Guild','sort'));

    $max = 10;
    $count = 0;
    $response = array();
    $names = array();
    foreach ($guilds as $guild) {
      $count++;
      $response[] = Display::addOrdinalNumberSuffix($count).': ('.Display::get_fame($guild->get_total_points()).') '.$guild->get_display_name();
      $names[] = $guild->get_display_name();
      if ($count == $max) break;
    }

    $attachment = new SlackAttachment ();
    $attachment->text = implode("\n", $response);
    $attachment->title = 'Top '.$max.' Guild Ranking:';
    $attachment->fallback = $attachment->title .' '. implode(", ", $names);
    $attachment->color = SlackAttachment::COLOR_BLUE;

    $message = new SlackMessage ();
    $message->text = 'Leaderboard standings for '.date('M j, Y').':';
    $message->add_attachment($attachment);

    return $message;
  }



  /**
       ____  __  ________________________
      / __ \/ / / / ____/ ___/_  __/ ___/
     / / / / / / / __/  \__ \ / /  \__ \ 
    / /_/ / /_/ / /___ ___/ // /  ___/ / 
    \___\_\____/_____//____//_/  /____/  
                                         
  */

  /**
   * Remove all available quests.
   */
  public static function remove_available_quests ($output_information = false) {
    // Load up the list of quest names.
    //$json = Quest::load_quest_names_list();

    // Get all of the available quests.
    // $quests = Quest::load_multiple(array('active' => true, 'permanent' => false, 'gid' => 0, 'agid' => 0));

    // // Get the names from the quests and re-insert them into the JSON file of names.
    // foreach ($quests as $quest) {
    //   // Recycle the Quest's name and icon by adding it back to the JSON file.
    //   Quest::recycle_quest($quest, $json);

    //   // Delete the quest.
    //   $quest->delete();

    //   if ($output_information) print 'Deleted quest: '.$quest->name."\n";
    // }

    // Add the names back to the JSON file.
    //Quest::save_quest_names_list($json);
  }


  /**
   * Generate new quests and make them available to players.
   */
  public static function generate_new_quests ($output_information = false, $num_quests = -1, $num_multiplayer_quests = -1) {
    // Get the current season.
    $season = Season::current();
    if (empty($season)) {
      if ($output_information) print "Could not find an active season.\n";
      return FALSE;
    }

    // Load up the list of quest names.
    $json = Quest::load_quest_names_list();
    $original_json = Quest::load_quest_names_list(true);

    // Get list of all revealed locations.
    $locations = Location::get_all_unique_locations();
    
    // If there are no revealed locations, we can't generate quests yet.
    if (empty($locations)) {
      if ($output_information) print "No new quests were created because no locations have been revealed.\n";
      return FALSE;
    }

    // Sort out locations by star-rating.
    $all_locations = Location::sort_locations_by_star($locations);

    // Get all the active Guilds this season.
    $guilds = Guild::load_multiple(array('season' => $season->sid));
    $total_guild_count = count($guilds);

    // Randomly choose a number of personal quests for all Guilds.
    if ($num_quests < 0) $num_quests = rand(2, 4);
    if ($num_multiplayer_quests < 0) $num_multiplayer_quests = rand(1, ceil($total_guild_count / 4));

    // Generate quests for each Guild.
    $guild_count = 0;
    $guilds_with_quests = array();
    foreach ($guilds as $guild) {
      // Check the number of current quests, as we do not want to exceed the max quest allowance.
      $cur_quests = $guild->get_quests();
      $quest_count = count($cur_quests);
      $num_new_quests = min($num_quests, Quest::MAX_COUNT - $quest_count);
      // If we've hit or exceeded the limit, do not create any quests.
      if ($num_new_quests <= 0) {
        if ($output_information) print $guild->name.' has hit or exceeded the quest limit ('.Quest::MAX_COUNT.").\n";
        continue;
      }

      // Determine the location list that the generator should pull from,
      // choosing lower-star locations for Guilds with weaker adventurers.
      $original_locations = ServerUtils::get_appropriate_locations_for_guild($all_locations, $guild);

      // Unset locations to prevent the previous Guild's level-appropriate
      // quests from bleeding into the next Guild's.
      unset($locations);

      $quests = array();
      for ($i = 0; $i < $num_new_quests; $i++) {
        // Choose a location to theme the quest with.
        if (empty($locations)) $locations = $original_locations;
        $loc_key = array_rand($locations);
        $location = $locations[$loc_key];
        unset($locations[$loc_key]);
        // If we still don't have a location, there's a problem so break out.
        if (empty($location)) break;
        // Generate the quest.
        $gquest = Quest::generate_personal_quest($guild, $location, $json, $original_json, true);
        if (!empty($gquest)) $quests[] = $gquest;
      }

      $guild_count++;
      $guilds_with_quests[] = $guild;

      if ($output_information) print 'Generated '.count($quests).' quest(s) for '.$guild->name.".\n";
    }

    // Generate some multiplayer quests.
    $mquests = array();
    if ($num_multiplayer_quests > 0) {
      // Maximum number of multiplayer quests available is a third of the total registered guilds.
      $max_multiplayer = ceil($total_guild_count / 3);

      // Load up any current multiplayer quests so we don't generate too many.
      $cur_mquests = Quest::load_multiple(array('multiplayer' => true, 'active' => true));

      // Check how many quests we can make.
      $num_multiplayer_quests = min($num_multiplayer_quests, $max_multiplayer - count($cur_mquests));
      if ($num_multiplayer_quests > 0) {
        unset($locations);
        for ($i = 0; $i < $num_multiplayer_quests; $i++) {
          // Choose a location to theme the quest with.
          if (empty($locations)) $locations = $all_locations['all'];
          $loc_key = array_rand($locations);
          $location = $locations[$loc_key];
          unset($locations[$loc_key]);
          // If we still don't have a location, there's a problem so break out.
          if (empty($location)) break;
          // Generate the multiplayer quest.
          $mquest = Quest::generate_multiplayer_quest($location, $json, $original_json, true);
          if (!empty($mquest)) $mquests[] = $mquest;
        }

        if ($output_information) print 'Generated '.count($mquests)." Multi-Guild quest(s).\n";
      }
    }

    // Write out the JSON file to prevent names from being reused.
    Quest::save_quest_names_list($json);

    // Output the quests that were created.
    if ($output_information) print 'Created new quests for '.$guild_count." Guild(s).\n";

    return $guilds_with_quests;
  }

  /**
   * Get locations that are appropriate for this Guild (based on star rating and adventurer levels).
   *
   * @return -> Returns a list of level-appropriate locations. If none exist, it returns all locations.
   */
  public static function get_appropriate_locations_for_guild ($sorted_locations, $guild) {
    // Determine the location list that the generator should pull from,
    // choosing lower-star locations for Guilds with weaker adventurers.
    $level = $guild->calculate_adventurer_level_info();
    $star = Quest::calculate_appropriate_star_range($level['lo'], $level['hi']);
    $locations = array();
    // Loop through all locations and merge together the viable locations.
    for ($s = $star['lo']; $s <= $star['hi']; $s++) {
      if (!isset($sorted_locations[$s])) continue;
      $locations = array_merge($locations, $sorted_locations[$s]);
    }

    // If there are no locations for this star range (for whatever reason), default to all locations.
    if (empty($locations)) $locations = $sorted_locations['all'];

    return $locations;
  }

  public static function get_quest_is_generated_message ($guild, $attachment_only = false) {
    $attachment = new SlackAttachment ();
    $attachment->title = 'Guild Update';
    $attachment->text = 'You have a new quest. Type `quest` to view it.';
    $attachment->fallback = $attachment->title .' - '. $attachment->text;
    $attachment->color = SlackAttachment::COLOR_BLUE;
    if ($attachment_only) return $attachment;

    $message = new SlackMessage (array('player' => $guild));
    $message->add_attachment($attachment);

    return $message;
  }

  public static function get_boss_quest_is_generated_message ($attachment_only = false) {
    $attachment = new SlackAttachment ();
    $attachment->title = 'Boss Approaching';
    $attachment->text = 'A new boss has appeared and is spreading mayhem across the countryside! Type `quest` to view it.';
    $attachment->fallback = $attachment->title .' - '. $attachment->text;
    $attachment->color = SlackAttachment::COLOR_BLUE;
    if ($attachment_only) return $attachment;

    $message = new SlackMessage ();
    $message->add_attachment($attachment);

    return $message;
  }
}