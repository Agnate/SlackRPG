<?php

abstract class RPGEntitySaveable extends RPGEntity {

  // Private vars
  static $primary_key = '';

  /**
   * $duration -> defined in seconds from the current time. (eg. 40 = 40 seconds from now).
   * $guild_id -> add an optional Guild ID (if necessary).
   */
  public function queue ($duration, $guild_id = 0, $save = true) {
    // If we don't have a database table, we're done.
    if ( static::$db_table == '' ) return FALSE;
    if ( empty(static::$default_class) ) return FALSE;
    if ( empty(static::$primary_key) ) return FALSE;

    $time = time();
    $data = array(
      'type' => static::$default_class,
      'type_id' => $this->{static::$primary_key},
      'created' => $time,
      'execute' => $time + $duration,
    );
    if (!empty($guild_id)) $data['gid'] = $guild_id;

    $queue = new Queue ($data);

    $success = true;
    if ($save) $success = $queue->save();
    // If we failed to save, return false.
    if ($success === false) return $success;

    return $queue;
  }

  public function queue_process () {
    return 'No queue processing has been set up.';
  }

  public function save () {
    // If we don't have a database table, we're done.
    if ( static::$db_table == '' ) return FALSE;
    if ( empty(static::$primary_key) ) return FALSE;

    // Get database values to save out.
    $data = call_user_func('get_object_vars', $this);

    // If there's no $pid, it means it's a new lifeform.
    $is_new = empty($data[static::$primary_key]);
    if ( $is_new ) {
      unset($data[static::$primary_key]);
    }

    // Generate the database tokens.
    $tokens = array();
    $new_data = array();
    foreach ($data as $key => &$value) {
      if ($value === null) continue;

      $tokens[$key] = ':'.$key;
      $new_data[':'.$key] = $value;
    }

    // New object
    if ($is_new) {
      $query = "INSERT INTO ". static::$db_table ." (". implode(', ', array_keys($tokens)) .") VALUES (". implode(", ", array_values($tokens)) .")";
      $query = pdo_prepare($query);
      $success = $query->execute($new_data);

      // if (!$success) {
      //   d("INSERT INTO ". static::$db_table ." (". implode(', ', array_keys($tokens)) .") VALUES (". implode(", ", array_values($tokens)) .")");
      //   d($query->errorInfo());
      // }
      
      // Save the $primary_key.
      $this->{static::$primary_key} = get_pdo()->lastInsertId(static::$primary_key);
    }
    // Existing object
    else {
      $sets = array();
      foreach( $tokens as $key => $token ) {
        if ( $key == static::$primary_key ) continue;
        $sets[] = $key .'='. $token;
      }
      
      $query = "UPDATE ". static::$db_table ." SET ". implode(', ', $sets) ." WHERE ". static::$primary_key.'='.$tokens[static::$primary_key];
      $query = pdo_prepare($query);
      $success = $query->execute($new_data);
    }

    return $success;
  }

  public function delete () {
    // If we don't have a database table, we're done.
    if ( static::$db_table == '' ) return FALSE;
    if ( empty(static::$primary_key) ) return FALSE;

    $data = array(
      ':primarykey' => $this->{static::$primary_key},
    );

    // Delete the entry based on the primary key.
    $query = "DELETE FROM ".static::$db_table." WHERE ".static::$primary_key."=:primarykey";
    $query = pdo_prepare($query);
    $result = $query->execute($data);

    $info = array(
      'success' => ($result !== false),
      'result' => $result,
    );

    // If it was an error, return the error.
    if ( $result === false ) {
      $info['error'] = $query->errorInfo();
    }

    return $info;
  }
}