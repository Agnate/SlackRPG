<?php
/* This file contains 2 classes: SlackAttachment and SlackAttachmentField */

class SlackAttachment {

  public $fallback;
  public $color;
  public $pretext;
  public $author_name;
  public $author_link;
  public $author_icon;
  public $title;
  public $title_link;
  public $text;
  public $fields; // List of SlackAttachmentField objects (see bottom of file for other class).
  public $image_url;
  public $thumb_url;
  public $mrkdwn_in;

  static $_mandatory = array('fallback', 'text', 'mrkdwn_in');
  static $_lists = array('fields', 'mrkdwn_in');

  const COLOR_RED = '#D50200';
  const COLOR_GREEN = '#2FA44F';
  const COLOR_BLUE = '#1F87BC';

  function __construct ($data = array()) {
    // Save values to object.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Defaults
    if ($this->fallback === null) $this->fallback = '';
    if ($this->text === null) $this->text = '';
    if (!is_array($this->fields)) $this->fields = array();
    if ($this->mrkdwn_in === null) $this->mrkdwn_in = array('pretext', 'text', 'fields');
  }

  public function encode () {
    // Get all the fields to save out.
    $data = call_user_func('get_object_vars', $this);

    foreach ($data as $key => $value) {
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

  public function add_field ($field) {
    $this->fields[] = $field;
  }
}





class SlackAttachmentField {

  public $title;
  public $value;
  public $short;

  function __construct ($data = array()) {
    // Save values to object.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Defaults
    if ($this->title === null) $this->title = '';
    if ($this->value === null) $this->value = '';
    if ($this->short === null) $this->short = 'false';
  }

  public function encode () {
    // Get all the fields to save out.
    return call_user_func('get_object_vars', $this);
  }
}