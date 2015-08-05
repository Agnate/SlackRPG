<?php

class Display {
  
  public static function get_currency ($amount) {
    return number_format($amount).':rpg-coin:';
  }

  public static function get_duration_as_hours ($duration) {
    $seconds = $duration;
    $hours = floor($seconds / (60 * 60));
    $seconds -= ($hours * 60 * 60);
    $minutes = floor($seconds / 60);
    $seconds -= ($minutes * 60);
    
    return ($hours > 0 ? $hours.' hours, ' : '').($minutes > 0 ? $minutes.' minutes, ' : '').($seconds > 0 ? $seconds.' seconds' : '');
  }

  public static function get_fame ($fame) {
    return number_format($fame).':beginner:';
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

  public static function show_adventurer_count ($count) {
    return $count .':rpg-misc-adv:';
  }
}