<?php

class Enhancement {
  // Fields
  public $bonus;
  public $value;
  public $for;

  function __construct($data = array()) {
    // Save values to object.
    $this->__copy_data($data);
  }

  protected function __copy_data ($data) {
    // Add for to $data before saving it.
    if (!isset($data['for'])) $data['for'] = Bonus::FOR_DEFAULT;

    // Parse the data and save the values.
    if (count($data)) {
      foreach ($data as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    // Set value to float.
    if (is_string($this->value)) $this->value = floatval($this->qty);
  }

  public function get_display_name () {
    $bonus_name = Bonus::get_name($this->bonus, $this->value, $this->for);
    $change = $this->value >= 0 ? 'Increased' : 'Decreased';
    return $change .' '. $bonus_name;
  }

  /**
   * Format for the enhancement into a string for saving in the database.
   *
   * $value -> "BONUS,VALUE|BONUS,VALUE,FOR"   (item separator = "|", divider between bonus name, value, and for = ",")
   *    BONUS -> Bonus types (example: Bonus::TRAVEL_SPEED)
   *    VALUE -> Bonus value (example: 5, 0.15, -1.05)
   *    FOR -> Specificity of what the bonus applies to (example: Bonus::FOR_DEFAULT or "Quest->".Quest::TYPE_BOSS)
   *
   * Examples:
   *    increase travel speed by 5% returns -->  "_travel_speed,-0.05"
   *    increase quest success by 5% returns -->  "_quest_success,0.05,Quest->boss"
   */
  public function encode () {
    return $this->bonus .','. $this->value .($this->for != Bonus::FOR_DEFAULT ? ','.$this->for : '');
  }

  /**
   * Decode the string-version of an enhancement and save the data into this object.
   */
  public function decode ($value) {
    $data = Enhancement::from($value, false);
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
   * Decode the string-version and create an Enhancement object.
   * @see decode() for more details.
   */
  public static function from ($value, $create_object = true) {
    // Explode by comma to separate mod name, value, and for info.
    $info = explode(',', $value);

    // Create data array.
    $data = array(
      'bonus' => $info[0],
      'value' => floatval($info[1]),
      'for' => Bonus::FOR_DEFAULT,
    );

    // Set the for info if it's present.
    if (isset($info[2])) $data['for'] = $info[2];

    // If we're creating a new object, make it. Otherwise just return the data.
    return $create_object ? new Enhancement ($data) : $data;
  }
}