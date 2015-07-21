<?php

class Requirement {
  // Fields
  public $name_id;
  public $type;
  public $qty;

  const TYPE_ITEM = 'item';
  const TYPE_UPGRADE = 'upgrade';

  function __construct($data = array()) {
    // Save values to object.
    $this->__copy_data($data);
  }

  protected function __copy_data ($data) {
    // Add quantity to $data before saving it.
    if (!isset($data['qty'])) $data['qty'] = 1;

    // Parse the data and save the values.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Set quantity to integer.
    if (is_string($this->qty)) $this->qty = intval($this->qty);
  }

  /**
   * Format the requirement into a string for saving in the database.
   *
   * @return "TYPE,NAME_ID,QTY" --> Current types supported:
   *    "item" -> Requirement::TYPE_ITEM)
   *    "upgrade" -> Requirement::TYPE_UPGRADE)
   *
   * Examples:
   *    requirement of 3 iron ore returns -->  "item,ore_iron,3"
   *    requirement of 1 steel ingot returns -->  "item,ore_steel"
   *    requirement of a previous upgrade equip1 returns -->  "upgrade,equip1"
   */
  public function encode () {
    return $this->type .','. $this->name_id .($this->qty > 1 ? ','.$this->qty : '');
  }

  /**
   * Decode the string-version of a requirement and save the data into this object.
   */
  public function decode ($value) {
    $data = Requirement::from($value, false);
    $this->__copy_data($data);
  }

  public function __toString() {
    return $this->encode();
  }



  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  /**
   * Decode the string-version and create a Requirement object.
   * @see decode() for more details.
   */
  public static function from ($value, $create_object = true) {
    // Explode by comma to separate type, name_id, and quantity.
    $info = explode(',', $value);

    // Create data array.
    $data = array(
      'type' => $info[0],
      'name_id' => $info[1],
      'qty' => 1,
    );

    // Set the quantity if it's present.
    if (isset($info[2])) $data['qty'] = intval($info[2]);

    // If we're creating a new object, make it. Otherwise just return the data.
    return $create_object ? new Requirement ($data) : $data;
  }
}