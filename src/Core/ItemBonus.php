<?php

class ItemBonus {

  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */
  
  public static function apply_bonus ($item) {
    $bonus = $item->get_bonus();

    switch ($item->name_id) {
      case 'kit_firstaid':
        $bonus->add_mod(Bonus::DEATH_RATE, -0.05);
        break;

      case 'kit_advsupplies':
        $bonus->add_mod(Bonus::QUEST_SUCCESS, 0.05);
        break;

      case 'kit_guildbanner':
        $bonus->add_mod(Bonus::QUEST_REWARD_FAME, 0.05);
        break;

      case 'kit_guide':
        $bonus->add_mod(Bonus::TRAVEL_SPEED, -0.05);
        break;

      case 'kit_seisreport':
        $bonus->add_mod(Bonus::QUEST_REWARD_ITEM, 0.05);
        $bonus->add_mod(Bonus::ITEM_TYPE_FIND_RATE, 0.50, 'ItemType->'.ItemType::ORE);
        break;

      case 'kit_apprentice':
        $bonus->add_mod(Bonus::QUEST_REWARD_ITEM, 0.05);
        $bonus->add_mod(Bonus::ITEM_TYPE_FIND_RATE, 0.25, 'ItemType->'.ItemType::POWERSTONE);
        break;

      case 'kit_herbalist':
        $bonus->add_mod(Bonus::QUEST_REWARD_ITEM, 0.05);
        $bonus->add_mod(Bonus::ITEM_TYPE_FIND_RATE, 0.50, 'ItemType->'.ItemType::HERB);
        break;

      case 'kit_shepherd':
        $bonus->add_mod(Bonus::QUEST_REWARD_ITEM, 0.05);
        $bonus->add_mod(Bonus::ITEM_TYPE_FIND_RATE, 0.50, 'ItemType->'.ItemType::ANIMAL);
        break;
    }
  }
}