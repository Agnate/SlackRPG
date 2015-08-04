<?php
require_once('/rpg_slack/test/config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');
require_once(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');


/**
 * Show leaderboard standings in public channel.
 */
function show_leaderboard_standings ($output_information = false) {
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