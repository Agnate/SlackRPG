<?php

class Display {
  
  public static function get_currency ($amount) {
    return number_format($amount).':rpg-coin:';
  }

  public static function currency () {
    return ':rpg-coin:_Gold_';
  }

  public static function get_duration ($duration) {
    $seconds = $duration;
    
    $days = floor($seconds / (60 * 60 * 24));
    $seconds -= ($days * 60 * 60 * 24);

    $hours = floor($seconds / (60 * 60));
    $seconds -= ($hours * 60 * 60);
    
    $minutes = floor($seconds / 60);
    $seconds -= ($minutes * 60);

    $time = array();
    if ($days > 0) $time[] = $days.' day'.($days == 1 ? '' : 's');
    if ($hours > 0) $time[] = $hours.' hour'.($hours == 1 ? '' : 's');
    if ($minutes > 0) $time[] = $minutes.' minute'.($minutes == 1 ? '' : 's');
    if ($seconds > 0) $time[] = $seconds.' second'.($seconds == 1 ? '' : 's');
    
    return implode(', ', $time);
  }

  public static function get_remaining_time ($duration, $prefix = 'in') {
    $time = Display::get_duration($duration);
    return ($duration == 0 ? 'now' : $prefix.' '.$time);
  }

  public static function get_fame ($fame) {
    return number_format($fame).':fame:';
  }

  public static function fame () {
    return ':fame:_Fame_';
  }

  public static function get_stars ($stars, $max = 5) {
    $text = '';
    // for ($i = 1; $i <= $max; $i++) $text .= ($i <= $stars ? ':quest-star:' : ':quest-star-empty:');
    for ($i = 1; $i <= $stars; $i++) $text .= ':star:';
    return $text;
  }

  public static function get_exp ($exp) {
    return $exp;
  }

  public static function get_difficulty ($rate) {
    if ($rate <= 0) return ':rpg-quest-diff0:';
    if ($rate <= 35) return ':rpg-quest-diff1:';
    if ($rate <= 50) return ':rpg-quest-diff2:';
    if ($rate <= 75) return ':rpg-quest-diff3:';
    
    return ':rpg-quest-diff4:';
  }

  public static function get_difficulty_stars ($stars, $rate) {
    if ($rate <= 0) return $stars.':rpg-star-black:'; // black
    if ($rate <= 35) return $stars.':rpg-star-red:'; // red
    if ($rate <= 50) return $stars.':rpg-star-orange:'; // orange
    if ($rate <= 75) return $stars.':rpg-star-yellow:'; // yellow
    
    return $stars.':rpg-star-green:'; // green
  }

  public static function get_difficulty_legend () {
    return "*Difficulty Legend*:\n:rpg-quest-diff0: Impossible, :rpg-quest-diff1: Risky, :rpg-quest-diff2: Difficult, :rpg-quest-diff3: Challenging, :rpg-quest-diff4: Recommended\n:skull: Adventurers can die";
  }

  public static function addOrdinalNumberSuffix ($num) {
    if (!in_array(($num % 100), array(11,12,13))) {
      switch ($num % 10) {
        // Handle 1st, 2nd, 3rd
        case 1: return $num.'st';
        case 2: return $num.'nd';
        case 3: return $num.'rd';
      }
    }
    return $num.'th';
  }

  public static function show_adventurer_count ($total, $count = null) {
    return ($count !== null ? $count.' / ' : ''). $total .':rpg-misc-adv:';
  }
}