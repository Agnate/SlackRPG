<?php

class JSONList {
  
  public static function generate_name (&$json_list, $original_json_list, $default_tokens = array()) {
    if (!is_array($default_tokens)) return FALSE;

    $info = array(
      'name' => '',
      'keywords' => array(),
    );

    // If it is format-based, pick a format and generate the pieces.
    if (isset($json_list['format'])) {
      $format_index = array_rand($json_list['format']);
      $format = $json_list['format'][$format_index];
      // Create the list of substitution tokens.
      $tokens = $default_tokens;
      foreach ($json_list as $token => &$data) {
        if ($token == 'format') continue;
        if (!isset($data['parts'])) continue;
        // Generate the token value.
        $join = isset($data['join']) ? $data['join'] : ' ';
        $parts = JSONList::generate_from_parts($data['parts'], $original_json_list[$token]['parts']);
        $tokens[$token] = implode($join, $parts);
      }
      $token_keys = array_keys($tokens);
      $info['keywords'] = array_values($tokens);
      $info['name'] = str_replace($token_keys, $info['keywords'], $format);
      // Add format to the keywords after the name replacement.
      $keyword = str_replace($token_keys, '', $format);
      $info['keywords'][] = trim($keyword);
    }
    // If it's just a series of parts, connect them.
    else if (isset($json_list['parts'])) {
      $join = isset($json_list['join']) ? $json_list['join'] : ' ';
      $info['keywords'] = JSONList::generate_from_parts($json_list['parts'], $original_json_list['parts']);
      $info['name'] = implode($join, $info['keywords']);
    }

    return $info;
  }

  protected static function generate_from_parts (&$parts, $original_parts) {
    if (is_string($parts)) return $parts;
    if (!is_array($parts)) return '';

    // If there are arrays for each part, randomly pick one.
    $name = array();
    foreach ($parts as $key => &$list) {
      if (is_array($list)) {
        $index = array_rand($list);
        // Re-index
        if ($index === NULL) {
          $list = $original_parts[$key];
          $index = array_rand($list);
        }
        $name[] = $list[$index];
        unset($list[$index]);
      }
      else if (is_string($list)) $name[] = $list;
    }

    return $name;
  }
}