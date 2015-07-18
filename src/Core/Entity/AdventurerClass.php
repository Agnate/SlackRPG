<?php

class AdventurerClass extends RPGEntitySaveable {
  // Fields
  public $acid;
  public $name_id;
  public $name;
  public $icon;
  public $class_name; // PHP Object class name if there is an extended implementation.

  // Private vars
  static $fields_int = array();
  static $db_table = 'adventurer_classes';
  static $default_class = 'AdventurerClass';
  static $primary_key = 'acid';

  static $_all_classes = array();

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );
  }

  public function get_display_name () {
    return $this->name;
  }

  public function apply_bonus ($adventurer) {
    $bonus = $adventurer->get_bonus();

    switch ($this->name_id) {
      case 'shaman':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::DEATH_RATE, -0.05);
        // Challenge mods.
        $bonus->add_mod(Bonus::BREAK_AS_SUCCESS, 0.05, 'Challenge->'.Challenge::MOVE_BREAK);
        break;

      case 'brigand':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::QUEST_REWARD_GOLD, 0.10);
        $bonus->add_mod(Bonus::QUEST_REWARD_ITEM, 0.03);
        // Challenge mods.
        $bonus->add_mod(Bonus::LOSS_BY_ONE_AS_TIE, 0.05);
        break;

      case 'judge':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::QUEST_REWARD_FAME, 0.05);
        // Challenge mods.
        $bonus->add_mod(Bonus::ATTACK_AS_SUCCESS, 0.05, 'Challenge->'.Challenge::MOVE_ATTACK);
        break;

      case 'magus':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::QUEST_REWARD_EXP, 0.10);
        // Challenge mods.
        $bonus->add_mod(Bonus::CRIT_RATE, 0.15);
        $bonus->add_mod(Bonus::LOSS_ON_SUCCESS, 0.10, 'Challenge->'.Challenge::MOVE_DEFEND);
        break;

      case 'dragoon':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::QUEST_SUCCESS, 0.05, 'Quest->'.Quest::TYPE_BOSS);
        $bonus->add_mod(Bonus::QUEST_SUCCESS, 0.05, 'Quest->'.Quest::TYPE_FIGHT);
        $bonus->add_mod(Bonus::QUEST_SPEED, -0.10, 'Quest->'.Quest::TYPE_BOSS);
        $bonus->add_mod(Bonus::QUEST_SPEED, -0.10, 'Quest->'.Quest::TYPE_FIGHT);
        // Challenge mods.
        $bonus->add_mod(Bonus::MISS_RATE, -0.05);
        $bonus->add_mod(Bonus::CRIT_RATE, 0.05);
        break;

      case 'strider':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::TRAVEL_SPEED, -0.05);
        // Challenge mods.
        $bonus->add_mod(Bonus::OPPONENT_MISS_RATE, 0.08);
        break;

      case 'oracle':
        // Questing/Exploring mods.
        $bonus->add_mod(Bonus::QUEST_SUCCESS, 0.05);
        // Challenge mods.
        $bonus->add_mod(Bonus::TIE_BREAKER_ON_FAIL, 0.05);
        break;

      case 'juggernaut':
        // Questing/Exploring mods.
        $adventurer->level += 2;
        // Challenge mods.
        $bonus->add_mod(Bonus::DEFEND_AS_SUCCESS, 0.05, 'Challenge->'.Challenge::MOVE_DEFEND);
        break;
    }
  }


  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function all_classes () {
    // If we've already loaded all the classes, return it now.
    if (!empty(static::$_all_classes)) return static::$_all_classes;
    // Load all the classes.
    $adventurer_classes = AdventurerClass::load_multiple(array());
    $class_ids = array();
    foreach ($adventurer_classes as $adventurer_class) $class_ids[] = $adventurer_class->name_id;
    static::$_all_classes = $class_ids;
    return static::$_all_classes;
  }
}