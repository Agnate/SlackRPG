<?php

abstract class RPGEntity {

  // Private vars
  static $fields_int;
  static $db_table = '';
  static $default_class = '';
  static $partials = array('name');

  function __construct($data = array()) {
    // Save values to object.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Set some more defaults.
    if (!empty(static::$fields_int)) {
      foreach (static::$fields_int as $field) {
        if (empty($this->{$field})) $this->{$field} = 0;
        else if (!empty($this->{$field}) && !is_int($this->{$field})) $this->{$field} = (int)$this->{$field};
      }
    }
  }

  protected function get_db () {
    $class = get_class($this);
    return $class::$db_table;
  }

  public static function load ($data, $find_partials = false) {
    // If we don't have a database table, we're done.
    if (static::$db_table == '') {
      return FALSE;
    }

    // Generate the database tokens.
    $tokens = array();
    $new_data = array();
    foreach ($data as $key => &$value) {
      if (is_array($value)) {
        $tokens[$key] = array();
        $count = 0;
        foreach ($value as $subvalue) {
          $count++;
          $tokens[$key][] = ':'.$key.'_i'.$count;
          $new_data[':'.$key.'_i'.$count] = $subvalue;
        }
      }
      else {
        $tokens[$key] = ':'.$key;
        $new_data[':'.$key] = ($find_partials && in_array($key, static::$partials)) ? '%'.$value.'%' : $value;  
      } 
    }

    $where = array();
    foreach ($tokens as $key => $token) {
      // If this is an array of tokens, put it into an IN statement.
      if (is_array($token)) $where[] = $key .' IN ('. implode(',', $token) .')';
      // Else if need to look up partials, do that.
      else if ($find_partials && in_array($key, static::$partials)) $where[] = $key .' LIKE '. $token;
      // Otherwise just find the value given.
      else $where[] = $key .'='. $token;
    }

    if (count($where) <= 0) return FALSE;

    $query = "SELECT * FROM ". static::$db_table ." WHERE ". implode(' AND ', $where) ." LIMIT 1";
    $query = pdo_prepare($query);

    if (static::$default_class != '' && class_exists(static::$default_class)) {
      $query->setFetchMode(PDO::FETCH_CLASS, static::$default_class, array());
    }
    
    $query->execute($new_data);

    if ($query->rowCount() <= 0) {
      return array();
    }

    $row = $query->fetch();

    // Get the class name and load it up into there.
    if (is_object($row)) {
      if (!property_exists($row, 'class_name') || !class_exists($row->class_name)) {
        return $row;
      }

      $new_row = new $row->class_name ( $row );
    }
    // If it's not an object, it has to be an array.
    else {
      if (!isset($row['class_name']) || !class_exists($row['class_name'])) {
        return $row;
      }

      $new_row = new $row['class_name'] ($row);
    }

    // If the new class is not a subclass (aka, not in the same lineage of classes), we shouldn't use it.
    // (NOTE: If I upgrade to PHP 5.5, I can use Reflection, which might be smarter)
    if (static::$default_class != '' && !is_subclass_of($new_row, static::$default_class)) {
      return $row;
    }

    return $new_row;
  }

  /**
   * Loads multiple rows of data based on the query $data provided.
   * $data -> An array of exact values that the query will search for.
   * $special -> The text here is tacted onto the end of the query. It's useful for things like "order by" and "limit".
   */
  public static function load_multiple ($data, $special = "") {
    // If we don't have a database table, we're done.
    if (static::$db_table == '') return FALSE;

    // Generate the database tokens.
    $tokens = array();
    $new_data = array();
    foreach ($data as $key => &$value) {
      if (is_array($value)) {
        $tokens[$key] = array();
        $count = 0;
        foreach ($value as $subvalue) {
          $count++;
          $tokens[$key][] = ':'.$key.'_i'.$count;
          $new_data[':'.$key.'_i'.$count] = $subvalue;
        }
      }
      else {
        $tokens[$key] = ':'.$key;
        $new_data[':'.$key] = $value;  
      } 
    }

    $where = array();
    foreach ($tokens as $key => $token) {
      if (is_array($token)) $where[] = $key .' IN ('. implode(',', $token) .')';
      else $where[] = $key .'='. $token;
    }

    $query = "SELECT * FROM ". static::$db_table .(count($where) > 0 ? " WHERE ". implode(' AND ', $where) : "") .(!empty($special) ? " ".$special : "");
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