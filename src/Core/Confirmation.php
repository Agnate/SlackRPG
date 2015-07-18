<?php

class Confirmation {
  
  public static $commands = array(
    'e' => 'explore',
    'q' => 'quest',
    'r' => 'recruit',
    'd' => 'dismiss',
    'u' => 'upgrade',
    'p' => 'powerup',
    'c' => 'challenge',
    't' => 'test',
  );

  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  /**
   * $parts -> an array of the commands to send to RPGSession.
   */
  public static function encode ($parts) {
    if (!is_array($parts)) return FALSE;
    if (count($parts) <= 0) return FALSE;

    // Convert first item to shortened letter.
    $short = array_search($parts[0], static::$commands);
    if ($short === FALSE) return FALSE;
    $parts[0] = $short;

    // Encrypt it to obfuscate it.
    return Confirmation::encrypt(implode(' ', $parts));
  }

  /**
   * $text -> a string containing the encoded confirmation command.
   */
  public static function decode ($text) {
    $code = explode(' ', Confirmation::decrypt($text));
    if (count($code) <= 0) return FALSE;

    // Get class type.
    $type = strtolower($code[0]);
    if (!isset(static::$commands[$type])) return FALSE;

    // Replace the shortened command.
    $code[0] = static::$commands[$type];
    
    return $code;
  }

  public static function encrypt ($text) {
    //return base64_encode(gzcompress($text));
    return base64_encode($text);
  }

  public static function decrypt ($text) {
    //return gzuncompress(base64_decode($text));
    return base64_decode($text);
  }
}