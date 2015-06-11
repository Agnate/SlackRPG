<?php

class Queue extends RPGEntitySaveable {
  // Fields
  public $queue_id;
  public $gid;
  public $type;
  public $type_id;
  public $created;
  public $execute;

  // Private vars
  static $fields_int = array('created', 'execute');
  static $db_table = 'queue';
  static $default_class = 'Queue';
  static $primary_key = 'queue_id';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Add created timestamp if nothing did already.
    if (empty($this->created)) $this->created = time();
  }

  public function process () {
    // Load the appropriate class.
    $class_name = $this->type;
    $prim_key = $class_name::$primary_key;

    $item = $class_name::load(array($prim_key => $this->type_id));
    return $item;
  }

  /**
   * Load all queue items that are ready to execute.
   */
  public static function load_ready ($data = array(), $time = NULL) {
    // If we don't have a database table, we're done.
    if (static::$db_table == '') return FALSE;

    if (empty($time)) $time = time();

    // Generate the database tokens.
    $tokens = array();
    $new_data = array();
    foreach ($data as $key => &$value) {
      $tokens[$key] = ':'.$key;
      $new_data[':'.$key] = $value;
    }

    $where = array();
    foreach ($tokens as $key => $token) {
      $where[] = $key .'='. $token;
    }

    // Add special cause to check for queue items that are ready.
    //$tokens['time'] = ':time';
    $new_data[':time'] = $time;
    $where[] = 'execute <= :time';

    if (count($where) <= 0) return FALSE;

    $query = "SELECT * FROM ". static::$db_table ." WHERE ". implode(' AND ', $where);
    $query = pdo_prepare($query);

    if (static::$default_class != '' && class_exists(static::$default_class)) {
      $query->setFetchMode(PDO::FETCH_CLASS, static::$default_class, array());
    }
    
    $query->execute($new_data);

    $rows = array();
    if ($query->rowCount() > 0) {
      while ($row = $query->fetch()) {
        // Get the class name and load it up into there.
        if (is_object($row) && property_exists($row, 'class_name') && class_exists($row->class_name)) {
          $row = new $row->class_name ($row);
        }
        // Else if it's an array, load it.
        else if (is_array($row) && isset($row['class_name']) && class_exists($row['class_name'])) {
          $row = new $row['class_name'] ($row);
        }

        $rows[] = $row;
      }
    }

    return $rows;
  }
}