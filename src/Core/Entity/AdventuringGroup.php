<?php

class AdventuringGroup extends RPGEntitySaveable {
  // Fields
  public $agid;
  public $gid; // Guild they is in.
  public $created;
  public $task_id;
  public $task_type;
  public $task_eta;
  public $completed;

  // Private vars
  static $fields_int = array('created', 'task_eta');
  static $db_table = 'adventuring_groups';
  static $default_class = 'AdventuringGroup';
  static $primary_key = 'agid';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
    if (empty($this->completed)) $this->completed = false;
  }
}