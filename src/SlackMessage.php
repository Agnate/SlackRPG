<?php

class SlackMessage {
  
  public $player;

  public $channel;
  public $text;
  public $parse;
  public $link_names;
  public $attachments;
  public $unfurl_links;
  public $unfurl_media;

  static $_mandatory = array('channel', 'text');
  static $_lists = array('attachments');
  static $_do_not_encode = array('player');

  function __construct ($data = array()) {
    // Save values to object.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Set defaults.
    if ($this->channel === null) $this->channel = '';
    if ($this->text === null) $this->text = '';
    if (!is_array($this->attachments)) $this->attachments = array();
  }

  public function encode () {
    // Get all the fields to save out.
    $data = call_user_func('get_object_vars', $this);

    foreach ($data as $key => $value) {
      // If we shouldn't encode this, don't.
      if (in_array($key, static::$_do_not_encode)) {
        unset($data[$key]);
        continue;
      }

      // If any of the values are null, remove them.
      if ($value === null && !in_array($key, static::$_mandatory)) {
        unset($data[$key]);
        continue;
      }

      // If these are lists, handle differently.
      if (!in_array($key, static::$_lists)) continue;

      // If the list is empty and not mandatory, remove it.
      if (empty($value) && !in_array($key, static::$_mandatory)) {
        unset($data[$key]);
        continue;
      }

      // Convert any special objects into associative arrays.
      foreach ($value as $field_key => $field) {
        $data[$key][$field_key] = method_exists($field, 'encode') ? $field->encode() : $field;
      }

      // Encode the list.
      $data[$key] = json_encode($value);
    }

    return $data;
  }

  public function is_instant_message () {
    return !empty($this->player);
  }

  public function add_attachment ($attachment) {
    $this->attachments[] = $attachment;
  }
}